# Changelog

All notable changes to this project will be documented in this file.

## [1.1.0] - 2026-05-23

### Security & Session Isolation
- **Przejście z przechowywania globalnego na sesyjne (per-przeglądarka):** Zastąpiono globalny plik `token.json` na serwerze bezpiecznym mechanizmem sesji PHP (`$_SESSION['google_tokens']`). Zapewnia to pełną izolację sesji między urządzeniami (np. komputerem a telefonem).
- **Zwiększona żywotność sesji (30 dni):** Skonfigurowano parametry `session.cookie_lifetime` i `session.gc_maxlifetime` na 30 dni, aby uniknąć konieczności częstego logowania się na urządzeniach mobilnych.
- **Usunięcie starego pliku tokena:** Usunięto zbędny plik `token.json` z katalogu projektu w celu poprawy bezpieczeństwa.
