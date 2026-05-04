<?php
// BEZPIECZNY ODCZYT PLIKU .ENV DLA FRONTENDU
$env_path = __DIR__ . '/.env';
$api_gateway_filename = 'google-cloud-api-gateway.php';
$tasks_controller_filename = 'tasks-controller.php';

if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            
            if ($key === 'GOOGLE_REDIRECT_URI') $api_gateway_filename = basename($value);
            if ($key === 'TASKS_CONTROLLER_FILE') $tasks_controller_filename = $value;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Adi-Tasks PRO</title>
    <link rel="icon" href="icon.svg" type="image/svg+xml">
    <link rel="manifest" href="manifest.php">
    <meta name="theme-color" content="#1a2a6c">
    <link rel="apple-touch-icon" href="https://cdn-icons-png.flaticon.com/512/906/906334.png">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <style>
        :root { 
            --glass: rgba(25, 25, 35, 0.45); 
            --glass-hover: rgba(255, 255, 255, 0.08);
            --border: rgba(255, 255, 255, 0.15); 
            --success: #00e676; 
            --primary: #4facfe;
            --danger: #ff416c;
            --warning: #ff9f43;
        }
        
        ::-webkit-scrollbar { display: none; }
        * { scrollbar-width: none; box-sizing: border-box; }

        body { 
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #fdbb2d); 
            background-attachment: fixed;
            margin: 0; font-family: 'Segoe UI', system-ui, sans-serif; color: white;
            display: flex; justify-content: center; min-height: 100vh;
            -webkit-user-select: none; user-select: none; 
        }

        input, textarea, .task-notes { -webkit-user-select: auto; user-select: auto; }

        .app-container { width: 100%; max-width: 650px; display: flex; flex-direction: column; }

        /* ==================================================
           NAGŁÓWEK (Poprawione Glassmorphism)
           ================================================== */
        .top-header {
            position: sticky; top: 15px; z-index: 100;
            background: rgba(30, 30, 45, 0.55); 
            backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--border); border-radius: 20px;
            padding: 15px; margin: 15px 15px 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .header-row-top { display: flex; justify-content: space-between; align-items: center; gap: 15px; position: relative; z-index: 102; }

        .custom-select-container { width: 100%; position: relative; font-family: inherit; }
        .select-selected { 
            background: rgba(0,0,0,0.3); border: 1px solid var(--border); color: white; 
            padding: 12px 16px; border-radius: 12px; font-size: 15px; font-weight: 700; 
            cursor: pointer; display: flex; justify-content: space-between; align-items: center; 
            transition: 0.2s; box-shadow: inset 0 2px 10px rgba(0,0,0,0.2); 
        }
        .select-selected::after { content: '▼'; font-size: 0.7rem; color: var(--primary); transition: 0.2s; }
        .custom-select-container.open .select-selected::after { transform: rotate(180deg); }
        
        .select-items { 
            position: absolute; top: calc(100% + 5px); left: 0; right: 0; 
            background: rgba(30, 30, 45, 0.98); border: 1px solid var(--border); 
            border-radius: 12px; z-index: 9999; max-height: 250px; overflow-y: auto; 
            opacity: 0; visibility: hidden; transition: 0.2s; box-shadow: 0 15px 40px rgba(0,0,0,0.6); 
        }
        .custom-select-container.open .select-items { opacity: 1; visibility: visible; }
        .select-item { padding: 12px 14px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; font-weight: 500; }
        .select-item:hover { background: rgba(255,255,255,0.1); color: var(--primary); padding-left: 20px; }

        .menu-trigger { background: transparent; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; transition: 0.3s; padding: 0; }
        .menu-trigger svg { transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); stroke: #ff416c; opacity: 0.8; }
        .menu-trigger:hover svg { opacity: 1; transform: scale(1.1); }
        .menu-trigger.active svg { transform: rotate(90deg); stroke: var(--primary); opacity: 1; }

        .header-collapsible { max-height: 0; overflow: hidden; opacity: 0; transition: max-height 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275), opacity 0.3s ease, margin-top 0.3s ease; }
        .header-collapsible.expanded { max-height: 400px; opacity: 1; margin-top: 15px; }

        .buttons-row { display: flex; gap: 10px; margin-bottom: 10px; }
        .menu-btn { 
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; 
            padding: 12px 5px; color: white; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; 
            display: flex; align-items: center; justify-content: center; gap: 8px; white-space: nowrap; box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        .menu-btn:active { transform: scale(0.95); }
        .menu-btn:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); transform: translateY(-2px); }
        
        .menu-btn.primary { background: rgba(79, 172, 254, 0.1); border-color: rgba(79, 172, 254, 0.3); color: var(--primary); }
        .menu-btn.primary:hover { background: rgba(79, 172, 254, 0.2); border-color: var(--primary); }
        
        .menu-btn.danger { color: #ff6b81; }
        .menu-btn.danger:hover { background: rgba(255, 65, 108, 0.15); border-color: rgba(255, 65, 108, 0.4); color: white; }

        /* FLAGI Z NAPRAWĄ DLA WINDOWSA */
        .flags-container { display: flex; justify-content: center; gap: 15px; margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(255,255,255,0.15); }
        .flag-btn { background: none; border: none; cursor: pointer; transition: 0.2s; filter: grayscale(1); opacity: 0.4; padding: 0; display: flex; align-items: center; justify-content: center; }
        .flag-btn img { border-radius: 4px; width: 26px; height: 18px; object-fit: cover; box-shadow: 0 2px 5px rgba(0,0,0,0.3); }
        .flag-btn.active { filter: grayscale(0); opacity: 1; transform: scale(1.2); }

        /* KRĘCĄCA SIĘ IKONKA SYNC */
        .spin-icon { animation: spin-majestically 8s linear infinite; }
        .syncing .spin-icon { animation: spin-fast 0.6s linear infinite; }
        @keyframes spin-majestically { 100% { transform: rotate(360deg); } }
        @keyframes spin-fast { 100% { transform: rotate(360deg); } }

        /* ==================================================
           ZADANIA I STRUKTURA
           ================================================== */
        .content-area { padding: 0 15px 100px; }
        .task-group { margin-bottom: 12px; }
        
        .subtasks-container { margin-left: 20px; border-left: 2px solid rgba(255,255,255,0.1); padding-left: 6px; margin-top: 5px; min-height: 10px; padding-bottom: 5px;}
        .task-item { background: var(--glass); border-radius: 16px; display: flex; align-items: stretch; border: 1px solid rgba(255,255,255,0.08); transition: background 0.2s; overflow: hidden; }
        .task-item:hover { background: var(--glass-hover); }
        
        .task-item.subtask { background: rgba(0,0,0,0.25); border: none; border-radius: 12px; position: relative; margin-bottom: 0px; padding-left: 12px; }
        .task-item.subtask::before { content: '↳'; position: absolute; left: -14px; top: 12px; color: rgba(255,255,255,0.4); font-size: 14px; }
        
        .drag-handle { cursor: grab; color: rgba(255,255,255,0.2); font-size: 20px; padding: 0 12px 0 15px; display: flex; align-items: center; justify-content: center; }
        .sortable-ghost { opacity: 0.3; background: rgba(0,0,0,0.4); border: 2px dashed rgba(255,255,255,0.4); border-radius: 16px; }
        .sortable-fallback { opacity: 1 !important; background: rgba(30, 30, 45, 0.98) !important; box-shadow: 0 25px 50px rgba(0,0,0,0.7); transform: scale(1.03); z-index: 9999; }
        
        .task-main { flex-grow: 1; padding: 14px 15px 14px 5px; display: flex; flex-direction: column; cursor: pointer; min-width: 0; }
        .task-top-row { display: flex; align-items: center; gap: 12px; width: 100%; }
        
        input[type="checkbox"] { 
            appearance: none; -webkit-appearance: none;
            width: 18px; height: 18px; cursor: pointer; flex-shrink: 0; margin: 0;
            background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 6px;
            position: relative; transition: all 0.2s ease;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.3);
        }
        input[type="checkbox"]:hover { background: rgba(255, 255, 255, 0.15); border-color: rgba(255, 255, 255, 0.5); }
        input[type="checkbox"]:checked { 
            background: rgba(79, 172, 254, 0.4); border-color: var(--primary);
            box-shadow: 0 0 10px rgba(79, 172, 254, 0.3);
        }
        input[type="checkbox"]:checked::after {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><polyline points="20 6 9 17 4 12"></polyline></svg>');
            background-size: 70%; background-position: center; background-repeat: no-repeat;
        }
        
        .task-item.subtask input[type="checkbox"] { width: 16px; height: 16px; border-radius: 5px; }
        
        .task-title { font-size: 0.95em; line-height: 1.3; font-weight: 500; flex-grow: 1; word-break: break-word; overflow-wrap: anywhere; }
        
        /* Zabezpieczenie przed znikaniem ikon! */
        .task-actions { display: flex; align-items: center; flex-shrink: 0; gap: 4px; }
        .task-actions button { background: transparent; padding: 6px; border: none; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
        .task-actions button:hover { transform: scale(1.2); }
        
        .task-details { margin-left: 34px; margin-top: 6px; }
        .task-notes { font-size: 0.85em; color: rgba(255,255,255,0.7); white-space: pre-wrap; word-break: break-word; overflow-wrap: anywhere; line-height: 1.4; border-left: 2px solid rgba(255,255,255,0.2); padding-left: 10px; }
        
        .date-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.75em; background: rgba(0,0,0,0.5); padding: 5px 10px; border-radius: 8px; margin-top: 8px; border: 1px solid rgba(255,255,255,0.1); font-weight: 600; color: #bde0fe; }
        .task-group.completed .task-item { opacity: 0.5; }
        .task-group.completed .task-title { text-decoration: line-through; color: rgba(255,255,255,0.5); }

        .date-badge.today { background: rgba(0, 230, 118, 0.15); border-color: rgba(0, 230, 118, 0.4); color: var(--success); }
        .date-badge.tomorrow { background: rgba(79, 172, 254, 0.15); border-color: rgba(79, 172, 254, 0.4); color: var(--primary); }
        .date-badge.overdue { background: rgba(255, 65, 108, 0.15); border-color: rgba(255, 65, 108, 0.4); color: var(--danger); }

        .toggle-completed { display: inline-flex; align-items: center; justify-content: center; gap: 8px; margin: 30px auto 20px; cursor: pointer; opacity: 0.6; transition: 0.2s; font-size: 0.85em; letter-spacing: 3px; font-weight: bold; text-transform: uppercase; width: 100%; -webkit-tap-highlight-color: transparent; }
        .toggle-completed:hover { opacity: 1; color: var(--primary); text-shadow: 0 0 10px rgba(79, 172, 254, 0.5); }

        /* TOASTERY */
        #toast-container { position: fixed; z-index: 9999; display: flex; flex-direction: column; gap: 12px; pointer-events: none; width: 100%; max-width: 450px; }
        @media (min-width: 768px) { #toast-container { top: 25px; right: 25px; align-items: flex-end; } }
        @media (max-width: 767px) { #toast-container { bottom: 30px; left: 50%; transform: translateX(-50%); padding: 0 15px; align-items: center; } }
        .toast { background: rgba(20, 20, 30, 0.95); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; color: white; padding: 16px 20px; display: flex; align-items: flex-start; gap: 14px; font-weight: 500; font-size: 15px; box-shadow: 0 10px 40px rgba(0,0,0,0.6); opacity: 0; transition: 0.4s; width: 100%; }
        .toast.success { border-left: 5px solid var(--success); }
        .toast.info { border-left: 5px solid var(--primary); }
        .toast.danger { border-left: 5px solid var(--danger); }
        @media (min-width: 768px) { .toast { transform: translateX(50px); } .toast.show { transform: translateX(0); opacity: 1; } }
        @media (max-width: 767px) { .toast { transform: translateY(30px); } .toast.show { transform: translateY(0); opacity: 1; } }

        /* MODALE */
        .modal { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width: 92%; max-width: 450px; z-index: 10000; background: rgba(20, 20, 30, 0.55); backdrop-filter: blur(25px); border: 1px solid rgba(255,255,255,0.15); border-radius: 24px; padding: 30px; box-shadow: 0 30px 60px rgba(0,0,0,0.7); }
        .overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; backdrop-filter: blur(5px); }
        
        .input-group { position: relative; margin-bottom: 15px; }
        .mic-btn { 
            position: absolute; right: 8px; top: 8px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 8px; width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; 
            cursor: pointer; opacity: 0.7; transition: 0.2s; padding: 0;
        }
        .mic-btn:hover { opacity: 1; background: rgba(255,255,255,0.15); transform: scale(1.1); }
        .mic-btn.recording { background: rgba(255, 65, 108, 0.2); border-color: rgba(255, 65, 108, 0.5); animation: pulse-danger 1.5s infinite; opacity: 1; }
        .mic-btn.recording svg { stroke: var(--danger); }
        @keyframes pulse-danger { 0% { box-shadow: 0 0 0 0 rgba(255, 65, 108, 0.5); } 70% { box-shadow: 0 0 0 10px rgba(255, 65, 108, 0); } 100% { box-shadow: 0 0 0 0 rgba(255, 65, 108, 0); } }

        /* Piękne, wyodrębnione panele do wpisywania */
        .modal input.standard, .modal textarea.standard, .modal input.borderless, .modal textarea.borderless { 
            width: 100%; background: rgba(0,0,0,0.25); border: 1px solid rgba(255,255,255,0.08); color: white; 
            padding: 14px; padding-right: 48px; border-radius: 12px; outline: none; font-family: inherit; transition: 0.2s; box-shadow: inset 0 2px 10px rgba(0,0,0,0.1);
        }
        .modal input.standard:focus, .modal textarea.standard:focus, .modal input.borderless:focus, .modal textarea.borderless:focus { 
            background: rgba(0,0,0,0.4); border-color: var(--primary); box-shadow: inset 0 2px 10px rgba(0,0,0,0.2), 0 0 10px rgba(79, 172, 254, 0.2);
        }
        .modal input.borderless.title { font-size: 1.2rem; font-weight: 700; margin-bottom: 5px; }
        .modal textarea.borderless.notes { font-size: 1rem; color: rgba(255,255,255,0.9); min-height: 90px; resize: none; }
        
        .subtasks-separator { border: 0; height: 1px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent); margin: 25px 0 15px; }
        .custom-date-input::-webkit-calendar-picker-indicator { background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 24 24" fill="none" stroke="%234facfe" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>'); cursor: pointer; opacity: 0.9; }
        
        .modal-buttons { display: flex; gap: 12px; margin-top: 15px; }
        .modal-buttons button { flex: 1; padding: 14px; border-radius: 14px; border: 1px solid rgba(255,255,255,0.1); font-weight: bold; cursor: pointer; color: white; display: flex; align-items: center; justify-content: center; gap: 8px; background: rgba(255,255,255,0.08); }
        .modal-subtasks-list { margin-top: 10px; margin-bottom: 20px; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); }
        .modal-subtask-item { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 8px; font-size: 0.9rem; }
        .modal-subtask-item.done span { text-decoration: line-through; opacity: 0.5; }
    </style>
</head>
<body>
<div id="toast-container"></div>

<div class="app-container">

    <!-- ================================================== -->
    <!-- NAGŁÓWEK KASKADOWY Z FLAGAMI (GRAFIKI CDN)         -->
    <!-- ================================================== -->
    <div class="top-header" id="mainHeader">
        <div class="header-row-top">
            <div class="custom-select-container" id="listSelectContainer">
                <div class="select-selected" id="listSelectSelected" onclick="toggleCustomSelect(event)">Ładowanie...</div>
                <div class="select-items" id="listSelectItems"></div>
            </div>
            
            <button class="menu-trigger" id="mainMenuBtn" onclick="toggleMegaMenu()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="5" cy="12" r="1"></circle><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle>
                </svg>
            </button>
        </div>

        <div class="header-collapsible" id="megaMenu">
            <div class="buttons-row">
                <button class="menu-btn primary" style="flex: 1.5;" onclick="openAddTaskModal()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg> 
                    <span data-i18n="add_task">Dodaj zadanie</span>
                </button>
                <button class="menu-btn" style="flex: 1;" onclick="forceRefresh()" id="syncBtn">
                    <svg class="spin-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                    </svg>
                    <span>Sync</span>
                </button>
            </div>
            
            <div class="buttons-row">
                <button class="menu-btn" style="flex: 1;" onclick="showModal('listModal')"><span data-i18n="new_list">Nowa lista</span></button>
                <button class="menu-btn" style="flex: 1;" onclick="openEditListModal()"><span data-i18n="list_name">Nazwa listy</span></button>
                <button class="menu-btn danger" style="flex: 1;" onclick="confirmDeleteList()"><span data-i18n="del_list">Usuń listę</span></button>
            </div>
            
            <div class="flags-container" style="flex-wrap: wrap;">
                <button class="flag-btn" onclick="setLang('pl')" id="flag-pl"><img src="https://flagcdn.com/w40/pl.png" alt="PL"></button>
                <button class="flag-btn" onclick="setLang('en')" id="flag-en"><img src="https://flagcdn.com/w40/us.png" alt="US"></button>
                <button class="flag-btn" onclick="setLang('de')" id="flag-de"><img src="https://flagcdn.com/w40/de.png" alt="DE"></button>
                <button class="flag-btn" onclick="setLang('es')" id="flag-es"><img src="https://flagcdn.com/w40/es.png" alt="ES"></button>
                <button class="flag-btn" onclick="setLang('fr')" id="flag-fr"><img src="https://flagcdn.com/w40/fr.png" alt="FR"></button>
                <button class="flag-btn" onclick="setLang('zh')" id="flag-zh"><img src="https://flagcdn.com/w40/cn.png" alt="CN"></button>
                <button class="flag-btn" onclick="setLang('ua')" id="flag-ua"><img src="https://flagcdn.com/w40/ua.png" alt="UA"></button>
            </div>
        </div>
    </div>

    <!-- ZADANIA -->
    <div class="content-area">
        <div id="activeTasks" class="sortable-container"></div>
        <div id="completedSection" style="display: none;">
            <div class="toggle-completed" onclick="toggleCompletedTasks()">
                <span data-i18n="completed">WYKONANE</span>
                <svg id="completedIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" style="transition: 0.3s; transform: rotate(-90deg);"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
            <div id="completedTasks" style="display: none;"></div>
        </div>
    </div>
</div>

<!-- MODALE -->
<div id="editOverlay" class="overlay" onclick="closeModals()"></div>

<div id="addTaskModal" class="modal">
    <h3 id="addModalTitle" data-i18n="add_task">Utwórz zadanie</h3>
    <input type="hidden" id="ntParentId">
    <div class="input-group">
        <input type="text" id="ntTitle" class="standard" placeholder="Tytuł...">
        <button class="mic-btn" onclick="startDictation('ntTitle', this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>
        </button>
    </div>
    <div class="input-group">
        <textarea id="ntNotes" class="standard" placeholder="Szczegóły..." style="height: 80px; resize: none;"></textarea>
        <button class="mic-btn" onclick="startDictation('ntNotes', this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>
        </button>
    </div>
    <input type="text" id="ntDate" class="standard custom-date-input" placeholder="Termin..." onfocus="this.type='date'" onblur="if(!this.value) this.type='text'">
    <div class="modal-buttons">
        <button onclick="closeModals()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            <span data-i18n="cancel">Anuluj</span>
        </button>
        <button onclick="addTask()" style="background: rgba(0, 230, 118, 0.15); border-color: rgba(0, 230, 118, 0.3); color: var(--success);">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            <span data-i18n="save">Zapisz</span>
        </button>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="input-group">
        <input type="text" id="edTitle" class="borderless title" placeholder="Brak tytułu...">
        <button class="mic-btn" onclick="startDictation('edTitle', this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>
        </button>
    </div>
    <div class="input-group">
        <textarea id="edNotes" class="borderless notes" placeholder="Brak opisu..."></textarea>
        <button class="mic-btn" onclick="startDictation('edNotes', this)">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>
        </button>
    </div>
    <input type="text" id="edDate" class="borderless custom-date-input" style="color: var(--primary);" placeholder="📅 Termin" onfocus="this.type='date'" onblur="if(!this.value) this.type='text'">
    
    <div id="modalSubtasksContainer" style="display:none;">
        <hr class="subtasks-separator">
        <div style="font-size: 0.8rem; font-weight: bold; color: var(--primary); text-transform: uppercase; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            Podzadania
        </div>
        <div id="modalSubtasksList" class="modal-subtasks-list"></div>
    </div>

    <div class="modal-buttons" style="margin-top: 25px;">
        <button onclick="confirmDelTask(editingTaskId)" style="flex: 0.4; background: rgba(255, 65, 108, 0.15); border-color: rgba(255, 65, 108, 0.3); color: var(--danger);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </button>
        <button onclick="saveEdit()" style="background: rgba(0, 230, 118, 0.15); border-color: rgba(0, 230, 118, 0.3); color: var(--success);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
            <span data-i18n="save">Zapisz Zmiany</span>
        </button>
    </div>
</div>

<div id="listModal" class="modal">
    <h3 data-i18n="new_list">Nowa lista</h3>
    <input type="text" id="nlTitle" class="standard">
    <div class="modal-buttons">
        <button onclick="closeModals()"><span data-i18n="cancel">Anuluj</span></button>
        <button onclick="saveList()"><span data-i18n="save">Zapisz</span></button>
    </div>
</div>

<div id="editListModal" class="modal">
    <h3 data-i18n="list_name">Nazwa listy</h3>
    <input type="text" id="elTitle" class="standard">
    <div class="modal-buttons">
        <button onclick="closeModals()"><span data-i18n="cancel">Anuluj</span></button>
        <button onclick="saveEditList()"><span data-i18n="save">Zapisz</span></button>
    </div>
</div>

<div id="confirmModal" class="modal">
    <h3 id="confirmTitle" style="color: var(--danger);">Potwierdzenie</h3>
    <p id="confirmMessage" style="margin-bottom: 20px; color: rgba(255,255,255,0.8);"></p>
    <div class="modal-buttons">
        <button onclick="closeModals()"><span data-i18n="cancel">Anuluj</span></button>
        <button id="confirmActionBtn" style="color: var(--danger);"><span data-i18n="delete">Usuń</span></button>
    </div>
</div>

<div id="installModal" class="modal" style="text-align: center;">
    <div style="margin: 0 auto 20px; width: 70px; height: 70px; background: rgba(79, 172, 254, 0.15); border-radius: 20px; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(79, 172, 254, 0.3);">
        <img src="icon.svg" width="45" height="45" alt="Ikona">
    </div>
    <h3>Zainstaluj Adi-Tasks PRO</h3>
    <p style="color: rgba(255,255,255,0.7); font-size: 14px; margin-bottom: 25px;">Zainstaluj aplikację na ekranie, aby działała na pełnym ekranie i ładowała się błyskawicznie!</p>
    <div class="modal-buttons">
        <button onclick="closeModals()">Może później</button>
        <button onclick="installPWA()" style="background: rgba(0, 230, 118, 0.2); border-color: rgba(0, 230, 118, 0.5); color: var(--success);">Zainstaluj</button>
    </div>
</div>

<!-- ==========================================================
     SKRYPTY JAVASCRIPT
     ========================================================== -->
<script>
    // 🔥 WSTRZYKIWANIE ZMIENNYCH Z PHP 🔥
    const API = '<?= htmlspecialchars($tasks_controller_filename) ?>'; 
    const GATEWAY_URL = '<?= htmlspecialchars($api_gateway_filename) ?>';
    
    // ==========================================================
    // i18n & DICTIONARY
    // ==========================================================
    // ==========================================================
    // i18n & DICTIONARY (GLOBAL)
    // ==========================================================
    const dict = {
        pl: { add_task: 'Dodaj zadanie', new_list: 'Nowa lista', list_name: 'Nazwa listy', del_list: 'Usuń listę', completed: 'WYKONANE', cancel: 'Anuluj', save: 'Zapisz', delete: 'Usuń', t_err: 'Błąd!', t_ok: 'Sukces!', s_sync: 'Wykryto zmiany! Aktualizuję...', msg_del: 'Usunięto!', msg_saved: 'Zapisano!', s_del_list: 'Skasować listę i zadania?', no_mic: 'Brak obsługi mikrofonu.', syncing: 'Synchronizacja...', sending: 'Wysyłam...', deleting: 'Kasowanie...', saving: 'Zapisywanie...' },
        en: { add_task: 'Add Task', new_list: 'New List', list_name: 'List Name', del_list: 'Delete List', completed: 'COMPLETED', cancel: 'Cancel', save: 'Save', delete: 'Delete', t_err: 'Error!', t_ok: 'Success!', s_sync: 'Changes detected! Syncing...', msg_del: 'Deleted!', msg_saved: 'Saved!', s_del_list: 'Delete list and all tasks?', no_mic: 'Mic not supported.', syncing: 'Syncing...', sending: 'Sending...', deleting: 'Deleting...', saving: 'Saving...' },
        de: { add_task: 'Aufgabe', new_list: 'Neue Liste', list_name: 'Listenname', del_list: 'Liste löschen', completed: 'ERLEDIGT', cancel: 'Abbrechen', save: 'Speichern', delete: 'Löschen', t_err: 'Fehler!', t_ok: 'Erfolg!', s_sync: 'Änderungen! Aktualisiere...', msg_del: 'Gelöscht!', msg_saved: 'Gespeichert!', s_del_list: 'Liste löschen?', no_mic: 'Kein Mikrofon.', syncing: 'Synchronisieren...', sending: 'Senden...', deleting: 'Löschen...', saving: 'Speichern...' },
        es: { add_task: 'Añadir tarea', new_list: 'Nueva lista', list_name: 'Nombre', del_list: 'Borrar lista', completed: 'COMPLETADO', cancel: 'Cancelar', save: 'Guardar', delete: 'Borrar', t_err: '¡Error!', t_ok: '¡Éxito!', s_sync: '¡Cambios detectados! Sincronizando...', msg_del: '¡Eliminado!', msg_saved: '¡Guardado!', s_del_list: '¿Borrar lista y tareas?', no_mic: 'Micrófono no soportado.', syncing: 'Sincronizando...', sending: 'Enviando...', deleting: 'Eliminando...', saving: 'Guardando...' },
        fr: { add_task: 'Ajouter', new_list: 'Nouvelle liste', list_name: 'Nom de la liste', del_list: 'Supprimer la liste', completed: 'TERMINÉ', cancel: 'Annuler', save: 'Enregistrer', delete: 'Supprimer', t_err: 'Erreur!', t_ok: 'Succès!', s_sync: 'Changements détectés! Synchronisation...', msg_del: 'Supprimé!', msg_saved: 'Enregistré!', s_del_list: 'Supprimer la liste et les tâches?', no_mic: 'Micro non supporté.', syncing: 'Synchronisation...', sending: 'Envoi...', deleting: 'Suppression...', saving: 'Enregistrement...' },
        zh: { add_task: '添加任务', new_list: '新列表', list_name: '列表名称', del_list: '删除列表', completed: '已完成', cancel: '取消', save: '保存', delete: '删除', t_err: '错误！', t_ok: '成功！', s_sync: '检测到更改！同步中...', msg_del: '已删除！', msg_saved: '已保存！', s_del_list: '删除列表和所有任务？', no_mic: '不支持麦克风。', syncing: '同步中...', sending: '发送中...', deleting: '删除中...', saving: '保存中...' },
        ua: { add_task: 'Dodaty zavdannya', new_list: 'Novyy spysok', list_name: 'Nazva spysku', del_list: 'Vydalyty spysok', completed: 'VYKONANO', cancel: 'Skasuvaty', save: 'Zberehty', delete: 'Vydalyty', t_err: 'Pomylka!', t_ok: 'Uspikh!', s_sync: 'Zminy! Onovlyuyu...', msg_del: 'Vydaleno!', msg_saved: 'Zberezheno!', s_del_list: 'Vydalyty spysok?', no_mic: 'Mikrofon ne pidtrymuyetsya.', syncing: 'Synkhronizatsiya...', sending: 'Nadsylannya...', deleting: 'Vydalennya...', saving: 'Zberezhennya...' }
    };
    
    let currentLang = localStorage.getItem('appLang') || navigator.language.substring(0,2);
    if(!dict[currentLang]) currentLang = 'en';

    function setLang(lang) {
        currentLang = lang; 
        localStorage.setItem('appLang', lang);
        document.querySelectorAll('.flag-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('flag-'+lang).classList.add('active');
        document.querySelectorAll('[data-i18n]').forEach(el => { 
            el.innerText = dict[currentLang][el.dataset.i18n] || el.dataset.i18n; 
        });
        vibe('light');
    }
    setLang(currentLang);

    // Dynamiczne mapowanie kodów języków dla TTS i STT
    const langCodes = { pl: 'pl-PL', en: 'en-US', de: 'de-DE', es: 'es-ES', fr: 'fr-FR', zh: 'zh-CN', ua: 'uk-UA' };

    function vibe(type) {
        if(!navigator.vibrate) return;
        if(type === 'light') navigator.vibrate(15);
        else if(type === 'heavy') navigator.vibrate([15, 50, 15]);
        else if(type === 'success') navigator.vibrate(50);
    }

    function speakText(text) {
        if(!window.speechSynthesis) return;
        window.speechSynthesis.cancel(); 
        const ut = new SpeechSynthesisUtterance(text);
        ut.lang = langCodes[currentLang] || 'en-US';
        window.speechSynthesis.speak(ut);
    }

    function showToast(message, type = 'info') {
        const container = document.getElementById('toast-container'); 
        const toast = document.createElement('div'); 
        toast.className = `toast ${type}`;
        
        let iconSvg = '';
        if(type === 'success') { iconSvg = `🟢`; speakText(message); } 
        else if(type === 'danger') { iconSvg = `🔴`; speakText(message); } 
        else { iconSvg = `🔵`; }

        toast.innerHTML = `<span style="font-size:20px">${iconSvg}</span> <div class="toast-content">${message}</div>`; 
        container.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 400); }, 3000);
    }

    function startDictation(targetId, btnEl) {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if(!SpeechRecognition) { showToast(dict[currentLang].no_mic, 'danger'); return; }
        
        btnEl.classList.add('recording'); 
        vibe('light');
        const rec = new SpeechRecognition();
        rec.lang = langCodes[currentLang] || 'en-US';
        
        rec.onresult = function(e) { 
            document.getElementById(targetId).value += (document.getElementById(targetId).value ? ' ' : '') + e.results[0][0].transcript; 
            vibe('success'); 
        };
        rec.onend = function() { btnEl.classList.remove('recording'); };
        rec.start();
    }

    function toggleMegaMenu() {
        vibe('light');
        const menu = document.getElementById('megaMenu');
        const btn = document.getElementById('mainMenuBtn');
        if (menu.classList.contains('expanded')) {
            menu.classList.remove('expanded');
            btn.classList.remove('active');
        } else {
            menu.classList.add('expanded');
            btn.classList.add('active');
        }
    }
    
    document.addEventListener('click', e => {
        const h = document.getElementById('mainHeader');
        if(!h.contains(e.target)) {
            document.getElementById('megaMenu').classList.remove('expanded');
            document.getElementById('mainMenuBtn').classList.remove('active');
            document.getElementById('listSelectContainer').classList.remove('open');
        }
    });

    function toggleCustomSelect(e) { 
        e.stopPropagation(); 
        document.getElementById('listSelectContainer').classList.toggle('open'); 
    }

    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault(); 
        deferredPrompt = e;
        if (!sessionStorage.getItem('installPromptShown')) {
            setTimeout(() => showModal('installModal'), 2000); 
            sessionStorage.setItem('installPromptShown', 'true');
        }
    });

    function installPWA() {
        closeModals();
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(() => deferredPrompt = null);
        }
    }

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('sw.php'));
    }

    let currentListId = '@default'; 
    let editingTaskId = null; 
    let allTasksData = new Map(); 

    window.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        if(params.has('share_title') || params.has('share_text') || params.has('share_url')) {
            setTimeout(() => {
                document.getElementById('ntTitle').value = params.get('share_title') || '';
                document.getElementById('ntNotes').value = ((params.get('share_text')||'') + " " + (params.get('share_url')||'')).trim();
                openAddTaskModal();
            }, 1000);
        }
    });

    async function req(url, method='GET', body=null) {
        try {
            const finalUrl = `${url}${url.includes('?') ? '&' : '?'}_t=${Date.now()}`;
            const opt = { method, cache: 'no-store' }; 
            if(body) { opt.headers = { 'Content-Type': 'application/json' }; opt.body = JSON.stringify(body); }
            const r = await fetch(finalUrl, opt); 
            const t = await r.text(); 
            if(!t) return { error: 'Empty' };
            
            const d = JSON.parse(t); 
            if(d.error === 'auth_required') { window.location.href = GATEWAY_URL; return; }
            return d;
        } catch(e) { return { error: 'Net err' }; }
    }

    function escapeHTML(s) { 
        return s.replace(/[&<>'"]/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[t] || t)); 
    }
    
    function showModal(id) { 
        vibe('light'); document.getElementById('editOverlay').style.display = 'block'; document.getElementById(id).style.display = 'block'; 
    }
    
    function closeModals() { 
        document.getElementById('editOverlay').style.display = 'none'; document.querySelectorAll('.modal').forEach(m => m.style.display = 'none'); 
    }

    function selectListOption(id, title) { 
        currentListId = id; document.getElementById('listSelectSelected').innerText = title; 
        document.getElementById('listSelectContainer').classList.remove('open'); 
        loadTasks(); toggleMegaMenu(); 
    }
    
    function forceRefresh() {
        vibe('light'); 
        const btn = document.getElementById('syncBtn'); 
        btn.classList.add('syncing');
        showToast(dict[currentLang].syncing, 'info'); // 🔵 DODANE INFO: "Synchronizacja..."
        
        loadTasks().then(() => {
            setTimeout(() => btn.classList.remove('syncing'), 500);
            showToast(dict[currentLang].t_ok, 'success'); // 🟢 DODANE POTWIERDZENIE: "Sukces!"
        });
    }

    async function loadLists() {
        const itemsDiv = document.getElementById('listSelectItems'); const selectedDiv = document.getElementById('listSelectSelected');
        
        const d = await req(`${API}?action=get_lists`);
        if(!d || d.error) { selectedDiv.innerText = 'Błąd!'; return; }
        
        itemsDiv.innerHTML = d.items.map(l => `<div class="select-item" onclick="selectListOption('${l.id}', '${escapeHTML(l.title)}')">${escapeHTML(l.title)}</div>`).join('');
        if(currentListId === '@default' && d.items.length > 0) { currentListId = d.items[0].id; selectedDiv.innerText = d.items[0].title; }
        loadTasks();
    }

    function initSortables() {
        document.querySelectorAll('.sortable-container, .subtasks-container').forEach(el => {
            if(el.sortable) el.sortable.destroy(); 
            el.sortable = Sortable.create(el, {
                group: 'tasksGroup', animation: 250, handle: '.drag-handle', fallbackOnBody: true, swapThreshold: 0.65, ghostClass: 'sortable-ghost', forceFallback: true, fallbackClass: 'sortable-fallback',
                onStart: () => vibe('light'),
                onMove: function (evt) {
                    const hasSub = evt.dragged.querySelector('.subtasks-container') && evt.dragged.querySelector('.subtasks-container').children.length > 0;
                    if (hasSub && evt.to.classList.contains('subtasks-container')) return false;
                    if (evt.to === evt.dragged.querySelector('.subtasks-container')) return false; 
                },
                onEnd: async (evt) => {
                    vibe('heavy');
                    if (evt.from === evt.to && evt.oldIndex === evt.newIndex) return; 
                    
                    const tid = evt.item.dataset.id; 
                    const prevNode = evt.item.previousElementSibling; 
                    const prevId = prevNode ? prevNode.dataset.id : '';
                    let pId = ''; 
                    
                    if (evt.to.classList.contains('subtasks-container')) {
                        pId = evt.to.dataset.parentId; 
                    }
                    
                    showToast(dict[currentLang].saving, 'info');
                    const res = await req(`${API}?action=move_task&list_id=${currentListId}&task_id=${tid}&previous=${prevId}&parent=${pId}`, 'POST');
                    
                    if(res && !res.error) { 
                        showToast(dict[currentLang].msg_saved, 'success'); // 🟢 DODANY TOASTR SUKCESU
                        loadTasks(true); 
                    } else { 
                        showToast(dict[currentLang].t_err, 'danger'); 
                        loadTasks(true); 
                    }
                }
            });
        });
    }

    let lastServerHash = '';
    function genHash(items) { return JSON.stringify(items.map(t => ({ id: t.id, up: t.updated, p: t.parent||'', pos: t.position }))); }

function getFormattedDateHTML(dueStr) {
        if (!dueStr) return '';
        const due = new Date(dueStr); const now = new Date(); now.setHours(0,0,0,0);
        const dueMidnight = new Date(due); dueMidnight.setHours(0,0,0,0);
        const diffTime = dueMidnight.getTime() - now.getTime(); 
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        const isOverdue = diffDays < 0;

        let dateText = due.toLocaleDateString('pl-PL', { weekday: 'short', day: 'numeric', month: 'short' }); 
        let prefix = 'Termin: '; let colorClass = isOverdue ? 'overdue' : '';

        if (diffDays === 0) { dateText = 'Dzisiaj'; prefix = ''; colorClass = 'today'; }
        else if (diffDays === 1) { dateText = 'Jutro'; prefix = ''; colorClass = 'tomorrow'; }
        else if (diffDays === -1) { dateText = 'Wczoraj'; prefix = ''; colorClass = 'overdue'; }
        else if (diffDays < -14) { dateText = Math.floor(Math.abs(diffDays)/7) + ' tyg. temu'; prefix = ''; }
        else if (diffDays < -1) { dateText = Math.abs(diffDays) + ' dni temu'; prefix = ''; }

        // DODANA POPRAWKA: Sztywne 14x14px dla SVG, flex-shrink: 0, oraz white-space: nowrap dla tekstu!
        const svgIcon = `<svg width="14" height="14" style="flex-shrink: 0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>`;
        return `<div class="date-badge ${colorClass}" style="white-space: nowrap; width: fit-content; max-width: 100%; overflow: hidden; text-overflow: ellipsis;">${svgIcon} <span>${prefix}${dateText}</span></div>`;
    }

    async function loadTasks(silent = false) {
        const activeContainer = document.getElementById('activeTasks'); const doneContainer = document.getElementById('completedTasks');
        
        if(!silent) { activeContainer.innerHTML = `<div style="opacity:0.5; text-align:center; padding: 20px;">${dict[currentLang].syncing}</div>`; }

        const d = await req(`${API}?action=get_tasks&list_id=${currentListId}`);
        if(!d || d.error || !d.items) { activeContainer.innerHTML = ''; doneContainer.innerHTML = ''; return; }

        lastServerHash = genHash(d.items);
        allTasksData.clear(); 
        d.items.forEach(t => { t.subtasks =[]; allTasksData.set(t.id, t); });

        const topTasks =[];
        d.items.forEach(t => { 
            if (t.parent && allTasksData.has(t.parent)) { allTasksData.get(t.parent).subtasks.push(t); } else { topTasks.push(t); }
        });

        const sByPos = (a, b) => (a.position || "").localeCompare(b.position || "");
        topTasks.sort(sByPos); allTasksData.forEach(t => t.subtasks.sort(sByPos));

        let aHTML = ''; let dHTML = '';
        topTasks.forEach(t => { const h = renderTaskBlock(t); if(t.status === 'completed') dHTML += h; else aHTML += h; });
        
        activeContainer.innerHTML = aHTML; doneContainer.innerHTML = dHTML;
        
        const cSec = document.getElementById('completedSection');
        if(dHTML.trim() !== '') { cSec.style.display = 'block'; } else { cSec.style.display = 'none'; }

        initSortables(); 
    }

    function renderTaskBlock(t, isSub = false) {
        const isDone = t.status === 'completed';
        const notesHTML = t.notes ? `<div class="task-notes" style="margin-top:5px;">${escapeHTML(t.notes)}</div>` : '';
        const dueHTML = getFormattedDateHTML(t.due);
        
        let subH = ''; 
        if (!isSub) { subH = `<div class="subtasks-container sortable-list" data-parent-id="${t.id}">${(t.subtasks||[]).map(st => renderTaskBlock(st, true)).join('')}</div>`; }

        const addSubtaskBtn = !isSub ? `
            <button onclick="event.stopPropagation(); openAddSubtask('${t.id}')" title="Dodaj podzadanie">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 10 20 15 15 20"></polyline><path d="M4 4v7a4 4 0 0 0 4 4h12"></path></svg>
            </button>
        ` : '';

        return `
            <div class="task-group ${isDone ? 'completed' : ''}" data-id="${t.id}">
                <div class="task-item ${isSub ? 'subtask' : ''}">
                    <div class="drag-handle">⋮⋮</div>
                    <div class="task-main" onclick="openEdit('${t.id}')">
                        <div class="task-top-row">
                            <input type="checkbox" ${isDone ? 'checked' : ''} onclick="event.stopPropagation(); toggleTask('${t.id}', this.checked, this)">
                            <div class="task-title">${escapeHTML(t.title)}</div>
                            <div class="task-actions">
                                ${addSubtaskBtn}
                                <button onclick="event.stopPropagation(); confirmDelTask('${t.id}')" title="Usuń zadanie">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </div>
                        </div>
                        ${notesHTML || dueHTML ? `<div class="task-details">${notesHTML}${dueHTML}</div>` : ''}
                    </div>
                </div>
                ${subH}
            </div>`;
    }

    let isCompletedVisible = false;
    function toggleCompletedTasks() {
        vibe('light'); isCompletedVisible = !isCompletedVisible;
        document.getElementById('completedTasks').style.display = isCompletedVisible ? 'block' : 'none';
        document.getElementById('completedIcon').style.transform = isCompletedVisible ? 'rotate(0deg)' : 'rotate(-90deg)';
    }

    async function toggleTask(id, checked, cb) {
        vibe('success');
        const grp = cb.closest('.task-group'); const modalSubtask = cb.closest('.modal-subtask-item');
        if(grp) { if(checked) grp.classList.add('completed'); else grp.classList.remove('completed'); }
        if(modalSubtask) { if(checked) modalSubtask.classList.add('done'); else modalSubtask.classList.remove('done'); }
        
        showToast(dict[currentLang].saving, 'info');
        const res = await req(`${API}?action=upd_task&list_id=${currentListId}&task_id=${id}`, 'POST', {status: checked ? 'completed' : 'needsAction'});
        if(res && !res.error) { showToast(dict[currentLang].msg_saved, 'success'); loadTasks(true); } else { showToast(dict[currentLang].t_err, 'danger'); loadTasks(true); }
    }

    function openAddTaskModal() { document.getElementById('ntParentId').value = ''; showModal('addTaskModal'); }
    function openAddSubtask(parentId) { document.getElementById('ntParentId').value = parentId; showModal('addTaskModal'); }

    async function addTask() {
        const title = document.getElementById('ntTitle').value; const notes = document.getElementById('ntNotes').value; const due = document.getElementById('ntDate').value; const parentId = document.getElementById('ntParentId').value;
        if(!title) return; closeModals(); showToast(dict[currentLang].sending, 'info');
        const body = { title, notes }; if(due) body.due = new Date(due).toISOString();
        const res = await req(`${API}?action=add_task&list_id=${currentListId}` + (parentId ? `&parent=${parentId}` : ''), 'POST', body);
        if(res && !res.error) { document.getElementById('ntTitle').value = ''; document.getElementById('ntNotes').value = ''; document.getElementById('ntDate').type = 'text'; document.getElementById('ntDate').value = ''; showToast(dict[currentLang].msg_saved, 'success'); loadTasks(true); } else { showToast(dict[currentLang].t_err, 'danger'); }
    }

    function openEdit(id) {
        const t = allTasksData.get(id); if(!t) return; editingTaskId = id;
        document.getElementById('edTitle').value = t.title; document.getElementById('edNotes').value = t.notes || ''; 
        const edDateInput = document.getElementById('edDate'); edDateInput.type = t.due ? 'date' : 'text'; edDateInput.value = t.due ? t.due.split('T')[0] : '';
        
        const subContainer = document.getElementById('modalSubtasksContainer'); const subList = document.getElementById('modalSubtasksList');
        if(t.subtasks && t.subtasks.length > 0) {
            subContainer.style.display = 'block';
            subList.innerHTML = t.subtasks.map(st => `
                <div class="modal-subtask-item ${st.status === 'completed' ? 'done' : ''}">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <input type="checkbox" ${st.status === 'completed' ? 'checked' : ''} onchange="toggleTask('${st.id}', this.checked, this)">
                        <span onclick="openEdit('${st.id}')" style="cursor:pointer;">${escapeHTML(st.title)}</span>
                    </div>
                    <button onclick="confirmDelTask('${st.id}')" style="background:transparent; border:none; color:var(--danger); cursor:pointer;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                </div>
            `).join('');
        } else { subContainer.style.display = 'none'; }
        showModal('editModal');
    }

    async function saveEdit() {
        showToast(dict[currentLang].saving, 'info');
        const body = { title: document.getElementById('edTitle').value, notes: document.getElementById('edNotes').value };
        const dateVal = document.getElementById('edDate').value; if(dateVal) body.due = new Date(dateVal).toISOString(); else body.due = null; 
        const res = await req(`${API}?action=upd_task&list_id=${currentListId}&task_id=${editingTaskId}`, 'POST', body);
        closeModals(); if(res && !res.error) { showToast(dict[currentLang].msg_saved, 'success'); loadTasks(true); } else { showToast(dict[currentLang].t_err, 'danger'); }
    }

    function confirmDelTask(id) { 
        closeModals(); showConfirm(dict[currentLang].delete, '?', async () => { 
            showToast(dict[currentLang].deleting, 'info');
            const res = await req(`${API}?action=del_task&list_id=${currentListId}&task_id=${id}`, 'POST'); 
            if(res && !res.error) { showToast(dict[currentLang].msg_del, 'success'); loadTasks(true); } else { showToast(dict[currentLang].t_err, 'danger'); }
        }); 
    }
    
    async function saveList() { 
        const title = document.getElementById('nlTitle').value; if(!title) return; closeModals(); showToast(dict[currentLang].saving, 'info');
        const res = await req(`${API}?action=add_list`, 'POST', {title}); 
        if(res && !res.error) { showToast(dict[currentLang].msg_saved, 'success'); document.getElementById('nlTitle').value = ''; loadLists(); } else { showToast(dict[currentLang].t_err, 'danger'); }
    }
    
    function openEditListModal() { document.getElementById('elTitle').value = document.getElementById('listSelectSelected').innerText; showModal('editListModal'); }
    
    async function saveEditList() { 
        const title = document.getElementById('elTitle').value; if(!title) return; closeModals(); showToast(dict[currentLang].saving, 'info');
        const res = await req(`${API}?action=upd_list&list_id=${currentListId}`, 'PATCH', {title}); 
        if(res && !res.error) { showToast(dict[currentLang].msg_saved, 'success'); loadLists(); } else { showToast(dict[currentLang].t_err, 'danger'); }
    }
    
    function confirmDeleteList() { 
        closeModals(); showConfirm(dict[currentLang].del_list, dict[currentLang].s_del_list, async () => { 
            showToast(dict[currentLang].deleting, 'info');
            const res = await req(`${API}?action=del_list&list_id=${currentListId}`, 'POST'); 
            if(res && !res.error) { showToast(dict[currentLang].msg_del, 'success'); currentListId = '@default'; loadLists(); } else { showToast(dict[currentLang].t_err, 'danger'); }
        }); 
    }

    let currentConfirmCallback = null;
    function showConfirm(title, message, callback) { document.getElementById('confirmTitle').innerText = title; document.getElementById('confirmMessage').innerText = message; currentConfirmCallback = callback; showModal('confirmModal'); }
    document.getElementById('confirmActionBtn').addEventListener('click', () => { if(currentConfirmCallback) currentConfirmCallback(); closeModals(); });

    let isUserInteracting = false; 
    document.addEventListener('mousedown', () => isUserInteracting = true); document.addEventListener('mouseup', () => setTimeout(() => isUserInteracting = false, 1000));
    document.addEventListener('touchstart', () => isUserInteracting = true); document.addEventListener('touchend', () => setTimeout(() => isUserInteracting = false, 1000));
    new MutationObserver(() => { isUserInteracting = document.getElementById('editOverlay').style.display === 'block'; }).observe(document.getElementById('editOverlay'), { attributes: true });

    setInterval(async () => {
        if (isUserInteracting || currentListId === '@default') return;
        const d = await req(`${API}?action=get_tasks&list_id=${currentListId}`);
        if (!d || d.error || !d.items) return;
        const newServerHash = genHash(d.items);
        if (lastServerHash !== '' && newServerHash !== lastServerHash) { showToast(dict[currentLang].s_sync, 'info'); loadTasks(true); }
    }, 5000); 

    loadLists();
</script>