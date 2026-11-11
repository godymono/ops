# Security and production readiness

This package is a database integration foundation, not a complete bank-grade production security review.

Before deployment on a bank network:

1. Require authenticated users and enforce role/branch authorization in Laravel policies or middleware.
2. Enforce branch scope in database queries on the server. The client-side branch selector does not restrict API access.
3. Add audit trails for create, update, delete and assessment status changes (user ID, timestamp, before/after values).
4. Replace whole-dashboard `PUT /state` synchronization with per-record endpoints. The current contract is retained for compatibility and can overwrite concurrent users' changes.
5. Add pagination for large RCSA data sets rather than sending all branch assessments in one response.
6. Add automated tests for validation, branch authorization, concurrent updates, deletion and export.
7. Restrict who can edit master checklist templates, statuses, categories, branches and officers.
8. Use HTTPS, secure session cookies, backups and a tested restore procedure.
9. Do not store passwords or sensitive customer information in checklist remarks unless approved by bank policy.

The route snippet includes `auth`. If the project does not yet have authentication configured, use it only in a local development environment until authentication and authorization are implemented.
