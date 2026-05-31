# Changelog

All notable changes to this project will be documented in this file.

## [1.2.0] - 2026-06-01

### Project Reorganization
- **Modular Directory Structure:** Moved gateway files to `gcloud/gateway/` and tasks app files to `gcloud/tasks/` to prepare for integration with more Google services.
- **Environment Updates:** Updated internal references and `.env` paths to support the new directory layout.

### Gateway Enhancements & Security
- **UI Password Protection:** Added a secure unlock screen to the Gateway GUI dashboard. Access requires a password configured in the `.env` file (`GATEWAY_PASSWORD`).
- **Manual Lockout:** Added a "Zablokuj Gateway" button to reset the authentication session.
- **CLI/Proxy Support:** Added fallback storage of OAuth tokens to `token.json` specifically for background scripts and proxy requests.
- **Binary Uploads:** Added automatic `Content-Type: image/png` detection for raw binary data payloads in proxy requests.
- **Account Selection:** Enforced `select_account` in the OAuth consent prompt to allow users to easily switch Google accounts.

## [1.1.0] - 2026-05-23

### Security & Session Isolation
- **Przejście z przechowywania globalnego na sesyjne (per-przeglądarka):** Zastąpiono globalny plik `token.json` na serwerze bezpiecznym mechanizmem sesji PHP (`$_SESSION['google_tokens']`). Zapewnia to pełną izolację sesji między urządzeniami (np. komputerem a telefonem).
- **Zwiększona żywotność sesji (30 dni):** Skonfigurowano parametry `session.cookie_lifetime` i `session.gc_maxlifetime` na 30 dni, aby uniknąć konieczności częstego logowania się na urządzeniach mobilnych.
- **Usunięcie starego pliku tokena:** Usunięto zbędny plik `token.json` z katalogu projektu w celu poprawy bezpieczeństwa.
