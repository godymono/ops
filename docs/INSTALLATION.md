# Installation and migration guide

## 1. Back up the existing application and database

From the Laravel project directory:

```bash
cp -R app app.backup-before-azania-db
cp -R routes routes.backup-before-azania-db
cp -R database database.backup-before-azania-db
```

Also export your current MySQL database using phpMyAdmin or `mysqldump`. Do not rely only on the code backup.

## 2. Confirm MySQL connection

Check `.env` and set the values for your own local MySQL installation. Example only:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ops
DB_USERNAME=root
DB_PASSWORD=
```

Use the actual database name, port, username and password configured in MAMP/XAMPP. Then run:

```bash
php artisan config:clear
php artisan about
```

Do not proceed if Laravel cannot connect to MySQL.

## 3. Copy package files into the Laravel project

Copy the contents of these package folders into the matching Laravel folders, preserving paths:

- `app/Models/` → `your-project/app/Models/`
- `app/Http/Controllers/` → `your-project/app/Http/Controllers/`
- `database/migrations/` → `your-project/database/migrations/`
- `database/seeders/` → `your-project/database/seeders/`

Copy the three JSON files in `database/seeders/data/` as well. The seeder expects them at exactly that path.

## 4. Register the seeder

Open `database/seeders/DatabaseSeeder.php` and add this line inside its `run()` method:

```php
$this->call(AzaniaDashboardSeeder::class);
```

Do not replace other seeders already registered there.

## 5. Register routes

Open `routes/web.php`. Remove or replace any old `/state` GET/PUT routes that write a JSON file or store the dashboard as one JSON blob. Add the route definitions from `routes/api.php.snippet.php` to `routes/web.php`.

These routes use the `web` session and CSRF middleware. The snippet includes `auth`; if your app has no login system yet and you intentionally run a local-only development instance, you may temporarily remove `auth` from the route group. Do not deploy an unauthenticated bank operations dashboard to a shared network or production environment.

## 6. Run migrations and seed the database

First inspect the migration plan:

```bash
php artisan migrate:status
```

Then run:

```bash
php artisan migrate
php artisan db:seed --class=AzaniaDashboardSeeder
```

The seeder uses `firstOrCreate` / `updateOrCreate` for repeatability. It creates the five branch records found in the supplied dashboard source, imports the four maintenance records and five licence records, imports all 138 RCSA templates, and creates 690 unevaluated branch assessments.

If your database already contains tables with these names, stop and inspect them before running migrations. Do not use `migrate:fresh`; it drops every table in the configured database.

## 7. Connect the existing dashboard JavaScript

Open the Blade file containing the supplied dashboard HTML and its large inline `<script>` block.

1. Copy the contents of `integration/dynamic-options.js` into the existing script block, replacing the old `setupFilters()`, `populateModalDropdowns()`, `loadState()`, `resetData()` and `DOMContentLoaded` functions. Do not keep both old and new versions of those functions.
2. Remove the old `INITIAL_MAINTENANCE`, `INITIAL_LICENCES`, `EXCEL_TEMPLATE_QUESTIONS`, `generateRCSA306Items()` and `loadDefaultState()` declarations/functions. The database is now the source of truth.
3. Keep the existing `saveState()` function. It continues to send `PUT /state` with the existing `{ state: appState }` JSON shape.
4. In `openAddModal()`, remove its first call to `populateModalDropdowns()`. Call `populateModalDropdowns()` immediately after setting `modalModule` to `currentTab`.
5. In `openEditModal(module, id)`, remove its first call to `populateModalDropdowns()`. Call it immediately after setting `modalModule` to `module`.
6. Replace the original `DOMContentLoaded` listener entirely with the listener supplied in `dynamic-options.js`. There must be only one initialization listener for the dashboard.
7. The old Reset button now reloads saved database records; it no longer restores hardcoded defaults. Consider changing its title to `Reload database data` and its visible label from `Reset` to `Reload`.

### Example modal call order

For `openAddModal()`, the relevant sequence must be:

```javascript
document.getElementById('unifiedForm').reset();
document.getElementById('modalItemId').value = '';
document.getElementById('modalModule').value = currentTab;
populateModalDropdowns();
toggleModalFields();
```

For `openEditModal(module, id)`, set `modalModule` to `module`, then call `populateModalDropdowns()`, then populate the record fields.

## 8. Test the application

Run:

```bash
php artisan route:list --path=state
php artisan route:list --path=dashboard-options
php artisan optimize:clear
```

Open the dashboard and verify:

- All five branch names load from MySQL in the session selector, branch filter and modal.
- Categories and assigned officers load from MySQL.
- Maintenance and licence records load from MySQL.
- RCSA shows 138 questions for one branch and 690 assessments in the HQ view before filters.
- A new maintenance record can be saved, page refreshed, and record still exists.
- An edited RCSA result persists after refresh.
- Deleting a record persists after refresh.
- CSV export still works.

## 9. Count badge correction

The source has a hardcoded `306` badge, but the source question array has 138 questions and the current generator produces 690 records across five branches. Replace the hardcoded badge text with a neutral label such as `RCSA`, or implement a dynamic count from `appState.rcsa.length`. The `rcsaCountSummary` text is already updated dynamically by the existing `renderRcsaTable()` function.

## 10. If you need to roll back

Restore your code backups and database backup. Do not run `migrate:rollback` blindly if the database has received real operational updates since the migration was applied.
