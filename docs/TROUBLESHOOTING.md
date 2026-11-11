# Troubleshooting

## HTTP 404 for `/state` or `/dashboard-options`

- Check `routes/web.php` contains the routes from `routes/api.php.snippet.php`.
- Run `php artisan route:list --path=state` and `php artisan route:list --path=dashboard-options`.
- Clear route/config caches with `php artisan optimize:clear`.

## HTTP 419 Page Expired on save

- Ensure the dashboard is rendered by Laravel and has `<meta name="csrf-token" content="{{ csrf_token() }}">` in its `<head>`.
- Ensure the routes are in `web.php`, not `api.php`, so the session and CSRF middleware are active.
- Keep the existing `X-CSRF-TOKEN` header in `saveState()`.

## HTTP 401 / 302 redirect to login

The route group uses `auth`. Sign in, or configure authentication. Only remove `auth` temporarily for a local-only development instance.

## SQLSTATE connection error

Check `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`. Run `php artisan config:clear` after changes. Confirm MySQL is running.

## Dashboard reports no data

Run `php artisan db:seed --class=AzaniaDashboardSeeder`. Confirm the JSON data files exist under `database/seeders/data/` and the migration completed.

## Dropdowns are empty

Open `/dashboard-options` while signed in. The response should contain `branches`, `categories`, `maintenanceCategories`, `licenceCategories`, `rcsaCategories`, `officers`, `statuses`, `priorities` and `sessions`. Confirm `dynamic-options.js` has been added and the original initialization listener was replaced, not duplicated.

## Duplicate function / unexpected dropdown values

Do not keep both the old and replacement definitions of `setupFilters`, `populateModalDropdowns`, `loadState`, `resetData` or the DOMContentLoaded listener. Remove the hardcoded initial data arrays and generator so the dashboard does not fall back to them.

## Migration already exists / duplicate table

Inspect `php artisan migrate:status` and the existing schema. Do not run `migrate:fresh` on a database containing application data. Rename/reconcile an existing table only after a backup and schema review.
