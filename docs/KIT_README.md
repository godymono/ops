# Azania Bank Risk, Compliance & Maintenance Dashboard — MySQL Integration Kit

This package converts the dashboard's data layer from JavaScript arrays / browser `localStorage` into Laravel Eloquent tables backed by MySQL. It is designed to integrate with the existing Laravel application and preserve the current dashboard interface.

## What is included

- Eight normalized database tables: branches, categories, officers, dashboard options, maintenance issues, licences, RCSA templates and RCSA assessments.
- Laravel Eloquent models and relationships.
- A controller that serves the existing `/state` JSON contract and saves the dashboard state transactionally.
- A seeder containing the original maintenance and licence sample records plus all checklist questions extracted from the supplied HTML source.
- A database-driven JavaScript integration file for branch, category, officer, status and priority dropdowns.
- Installation, frontend integration, schema, security and troubleshooting guides.

## Important source-data finding

The supplied source contains **138 distinct RCSA checklist questions** in `EXCEL_TEMPLATE_QUESTIONS`. The current `generateRCSA306Items()` function loops those 138 questions across five branches, which produces **690 assessment records**, not 306. The source also displays a hardcoded “306” badge. This kit preserves all 138 original question texts and seeds 138 assessments per each of the five branches (690 assessments total). New assessments start as `N/E` (Not Evaluated); it does not fabricate YES/NO compliance answers.

## Requirements

- Laravel 12 (the code also uses standard Laravel Eloquent / routing features common to recent Laravel releases).
- PHP 8.2 or newer.
- MySQL / MariaDB configured in `.env`.
- Existing dashboard served from the same Laravel application origin, so session cookies and CSRF protection work.

## Quick installation

Read `docs/INSTALLATION.md` from beginning to end. Do not run migrations until you have backed up your current database.

## Existing `/state` route

The supplied JavaScript already calls `GET /state` and `PUT /state`. If your Laravel project already has a `/state` route/controller, replace its old JSON/file/local-storage persistence implementation with the controller and routes in this package. Do not register duplicate routes.

## Important deployment note

The included controller keeps the current dashboard's whole-state save contract to minimize frontend changes. It saves the submitted module arrays in a transaction and removes database rows omitted from the submitted state. That matches the existing single-dashboard JavaScript behaviour, but simultaneous edits by multiple users can overwrite each other's latest state. Before multi-user production rollout, replace whole-state saving with per-record CRUD endpoints, add authorization policies and audit logging, and enforce branch-level access on the server.
