<?php
header('Content-Type: application/javascript');
$env_path = __DIR__ . '/../.env';
$version = '1.2.0';
if (file_exists($env_path)) {
    $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), 'APP_VERSION=') === 0) {
            $version = trim(explode('=', $line, 2)[1], " \"'");
            break;
        }
    }
}
?>
const CACHE_NAME = 'adi-tasks-pro-v<?= $version ?>';
const ASSETS_TO_CACHE =[
    './tasks.php',
    './manifest.php',
    './icon.svg',
    'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js'
];

self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS_TO_CACHE)));
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.map(key => { if (key !== CACHE_NAME) return caches.delete(key); })
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    if (event.request.method !== 'GET') return;
    event.respondWith(fetch(event.request).catch(() => caches.match(event.request)));
});