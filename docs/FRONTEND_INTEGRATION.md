# Frontend integration notes

The original dashboard's visual layout, tabs, filters, modal and rendering functions can remain. The integration changes only where data and dropdown options come from.

## Existing data flow

The source code currently declares `INITIAL_MAINTENANCE`, `INITIAL_LICENCES` and `EXCEL_TEMPLATE_QUESTIONS`, generates branch RCSA rows in JavaScript, and falls back to browser `localStorage`. Those are the parts to remove. `GET /state` becomes the only initial data source and `PUT /state` persists changes in MySQL.

## Keep these existing functions

Keep the UI functions that render tables and KPIs, handle tabs, open and close modals, export CSV, filter data, and display toast messages. Keep `saveState()` because the new Laravel controller intentionally preserves its JSON request format.

## Replace these functions

Use `integration/dynamic-options.js` to replace:

- `setupFilters()`
- `populateModalDropdowns()`
- `loadState()`
- `resetData()`
- the existing `DOMContentLoaded` event listener

Also adjust the call order in `openAddModal()` and `openEditModal()` as described in `INSTALLATION.md`.

## Static module labels versus database records

The three module names (maintenance, licences, RCSA) are application navigation, not user-managed data, so they can remain in the HTML. Branches, checklist questions, categories, officers, statuses and priorities are records/options and should come from MySQL.

## Security reminder

The existing session selector is a UI context switcher only. It is not a security boundary. Server-side authorization must determine which branch a signed-in user can view or update. Do not trust `currentUserSession` or a branch name supplied by JavaScript as proof of authorization.
