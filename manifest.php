<?php
header('Content-Type: application/json');
?>
{
  "name": "Adi-Tasks PRO",
  "short_name": "Adi-Tasks",
  "description": "Profesjonalny menedżer zadań Gringo Sec.",
  "start_url": "./tasks.php",
  "display": "standalone",
  "background_color": "#1a2a6c",
  "theme_color": "#1a2a6c",
  "orientation": "portrait-primary",
  "icons":[
    {
      "src": "icon.svg",
      "sizes": "any",
      "type": "image/svg+xml",
      "purpose": "any maskable"
    }
  ],
  "share_target": {
    "action": "./tasks.php",
    "method": "GET",
    "enctype": "application/x-www-form-urlencoded",
    "params": {
      "title": "share_title",
      "text": "share_text",
      "url": "share_url"
    }
  },
  "shortcuts":[
    {
      "name": "Szybkie zadanie",
      "short_name": "Dodaj",
      "description": "Otwórz okno dodawania zadania",
      "url": "./tasks.php?action=new",
      "icons":[{ "src": "icon.svg", "sizes": "192x192", "type": "image/svg+xml" }]
    }
  ]
}