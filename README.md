# Numberblocks Calculator Application

A Laravel 12 + Inertia (Vue 3) calculator application with:

- high-precision calculator API
- persisted calculation history
- ticker tape UI (list, delete single, clear all)
- support for simple operations and advanced expression chains

## Features

- Simple mode: `left`, `operator`, `right`
- Expression mode: full expressions like `sqrt((((9*9)/12)+(13-4))*2)^2`
- Operators: `+`, `-`, `*`, `/`, `^`
- Functions: `sqrt()`
- High-precision string math (BCMath-based)
- Full API + UI integration with persisted history
- Pest feature and unit tests

## Tech Stack

- PHP 8.5+
- Laravel 12
- Inertia v2 + Vue 3 + TypeScript
- MySQL
- Pest 4

## Local Setup (Laravel Herd)

### 1) Prerequisites

- Laravel Herd installed
- PHP 8.5+ with BCMath extension enabled
- MySQL running locally
- Node 20+ and npm
- Composer

### 2) Install dependencies

```bash
composer install
npm install
```

### 3) Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Update DB credentials in `.env` if needed:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=numberblocks_calculator
DB_USERNAME=root
DB_PASSWORD=
```

### 4) Run migrations

```bash
php artisan migrate
```

### 5) Start development servers

```bash
composer run dev
```

With Herd, the app URL typically follows:

- `https://<project-folder-name>.test`

## Application Structure

### HTTP Layer

- `routes/api.php` defines calculator endpoints.
- `app/Http/Requests/StoreCalculationRequest.php` validates dual-mode payloads.
- `app/Http/Controllers/Api/CalculationController.php` handles create/list/delete/clear.
- `app/Http/Resources/CalculationResource.php` normalizes API output.

### Domain Layer

- `app/Data/CalculationInputData.php` DTO for validated input.
- `app/Services/Calculator/CalculationEngine.php` orchestrates calculation flow.
- `app/Services/Calculator/ExpressionEvaluator.php` tokenizes/parses/evaluates expressions.
- `app/Services/Calculator/OperatorRegistry.php` and `FunctionRegistry.php` define supported operations.
- `app/Services/Calculator/HighPrecisionMath.php` wraps BCMath operations and normalization.

### Persistence Layer

- `app/Models/Calculation.php`
- `database/migrations/*_create_calculations_table.php`

### Frontend Layer

- `resources/js/pages/Welcome.vue` contains calculator + ticker tape UI.

## API Endpoints

### Create Calculation

`POST /api/calculations`

Simple mode payload:

```json
{
  "left": "10.5",
  "operator": "+",
  "right": "2.25"
}
```

Expression mode payload:

```json
{
  "expression": "sqrt((((9*9)/12)+(13-4))*2)^2"
}
```

### List History

`GET /api/calculations`

### Delete One

`DELETE /api/calculations/{id}`

### Clear All

`DELETE /api/calculations`

## Running Tests

Run all tests:

```bash
php artisan test --compact
```

Run specific suites:

```bash
php artisan test --compact --filter=CalculationApiTest
php artisan test --compact --filter=ExpressionEvaluatorTest
```

## FAQ

### Why use DTOs?

DTOs keep transport details separate from domain logic, making business logic cleaner and easier to test.

### Why custom validators?

Validation rules capture boundary constraints (mode exclusivity, token restrictions, division by zero) before domain execution.

### Why registries for operators/functions?

Registries allow adding operators/functions without rewriting parser or controller logic.

### Why BCMath?

BCMath avoids common floating-point drift by keeping arithmetic in high-precision string form.

### Why persist all calculations?

It supports ticker tape history and auditability, while simplifying state recovery for the UI.

## Contributing

Contributions are welcome.

1. Fork the repository.
2. Create a feature branch from `main`.
3. Add tests for behavior changes.
4. Run formatting/tests locally:
   - `vendor/bin/pint --dirty --format agent`
   - `php artisan test --compact`
5. Open a pull request with a clear summary and test plan.

