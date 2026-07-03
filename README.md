# Library API (Symfony + Docker)

Opis projektu

Jest to prosta aplikacja API napisana w Symfony (czysta aplikacja) uruchamiana przy pomocy Dockera i PostgreSQL.
Aplikacja służy do zarządzania książkami w bibliotece (dla pracowników biblioteki). API umożliwia dodawanie, usuwanie, pobieranie listy oraz aktualizowanie stanu wypożyczeń.

Funkcjonalności

- Dodanie nowej książki (POST /api/books)
- Usunięcie książki (DELETE /api/books/{id})
- Pobranie listy książek (GET /api/books)
- Aktualizacja stanu książki (PATCH /api/books/{id}/state) - akcje: borrow, return

Uproszczenia i założenia

- Brak uwierzytelniania/autoryzacji (zgodnie z wymaganiem)
- Numer seryjny książki (serialNumber) jest 6-cyfrowym numerem dostarczonym przez pracownika (unikatowy)
- Numer karty wypożyczającego to 6-cyfrowa liczba

Uruchomienie

W katalogu projektu uruchom:

```bash
docker compose up --build
```

Polecenie zbuduje obraz, zainstaluje zależności composer, uruchomi migracje bazy danych i wystawi serwer na porcie 8000.

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

Co zostało przygotowane

- Dockerfile z PHP 8.2, Composerem i wymaganymi rozszerzeniami
- docker-compose.yml z usługami `app` i `db`
- Minimalna struktura Symfony: `src/`, `config/`, `public/`, `bin/console`
- Entity `Book` i migracja tworząca tabelę
- Prosty kontroler API `BookController` z operacjami CRUD
- Skrypt wejściowy `docker-entrypoint.sh`, który czeka na bazę, uruchamia migracje i uruchamia wbudowany serwer PHP

Uwagi

- Przy pierwszym uruchomieniu Docker pobierze i zainstaluje zależności Composer (może chwilę potrwać)
- Możesz dostosować zmienne środowiskowe w pliku `.env`

