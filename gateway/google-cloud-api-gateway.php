<?php
/**
 * ==========================================================================================
 * GOOGLE CLOUD API GATEWAY - CORE AUTH & ROUTER
 * ==========================================================================================
 * @author       Gringo & Adrian (Architects)
 * @version      7.0 (Pure Microservice Architecture)
 * @description  Czysty Gateway. Odpowiada WYŁĄCZNIE za:
 *               1. Odczyt .env
 *               2. Cykl życia OAuth 2.0 (Logowanie, Odświeżanie, Usuwanie)
 *               3. Wstrzykiwanie nagłówków autoryzacyjnych do zapytań (Proxy)
 *               4. Panel Sterowania (UI) w przypadku bezpośredniego wejścia.
 * ==========================================================================================
 */

error_reporting(0);
if (session_status() === PHP_SESSION_NONE) {
    // Czas trwania sesji w przeglądarce i na serwerze (np. 30 dni)
    ini_set('session.cookie_lifetime', 60 * 60 * 24 * 30);
    ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
    session_start();
}

$env_path = __DIR__ . '/../../.env';
if (!file_exists($env_path)) die(json_encode(['error' => 'Brak pliku .env']));

$env = [];
$lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if (strpos($line, '#') === 0) continue; 
    if (strpos($line, '=') !== false) {
        list($key, $value) = explode('=', $line, 2);
        $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

$client_id     = $env['GOOGLE_CLIENT_ID'] ?? '';
$client_secret = $env['GOOGLE_CLIENT_SECRET'] ?? '';
$redirect_uri  = $env['GOOGLE_REDIRECT_URI'] ?? '';
$scopes        = $env['GOOGLE_SCOPES'] ?? '';
// Przechowujemy tokeny w bezpiecznej sesji PHP użytkownika z fallbackiem do token.json dla wywołań CLI/Proxy
$token_exists = isset($_SESSION['google_tokens']);
$tokens = $token_exists ? $_SESSION['google_tokens'] : null;
if ($tokens) {
    // Natychmiastowy zapis do token.json w celu udostępnienia dla skryptów zewnętrznych/CLI
    file_put_contents(__DIR__ . '/token.json', json_encode($tokens));
} else if (file_exists(__DIR__ . '/token.json')) {
    $tokens = json_decode(file_get_contents(__DIR__ . '/token.json'), true);
}

/**
 * RDZEŃ SYSTEMU: Funkcja uderzająca do Google, dostępna dla innych skryptów backendowych.
 */
function google_api_call($path, $method='GET', $body=null) {
    global $tokens, $client_id, $client_secret;
    if (!$tokens) return ['error' => 'auth_required'];
    
    $url = (strpos($path, 'http') === 0) ? $path : "https://www.googleapis.com/" . $path;
    $headers = ['Authorization: Bearer ' . $tokens['access_token']];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($body) { 
        if (is_array($body)) {
            $headers[] = 'Content-Type: application/json'; 
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body)); 
        } else {
            // Surowy plik binarny (np. do wgrywania miniaturki)
            $headers[] = 'Content-Type: image/png';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch); 
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Auto-Odświeżanie
    if ($code == 401) { 
        $rc = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($rc, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($rc, CURLOPT_POSTFIELDS, [
            'client_id' => $client_id, 'client_secret' => $client_secret, 
            'refresh_token' => $tokens['refresh_token'] ?? '', 'grant_type' => 'refresh_token'
        ]);
        $tr = json_decode(curl_exec($rc), true);
        if (isset($tr['access_token'])) {
            $tokens['access_token'] = $tr['access_token'];
            $_SESSION['google_tokens'] = $tokens;
            file_put_contents(__DIR__ . '/token.json', json_encode($tokens));
            return google_api_call($path, $method, $body); 
        } else { 
            unset($_SESSION['google_tokens']); 
            if (file_exists(__DIR__ . '/token.json')) @unlink(__DIR__ . '/token.json');
            return ['error' => 'auth_required']; 
        }
    }
    if ($code == 204) return ['status' => 'deleted_successfully'];
    return json_decode($res, true) ?? ['error' => 'Empty response', 'http_code' => $code];
}

// ==========================================================================================
// TRYB BEZPOŚREDNIEGO DOSTĘPU (PANEL UI ORAZ CALLBACKI HTTP)
// Ten blok wykonuje się TYLKO gdy otwierasz Gateway prosto z paska adresu przeglądarki,
// lub strzelasz do niego jako REST Proxy (np. z Home Assistant).
// ==========================================================================================
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    header('Access-Control-Allow-Origin: *'); 
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

    // 1. Wylogowanie
    if (isset($_GET['action']) && $_GET['action'] === 'revoke') {
        unset($_SESSION['google_tokens']);
        if (file_exists(__DIR__ . '/token.json')) @unlink(__DIR__ . '/token.json');
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); exit;
    }

    // 2. Odbiór kodu z Google OAuth
    if (isset($_GET['code'])) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'code' => $_GET['code'], 'client_id' => $client_id, 'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri, 'grant_type' => 'authorization_code'
        ]);
        $res = json_decode(curl_exec($ch), true);
        if (isset($res['access_token'])) {
            $_SESSION['google_tokens'] = $res;
            file_put_contents(__DIR__ . '/token.json', json_encode($res));
        }
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); exit;
    }

    // 3. Surowe Proxy dla oprogramowania trzeciego (np. Home Assistant)
    if (isset($_GET['action']) && $_GET['action'] === 'universal_proxy') {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Content-Type: application/json');
        $endpoint = $_GET['endpoint'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        $json_input = json_decode(file_get_contents('php://input'), true) ?: [];
        $payload = array_merge($_POST, $json_input);
        echo json_encode(google_api_call($endpoint, $method, empty($payload) ? null : $payload));
        exit;
    }

    // 4. GUI Dashboard
    // Zabezpieczenie hasłem
    $gateway_password = $env['GATEWAY_PASSWORD'] ?? 'Ksantypa1*';
    if (isset($_POST['gateway_pwd'])) {
        if ($_POST['gateway_pwd'] === $gateway_password) {
            $_SESSION['gateway_auth'] = true;
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
            exit;
        } else {
            $pwd_error = true;
        }
    }
    if (isset($_GET['action']) && $_GET['action'] === 'reset_pwd') {
        unset($_SESSION['gateway_auth']);
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
    if (empty($_SESSION['gateway_auth'])) {
        ?>
        <!DOCTYPE html>
        <html lang="pl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Gateway - Logowanie</title>
            <style>
                body { background: #0f2027; color: white; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
                .popup { background: rgba(20, 20, 30, 0.8); padding: 40px; border-radius: 16px; text-align: center; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
                input { padding: 12px; border-radius: 8px; border: 1px solid #4facfe; background: rgba(0,0,0,0.5); color: #fff; outline: none; margin-bottom: 15px; width: 100%; box-sizing: border-box; font-size: 16px; }
                button { padding: 12px 20px; background: #4facfe; color: #fff; border: none; border-radius: 8px; cursor: pointer; width: 100%; font-weight: bold; font-size: 16px; transition: 0.3s; }
                button:hover { background: #3a9bdc; }
            </style>
        </head>
        <body>
            <div class="popup">
                <h2 style="margin-top:0;">Wymagane Hasło</h2>
                <?php if (isset($pwd_error)) echo "<p style='color:#ff416c; margin-bottom: 15px;'>Nieprawidłowe hasło!</p>"; ?>
                <form method="POST">
                    <input type="password" name="gateway_pwd" placeholder="Podaj hasło do bramki..." required autofocus>
                    <button type="submit">Odblokuj</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    $auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
        'client_id' => $client_id, 'redirect_uri' => $redirect_uri, 'response_type' => 'code',
        'scope' => $scopes, 'access_type' => 'offline', 'prompt' => 'consent select_account'
    ]);

    $scope_array = array_filter(explode(" ", $scopes));
    $services_html = '';
    if (empty($scope_array)) {
        $services_html = "<span class='badge' style='border-color: var(--danger); color: var(--danger);'>Błąd: Brak zadeklarowanych scopes w .env!</span>";
    } else {
        foreach ($scope_array as $s) {
            $service_name = explode('/', $s);
            $service_name = end($service_name); 
            $services_html .= "<span class='badge'>" . htmlspecialchars($service_name) . "</span>";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="pl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Google API Gateway</title>
        <style>
            :root { --primary: #4facfe; --success: #00e676; --danger: #ff416c; }
            body { background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); min-height: 100vh; margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; color: white; display: flex; justify-content: center; align-items: center; }
            .dashboard { background: rgba(20, 20, 30, 0.5); backdrop-filter: blur(25px); border: 1px solid rgba(255,255,255,0.1); padding: 50px 40px; border-radius: 24px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); text-align: center; max-width: 500px; width: 90%; }
            .pulse-dot { width: 45px; height: 45px; border-radius: 50%; margin: 0 auto 30px auto; position: relative; }
            .pulse-dot.active { background: var(--success); box-shadow: 0 0 20px var(--success); animation: pulse-success 2s infinite; }
            .pulse-dot.inactive { background: var(--danger); box-shadow: 0 0 20px var(--danger); animation: pulse-danger 2s infinite; }
            @keyframes pulse-success { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 25px rgba(0, 230, 118, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(0, 230, 118, 0); } }
            @keyframes pulse-danger { 0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 65, 108, 0.7); } 70% { transform: scale(1); box-shadow: 0 0 0 25px rgba(255, 65, 108, 0); } 100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(255, 65, 108, 0); } }
            h1 { margin: 0 0 10px 0; font-weight: 300; letter-spacing: 2px; font-size: 1.8rem; }
            p { color: rgba(255,255,255,0.7); margin-bottom: 35px; line-height: 1.6; font-size: 15px; }
            .btn { background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.15); border-radius: 16px; color: white; text-decoration: none; padding: 14px 28px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 10px; transition: all 0.3s ease; cursor: pointer; font-size: 15px; width: 100%; box-sizing: border-box; box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
            .btn:hover { background: rgba(255, 255, 255, 0.15); transform: translateY(-2px); box-shadow: 0 12px 25px rgba(0,0,0,0.25); }
            .btn-primary { border-bottom: 3px solid rgba(79, 172, 254, 0.8); }
            .btn-primary:hover { border-color: var(--primary); }
            .btn-danger { border-bottom: 3px solid rgba(255, 65, 108, 0.8); }
            .btn-danger:hover { border-color: var(--danger); }
            .services { margin: 25px 0 35px 0; padding: 20px; background: rgba(0,0,0,0.2); border-radius: 16px; border: 1px solid rgba(255,255,255,0.05); }
            .badge { background: rgba(255,255,255,0.05); padding: 8px 14px; border-radius: 10px; font-size: 13px; margin: 5px; display: inline-block; border: 1px solid rgba(255,255,255,0.1); color: var(--primary); font-weight: 500; letter-spacing: 1px; }
        </style>
    </head>
    <body>
        <div class="dashboard">
            <?php if ($token_exists): ?>
                <div class="pulse-dot active"></div>
                <h1>Google API Gateway</h1>
                <p>System autoryzacji w pełni sprawny.</p>
                <div class="services">
                    <div style="font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 15px;">AKTYWNE UPRAWNIENIA:</div>
                    <?= $services_html ?>
                </div>
                <a href="?action=revoke" class="btn btn-danger">Odłącz konto (Usuń Token)</a>
                <a href="?action=reset_pwd" class="btn" style="margin-top: 15px; color: #ff9f43; border-bottom: 3px solid rgba(255, 159, 67, 0.8);">Zablokuj Gateway (Reset Hasła)</a>
            <?php else: ?>
                <div class="pulse-dot inactive"></div>
                <h1>Brak autoryzacji</h1>
                <p>Zaloguj się kontem Google, aby włączyć API.</p>
                <div class="services">
                    <div style="font-size: 12px; color: rgba(255,255,255,0.5); margin-bottom: 15px;">WYMAGANE DO:</div>
                    <?= $services_html ?>
                </div>
                <a href="<?= $auth_url ?>" class="btn btn-primary">Zaloguj przez Google</a>
            <?php endif; ?>
        </div>
    </body>
    </html>
    <?php
}
?>
