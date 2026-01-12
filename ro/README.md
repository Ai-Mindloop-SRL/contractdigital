# Admin Dashboard for ContractDigital Platform

This directory contains the admin dashboard for managing the multi-tenant contract platform.

## Structure

- `dashboard/` - Admin dashboard pages
  - `edit_site.php` - Edit site configurations
  - `auth_check.php` - Authentication middleware
- `config/` - Configuration files (not in Git, server-specific)

## Important

This admin panel requires:
- `/config/database.php` - Database connection config
- `/includes/db_helpers.php` - Database helper functions
- Proper authentication session

## Database Helper Functions

The dashboard uses these helper functions from `/includes/db_helpers.php`:
- `executeUpdate()` - Execute UPDATE/INSERT/DELETE queries
- `fetchOne()` - Fetch single row from database
- `fetchAll()` - Fetch multiple rows from database
- `executeInsert()` - Execute INSERT and return last ID

## Fix Applied (2026-01-12)

Fixed "Call to undefined function executeUpdate()" error by:
1. Creating `/includes/db_helpers.php` with required database helper functions
2. Adding `require_once __DIR__ . '/../../includes/db_helpers.php';` to `edit_site.php`

## Deployment

When deploying this directory:
1. Ensure `/includes/db_helpers.php` exists on server
2. Ensure `/config/database.php` has correct credentials
3. Set proper file permissions (644 for PHP files)
