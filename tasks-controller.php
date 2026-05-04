<?php
/**
 * ==========================================================================================
 * TASKS DOMAIN CONTROLLER
 * ==========================================================================================
 * Tłumaczy żądania z Twojego frontendu (tasks.php) na zapytania do Google API.
 * W pełni wykorzystuje cichą moc pliku google-cloud-api-gateway.php.
 * ==========================================================================================
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

// Wczytujemy Gateway jako bibliotekę (udostępnia nam funkcję google_api_call!)
require_once __DIR__ . '/google-cloud-api-gateway.php';

$json_input = json_decode(file_get_contents('php://input'), true) ?:[];
$req_data = array_merge($_GET, $_POST, $json_input);

$action  = $req_data['action'] ?? '';
$list_id = $req_data['list_id'] ?? '@default';
$task_id = $req_data['task_id'] ?? '';

// Parsowanie Payloadu
$payload = [];
if (isset($req_data['title'])) $payload['title'] = $req_data['title'];
if (isset($req_data['notes'])) $payload['notes'] = $req_data['notes'];
if (array_key_exists('due', $req_data)) $payload['due'] = $req_data['due'];
if (isset($req_data['status'])) $payload['status'] = $req_data['status'];

switch($action) {
    case 'get_lists': echo json_encode(google_api_call("tasks/v1/users/@me/lists")); break;
    case 'add_list':  echo json_encode(google_api_call("tasks/v1/users/@me/lists", "POST", $payload)); break;
    case 'upd_list':  echo json_encode(google_api_call("tasks/v1/users/@me/lists/$list_id", "PATCH", $payload)); break;
    case 'del_list':  echo json_encode(google_api_call("tasks/v1/users/@me/lists/$list_id", "DELETE")); break;
    
    case 'get_tasks': echo json_encode(google_api_call("tasks/v1/lists/$list_id/tasks?showCompleted=true&showHidden=true")); break;
    
    case 'add_task':  
        $parent = $_GET['parent'] ?? null;
        $prev = $_GET['previous'] ?? null;
        if ($parent === '') $parent = null;
        $q = [];
        if ($parent) $q['parent'] = $parent;
        if ($prev) $q['previous'] = $prev;
        $qs = http_build_query($q);
        $url = "tasks/v1/lists/$list_id/tasks" . ($qs ? "?$qs" : "");
        echo json_encode(google_api_call($url, "POST", $payload)); 
        break;
        
    case 'upd_task':  echo json_encode(google_api_call("tasks/v1/lists/$list_id/tasks/$task_id", "PATCH", $payload)); break;
    
    case 'move_task': 
        $prev = $_GET['previous'] ?? null;
        $parent = $_GET['parent'] ?? null;
        if ($parent === '') $parent = null;
        $q = [];
        if ($prev) $q['previous'] = $prev;
        if ($parent) $q['parent'] = $parent;
        $qs = http_build_query($q);
        $url = "tasks/v1/lists/$list_id/tasks/$task_id/move" . ($qs ? "?$qs" : "");
        echo json_encode(google_api_call($url, "POST")); 
        break;
        
    case 'del_task':  echo json_encode(google_api_call("tasks/v1/lists/$list_id/tasks/$task_id", "DELETE")); break;
    
    default: echo json_encode(['error' => 'Nieznana akcja w kontrolerze zadań.']);
}