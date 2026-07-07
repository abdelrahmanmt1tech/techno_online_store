# Techno Online Store

## Stack

- **Laravel 13** / PHP ^8.3 / Filament ~5.0 / Tailwind CSS v4 / Vite
- **MySQL** (`multi_tenant_store`) — DB, sessions, cache, queues all use `database` driver
- **Locale**: Arabic (`ar`) in `.env`

## Commands

| Command | Action |
|---|---|
| `composer run dev` | Start dev server + queue + logs + Vite (4 concurrent processes) |
| `composer run test` | `config:clear` then `php artisan test` |
| `npm run build` / `npm run dev` | Vite build / dev |

- New Filament resources go under `app/Filament/Resources/` (auto-discovered).
- Admin panel at `/admin`, panel ID `admin`, primary color `Amber`.
- Run `php artisan make:filament-resource` for resource generation.

## Testing

- PHPUnit (not Pest) — `tests/Unit/` and `tests/Feature/`.
- In-memory SQLite (`:memory:`) used in `phpunit.xml`.
- `tests/TestCase.php` uses `Illuminate\Foundation\Testing\TestCase` (no `RefreshDatabase` by default — add trait when tests need DB).

## Code Style

- Laravel Pint for formatting, 4-space indentation per `.editorconfig`.
- Lint: `./vendor/bin/pint` (no dedicated npm lint script).

## Architecture Notes

- No custom Filament resources/pages/widgets yet — skeleton only.
- No API routes registered yet; `bootstrap/app.php` has placeholder JSON handling for `api/*`.
- `session`, `cache`, `queue` drivers all default to `database` — ensure migrations ran.
- `public/build/` is gitignored; run `npm run build` before deploying.
