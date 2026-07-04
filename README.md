# Library API (Symfony + Docker)

Opis projektu

To prosta aplikacja API napisana w Symfony i dostarczona z konfiguracją Docker + PostgreSQL. Aplikacja umożliwia pracownikom biblioteki:

- rejestrowanie książek (unikalny 6-cyfrowy numer seryjny),
- przechowywanie tytułu i autora,
- oznaczanie książki jako wypożyczonej (z 6-cyfrowym numerem karty wypożyczającego) i zwracanie książki,
- pobieranie listy wszystkich książek oraz usuwanie książek.

Wymagania z zadania rekrutacyjnego zostały zrealizowane — szczegóły w części "Weryfikacja wymagań".

Funkcjonalności (API)

- POST /api/books — dodanie nowej książki
- GET /api/books — pobranie listy wszystkich książek
- DELETE /api/books/{id} �� usunięcie książki
- PATCH /api/books/{id}/state — aktualizacja stanu (akcje: borrow, return)

Formaty i walidacja

- `serialNumber` — wymagany, 6-cyfrowy string, unikatowy
- `title`, `author` — wymagane
- `cardNumber` (dla akcji borrow) — 6-cyfrowy string

Uruchomienie (lokalnie, w katalogu projektu)

Jeśli chcesz uruchomić aplikację od nowa (zalecane po aktualizacjach):

```bash
# zatrzymaj kontenery i usuń wolumeny (USUWA DANE DB)
docker compose down -v

# wyczyść cache aplikacji
rm -rf var/cache/*

# odbuduj obrazy i uruchom kontenery
docker compose up --build -d

# po chwili uruchom migracje (tworzą schemat DB)
docker compose exec app php bin/console doctrine:database:create --if-not-exists
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

Jeśli nie chcesz usuwać wolumenów DB, wykonaj tylko:

```bash
docker compose up --build -d
docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction
```

Przykłady zapytań

Dodanie książki:

```bash
curl -X POST http://localhost:8000/api/books \
  -H "Content-Type: application/json" \
  -d '{"serialNumber":"123456","title":"Pan Tadeusz","author":"Adam Mickiewicz"}'
```

Pobranie listy:

```bash
curl http://localhost:8000/api/books
```

Wypożyczenie książki:

```bash
curl -X PATCH http://localhost:8000/api/books/1/state \
  -H "Content-Type: application/json" \
  -d '{"action":"borrow","cardNumber":"654321"}'
```

Zwrot książki:

```bash
curl -X PATCH http://localhost:8000/api/books/1/state \
  -H "Content-Type: application/json" \
  -d '{"action":"return"}'
```

Usunięcie książki:

```bash
curl -X DELETE http://localhost:8000/api/books/1
```

Weryfikacja wymagań (mapowanie do specyfikacji zadania)

- Unikalny numer seryjny 6-cyfrowy: zaimplementowane (walidacja w `BookController::create`, pole `serialNumber` z unikalnym indeksem).
- Tytuł i autor: zaimplementowane (wymagane pola przy tworzeniu).
- Informacja o wypożyczeniu: pole `isBorrowed`, `borrowedAt`, `borrowedByCardNumber` — zaimplementowane w encji `App\\Entity\\Book`.
- Numer karty wypożyczającego 6-cyfrowy: walidacja w `BookController::state` przy akcji `borrow`.
- Endpointy: dodanie, usunięcie, pobranie listy, aktualizacja stanu — wszystkie dostępne.
- Brak uwierzytelniania/autoryzacji: zgodnie z wymaganiem (aplikacja publiczna w tym zadaniu).
- Symfony + PostgreSQL + Docker: zaimplementowane (plik `docker-compose.yml` uruchamia serwis `db: postgres` i serwis `app` z PHP/Composer). `docker-entrypoint.sh` uruchamia migracje przy starcie.

Uwagi techniczne i naprawy dokonane

- Kontrolery zostały oznaczone tagiem `controller.service_arguments` w `config/services.yaml` aby Symfony mógł prawidłowo wstrzykiwać zależności do kontrolerów.
- Mapowanie Doctrine: aby uniknąć problemów z wykrywaniem metadanych w różnych środowiskach, dodano zarówno mapping przez atrybuty w encji (`src/Entity/Book.php`) jak i plik YAML mappingu (`config/doctrine/Book.orm.yml`).
- Nazwy kolumn w DB zostały ujednolicone do snake_case (np. `serial_number`, `is_borrowed`, `borrowed_at`, `borrowed_by_card_number`), a w encji używamy camelCase (np. `serialNumber`). Migracja została zaktualizowana aby tworzyć kolumny w snake_case.
- Jeśli po zmianach kontener używa starego cache — usuń `var/cache/*` i odbuduj obraz (`docker compose up --build`).

Pliki istotne

- `src/Entity/Book.php` — encja z atrybutami mappingu
- `src/Controller/Api/BookController.php` — logika endpointów
- `migrations/Version20260703.php` — migracja tworząca tabelę `books`
- `config/doctrine/Book.orm.yml` — dodatkowy mapping YAML
- `config/services.yaml` — konfiguracja serwisów (tag controller.service_arguments)
- `docker-entrypoint.sh`, `Dockerfile`, `docker-compose.yml` — uruchomienie i migracje

Kontakt / repo

Projekt jest w katalogu lokalnym — jeśli chcesz, mogę pomóc przygotować publiczne repozytorium (GitHub/GitLab) i instrukcję deployowania do hostingu.

Jeżeli coś nie działa po uruchomieniu, wklej proszę wyniki diagnostyki (logi kontenera i wynik `php bin/console doctrine:mapping:info`) — pomogę dalej.
