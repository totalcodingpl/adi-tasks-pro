# 🚀 Adi-Tasks PRO | The Ultimate Google Tasks PWA

<div align="center">
  
  <p align="center">
    <a href="README.md">🇺🇸 English</a> • <a href="README-pl.md">🇵🇱 Polski</a>
  </p>

  <!-- INSERT YOUR MAIN BANNER IMAGE HERE (Recommended size: 1280x720px) -->
  <img src="URL_TO_YOUR_MAIN_BANNER.jpg" alt="Adi-Tasks PRO Banner" width="800">

  <br><br>
  <b>A professional, standalone, and ultra-fast Google Tasks client built as a Progressive Web App (PWA).</b>
  <br>Designed for maximum productivity, featuring Glassmorphism UI, Haptic Feedback, Voice Control, and deep Drag & Drop capabilities.
  <br><br>

  [![Live Demo](https://img.shields.io/badge/Live-Demo-4facfe?style=for-the-badge&logo=google-chrome)](https://total.smallhost.pl/adi-tools/gcloud/tasks.php)
  [![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)]()
  [![PWA](https://img.shields.io/badge/PWA-Ready-5A0FC8?style=for-the-badge&logo=pwa&logoColor=white)]()
  
  <br><br>
  <!-- OFFICIAL KO-FI BUTTON -->
  <a href="https://ko-fi.com/adrianczarnecki" target="_blank">
    <img height="36" style="border:0px;height:36px;" src="https://storage.ko-fi.com/cdn/kofi6.png?v=6" border="0" alt="Buy Me a Coffee at ko-fi.com" />
  </a>

</div>

---

## 📖 Table of Contents
1. [Why Adi-Tasks PRO?](#-why-adi-tasks-pro)
2. [Killer Features](#-killer-features)
3. [Live Demo](#-live-demo)
4. [System Architecture](#%EF%B8%8F-system-architecture)
5. [Installation & Setup](#-installation--gcp-setup)
6. [Roadmap (Coming Soon)](#-roadmap--coming-soon)
7. [Support the Project](#-support)

---

## 💡 Why Adi-Tasks PRO?

The official web client for Google Tasks is nothing more than a narrow sidebar hidden inside Gmail or Google Calendar. It lacks independence, speed, and modern UX.

**Adi-Tasks PRO** pulls your tasks out of the shadows. It is a standalone *Progressive Web App* that acts as a full-fledged Dashboard on your PC, and installs like a native app on your smartphone (without the browser address bar).

> **ℹ️ Info:** The project utilizes a *Smart Background Polling* engine. Changes made on another device or inside Gmail will sync quietly in the background without refreshing the page.

---

## 🔥 Killer Features

- [x] **Premium UX/UI (Glassmorphism):** Dark backgrounds, blur effects, neon accents, and buttery smooth CSS animations.
-[x] **Deep Drag & Drop:** Multi-level task dragging powered by SortableJS. Pull a main task into a subtask position with a swipe of your finger.
- [x] **Web Speech API (STT & TTS):** Dictate tasks into your microphone and listen to voice confirmations of your actions!
- [x] **Haptic Feedback:** Physical vibrations when moving, dropping, and checking off tasks (supported on mobile devices).
- [x] **Multilingual (i18n):** Change the language on the fly (EN, PL, DE, ES, FR, ZH, UA) without reloading the page.
-[x] **Web Share Target:** Share links from your mobile browser directly into Adi-Tasks PRO to create tasks instantly!
- [x] **Shared Hosting Bypass:** Custom `.env` parser and advanced `.htaccess` rules to bypass hard error screens on cheap shared hostings.

### See it in action

| Majestic Menu | Deep Drag & Drop | Voice Input |
|:---:|:---:|:---:|
| <!-- INSERT GIF 1 LINK HERE --> <img src="link_to_menu_gif.gif" width="250" alt="Menu Animation"> | <!-- INSERT GIF 2 LINK HERE --> <img src="link_to_drag_gif.gif" width="250" alt="Drag and Drop"> | <!-- INSERT GIF 3 LINK HERE --> <img src="link_to_voice_gif.gif" width="250" alt="Voice Input"> |

---

## 👁️ Live Demo

Don't take my word for it. Try it out yourself. (Requires logging in with a Google Account to read/write your tasks via official OAuth 2.0).

👉 <b><a href="https://total.smallhost.pl/adi-tools/gcloud/tasks.php" target="_blank">Launch Adi-Tasks PRO</a></b>

> *Tip: Open this link on your smartphone via Chrome/Safari and tap "Add to Home Screen" to experience the full native PWA power.*

---

## 🏗️ System Architecture

The project was built according to strict MVC (Model-View-Controller) and Separation of Concerns patterns, split into 3 microservices:

1. 🛡️ `google-cloud-api-gateway.php` **(Auth & Proxy Core)**
   * Manages the entire OAuth 2.0 token lifecycle. Reads `.env`, refreshes `token.json` on the fly, and injects auth headers into REST requests. It has zero knowledge of "Tasks" logic.
2. ⚙️ `tasks-controller.php` **(Domain Controller)**
   * The bridge between the frontend and the Gateway. Translates frontend requests into Google Tasks API specifics.
3. 📱 `tasks.php` **(View / PWA)**
   * Pure Vanilla JS frontend. Handles Drag&Drop, Haptics, Polling, Speech APIs, and User Interaction.

---

## 🛠 Installation & GCP Setup

Want to host this app on your own server? Follow these steps:

### 1. Google Cloud Console
1. Log in to [Google Cloud Console](https://console.cloud.google.com/).
2. Create a new project and enable the **Google Tasks API**.
3. Go to *Credentials* -> Create Credentials -> **OAuth client ID** (Choose "Web application").
4. Under *Authorized redirect URIs*, enter the exact path to the gateway file on your server (e.g., `https://yourdomain.com/gcloud/google-cloud-api-gateway.php`).
5. Copy the generated `Client ID` and `Client Secret`.

### 2. Server Deployment
1. Clone this repository and upload the files to your hosting (Requires PHP 8.0+ and `mod_rewrite`).
2. Create a `.env` file in the script directory (We don't commit this file for security reasons).

### 3. `.env` Configuration
Fill your `.env` file with the following variables:

```ini
APP_VERSION="1.0.0"

# GOOGLE CREDENTIALS
GOOGLE_CLIENT_ID="your-client-id.apps.googleusercontent.com"
GOOGLE_CLIENT_SECRET="your-client-secret"
GOOGLE_REDIRECT_URI="https://yourdomain.com/path/google-cloud-api-gateway.php"
TOKEN_FILE_NAME="token.json"
GOOGLE_SCOPES="https://www.googleapis.com/auth/tasks"

# SYSTEM ROUTING
TASKS_FRONTEND_URL="https://yourdomain.com/path/tasks.php"
TASKS_CONTROLLER_FILE="tasks-controller.php"
```

> **🔒 Security Note:** The included `.htaccess` file will automatically block external access to your `.env` and `token.json` files, silently replacing them with a 403 Forbidden page.

---

## 🚀 Roadmap / Coming Soon

This project is actively maintained. Here is what we plan to implement next:

- [ ] 🎨 **Themes System:** Switch between Dark Mode (Glassmorphism), Light Mode, and AMOLED Pitch Black.
- [ ] 🔌 **Offline Mode (Sync Queue):** Add tasks without a network connection. The app will buffer them in *IndexedDB* and push them to Google as soon as you regain signal.
- [ ] 🔔 **Desktop Push Notifications:** Native browser notifications reminding you of approaching deadlines.
- [ ] 🏷️ **Custom Tags/Labels:** Visual "pill-tags" to quickly categorize your tasks.

---

## ☕ Support

This application is fully Open-Source. I spent countless hours optimizing every pixel and line of code so that the UX feels absolutely flawless. 

If Adi-Tasks PRO saves your productivity or if you learned something cool by analyzing this code — consider buying me a coffee! It will be a massive motivational boost to keep coding! 💪

<div align="center">
  <br>
  <a href="https://ko-fi.com/adrianczarnecki target="_blank">
    <img height="40" style="border:0px;height:40px;" src="https://storage.ko-fi.com/cdn/kofi6.png?v=6" border="0" alt="Buy Me a Coffee at ko-fi.com" />
  </a>
</div>

<br>

---
<div align="center">
  <i>Designed with a passion for clean code and seamless UX.</i>
</div>