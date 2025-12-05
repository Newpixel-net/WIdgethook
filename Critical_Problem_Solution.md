# Critical Problem Solution - 500 Internal Server Error

## Date: December 5, 2025

## Problem Summary
After merging PR #44, the production site (landingo.net) displayed a "500 Internal Server Error" page. The site was working at deployment #43 but broke after subsequent changes.

---

## Root Cause
The `pages` database table was missing columns that the application code expected. The query in `app/models/Page.php` (line 33) was failing:

```php
$result = database()->query('SELECT `url`, `title`, `type`, `open_in_new_tab`, `language`, `icon`, `position`, `plans_ids` FROM `pages` WHERE `is_published` = 1 ORDER BY `order`');
```

**Missing columns in the `pages` table:**
- `language`
- `icon`
- `plans_ids`
- `is_published`

When the query failed (returned `false`), the code attempted to call `fetch_object()` on a boolean, causing a fatal error:
```
Fatal error: Call to a member function fetch_object() on bool in Page.php on line 35
```

---

## Why It Was Hard to Diagnose
1. The 500.php error handler (loaded when DEBUG=0) displayed a pretty error page instead of the actual PHP error
2. Initial investigation focused on autoloader/trait issues which were red herrings
3. Fix scripts that loaded init.php also triggered the same 500 error handler

---

## Solution
Added the missing columns to the `pages` table using a standalone SQL fix:

```sql
ALTER TABLE `pages` ADD COLUMN `language` varchar(32) DEFAULT NULL;
ALTER TABLE `pages` ADD COLUMN `icon` varchar(64) DEFAULT NULL;
ALTER TABLE `pages` ADD COLUMN `plans_ids` text DEFAULT NULL;
ALTER TABLE `pages` ADD COLUMN `is_published` tinyint(4) NOT NULL DEFAULT 1;
```

---

## Fix Scripts Created
The following scripts were created to diagnose and fix the issue:

1. **standalone_fix.php** - Bypasses init.php, connects directly to database, adds missing columns
2. **comprehensive_check.php** - Tests all application components step by step
3. **final_debug.php** - Tests the complete application flow with error reporting

---

## How to Prevent This in the Future

### 1. Database Migration Management
- Always include SQL migration scripts when adding new columns to the database
- Create a `migrations/` folder to track database changes
- Document required database changes in PR descriptions

### 2. Pre-Deployment Checklist
- [ ] Check if any new database columns were added in the code
- [ ] Run database migrations on production BEFORE deploying code
- [ ] Test on staging environment with production database schema

### 3. Error Handling Improvements
- Consider logging actual errors to a file even when DEBUG=0
- Add database query error handling in critical models

---

## Files Modified During Fix

| File | Change |
|------|--------|
| `app/models/User.php` | Added null coalescing for plan_settings, billing, preferences |
| `app/core/App.php` | Added null checks for various settings |
| `app/core/Language.php` | Added isset checks for language settings |
| `themes/altum/views/wrapper.php` | Fixed isset check for export->pdf |
| `themes/altum/views/basic_wrapper.php` | Fixed isset check for export->pdf |
| `themes/altum/views/app_wrapper.php` | Fixed isset check for export->pdf |
| `themes/altum/views/admin/wrapper.php` | Fixed isset check for export->pdf |

---

## Database Changes Applied

```sql
-- Pages table: Added missing columns
ALTER TABLE `pages` ADD COLUMN `language` varchar(32) DEFAULT NULL;
ALTER TABLE `pages` ADD COLUMN `icon` varchar(64) DEFAULT NULL;
ALTER TABLE `pages` ADD COLUMN `plans_ids` text DEFAULT NULL;
ALTER TABLE `pages` ADD COLUMN `is_published` tinyint(4) NOT NULL DEFAULT 1;
```

---

## Cleanup Required
Delete these diagnostic scripts from production after confirming the site works:
- `standalone_fix.php`
- `comprehensive_check.php`
- `final_debug.php`
- `fix_opcache.php`
- `fix_pages_table.php`
- `test_real_init.php`
- `test_autoloader.php`
- `diagnose.php`
- `debug_login_redirect.php`
- `check_all_tables.php`

---

## Key Lessons

1. **Always ensure database schema matches code expectations before deployment.** When code references new database columns, those columns must exist in the production database before the code is deployed.

2. **Clear browser/server cache after fixing errors.** 500 error responses can be cached by browsers or CDNs (CloudFlare, Varnish, etc.), causing the error to persist even after the fix is applied. Always clear cache or test in incognito mode after applying fixes.
