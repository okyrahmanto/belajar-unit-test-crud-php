# PHP CRUD – Unit Testing Sample

A small Product CRUD app built to teach unit testing with **PHPUnit**.

## Setup

Requirements: PHP 8.1+, Composer, and the `pdo_sqlite` extension (for the integration tests).

```bash
composer install
composer test               # run everything
composer test:unit          # only unit tests (fast, no database)
composer test:integration   # only database tests (SQLite in memory)
```

Run one file or one test:

```bash
vendor/bin/phpunit tests/Unit/CreateProductTest.php
vendor/bin/phpunit --filter test_create_rejects_duplicate_name
```

## Run the web app (for manual testing)

```bash
composer serve
# or, without Composer:
php -S localhost:8000 -t public public/index.php
```

Open http://localhost:8000. The database file `database/app.sqlite` is created on the first run;
delete it to start again with an empty list.

Try the manual test from the slides: open http://localhost:8000/products/create, enter
name "Pencil" and price 0, click Save, and check the error shows and nothing was saved.
Then compare it with `tests/Unit/ManualVsAutomatedTest.php`.

| URL | What it does |
|---|---|
| `GET /products` | list products |
| `GET /products/create` | add form |
| `POST /products` | create |
| `GET /products/{id}/edit` | edit form |
| `POST /products/{id}` | update |
| `POST /products/{id}/delete` | delete |

The web page uses the same `ProductService` as the tests, so every rule the tests check
also applies in the browser. (It's a classroom demo: there's no login or CSRF protection.)

## Project structure

```
src/
  Product.php                     data object
  ProductRepositoryInterface.php  storage "contract"
  PdoProductRepository.php        real database implementation (PDO)
  ProductService.php              business rules  <-- what we unit test
  ValidationException.php
  ProductNotFoundException.php
public/index.php                  tiny web UI (manual testing)
database/schema.sql               products table
tests/
  Fakes/InMemoryProductRepository.php   fake DB using a PHP array
  Unit/CreateProductTest.php            C
  Unit/ReadProductTest.php              R
  Unit/UpdateProductTest.php            U
  Unit/DeleteProductTest.php            D
  Unit/ManualVsAutomatedTest.php        manual steps vs the same test in code
  Unit/ProductServiceMockTest.php       advanced: mocks
  Integration/PdoProductRepositoryTest.php  real SQL on SQLite :memory:
```

## Business rules (what the tests protect)

- Name is required (spaces are trimmed), max 100 characters, and must be unique
- Price must be greater than 0
- Stock cannot be negative
- Reading, updating or deleting a missing id throws `ProductNotFoundException`

## Key ideas

| Idea | Where to see it |
|---|---|
| Arrange → Act → Assert | `CreateProductTest::test_create_product_with_valid_data` |
| `setUp()` gives every test a fresh start | top of every test class |
| Testing exceptions | `expectException()` / `expectExceptionMessage()` |
| Boundary values (100 ok, 101 not) | `CreateProductTest` |
| Data providers (one test, many inputs) | `test_create_rejects_invalid_price` |
| Fake vs mock | `InMemoryProductRepository` vs `ProductServiceMockTest` |
| Unit vs integration | `tests/Unit` vs `tests/Integration` |

## Classroom exercises

1. **See red.** In `ProductService::validate()`, change `$price <= 0` to `$price < 0`.
   Run the tests. Which ones fail? Why? Change it back.
2. **Add a rule (TDD).** New rule: *price cannot be more than 100,000,000*.
   Write the failing test first, run it (red), then change the code (green).
3. **Search.** Add `searchByName(string $keyword): array` to the service.
   Write tests first: finds partial matches, is case-insensitive, returns `[]` when nothing matches.
4. **Stock.** Add `decreaseStock(int $id, int $qty)`. Tests: stock goes down,
   cannot go below 0, `$qty` must be positive, missing id throws.
5. **Integration.** Add the matching SQL test in `PdoProductRepositoryTest` for exercise 3.
6. **Coverage (optional, needs Xdebug or PCOV).**
   `XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text` and find untested lines.
