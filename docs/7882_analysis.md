# 7882 - IS1002 - 7869 - Reports – Customized 16 Mar 2026
**Date:** 21 Mar 2026
**Branch:** engr_alex_7889_task
**Status:** In Progress

---

## Issue 1 — Contact Module / Vehicle No Page: Add "Sub Customer" Field

**Location:** `Contact Module / Vehicle No page` → `resources/views/contact/create.blade.php` (around line 580–595)
**Status:** 🔲 PENDING

### What to do
- Add a new "Sub Customer" field (select2 dropdown) immediately after the existing Vehicle No field
- Dropdown should list sub-customers (contacts with a specific type/flag)
- Type and auto-filter (select2 search) required
- Plus icon to add a new customer inline (if not found in dropdown)
- On new customer add: show instantly in dropdown + save to DB
- Conditional display same as Vehicle No: `$customerSettings->vehicle_no == 1`

### Files to change
- `resources/views/contact/create.blade.php` — add Sub Customer field after Vehicle No block
- `app/Http/Controllers/ContactController.php` — handle sub_customer_id save/load
- Migration: new column `sub_customer_id` on contacts table (or separate table)
- Routes: new AJAX endpoint to fetch sub-customers list

---

## Issue 2 — Rename Sidebar Menu "129 Reports" → "LIOC Statement"

**Location:** `resources/views/layouts/partials/sidebar.blade.php` (line 2047)
**Status:** ✅ DONE

### What to do
- Change the sidebar sub-menu item text from `@lang('lang_v1.129_reports')` to `"LIOC Statement"`

### Files to change
- `resources/views/layouts/partials/sidebar.blade.php` (line 2047)
- `resources/lang/en/lang_v1.php` — optionally update `129_reports` key value

---

## Issue 3 — LIOC Statement: New Tab "Prefix & Numbers" (Last Tab)

**Location:** `Modules/ReportsCustomized/Resources/views/index.blade.php`
**Status:** 🔲 PENDING

### What to do
Add a new last tab "Prefix & Numbers" with:
- **Date** — auto-show current date & time, not editable
- **Prefix** — input
- **Statement Starting Number** — input
- **Constant Value** — input
- **Description Constant Details** — textarea
- **List below (table columns):**
  - Action (Edit — permitted users only)
  - Date & Time
  - Prefix
  - Statement Starting Number
  - Constant Value
  - Description Constant Details
  - Added User (logged-in user name)

### Files to change
- `Modules/ReportsCustomized/Resources/views/index.blade.php` — add tab + tab pane
- `Modules/ReportsCustomized/Http/Controllers/ReportsCustomizedSettingsController.php` — CRUD for Prefix & Numbers
- `Modules/ReportsCustomized/Entities/LiocReportCustomized.php` — verify/add fields
- Migration if new fields needed on `reports_customized_lioc_report_customizeds` table

---

## Issue 4 — "Add LIOC Statement" Tab: Rename, Fix Qty, Remove Unwanted Sections, Fix Totals

**Location:** `Modules/ReportsCustomized/Resources/views/index.blade.php` (tab 1: "Report Customized Details")
**Status:** 🔲 PENDING

### What to do
- Rename first tab from "Report Customized Details" → "Add LIOC Statement"
- **Qty column:** show without any column separation line (no border separators)
- **Description column:** show same details as PDF image (no change to content)
- **Remove** unwanted sections below the table in the current view
- **Total row:** show totals in the correct columns as per the PDF image
  - BILL REF shows: `Fuel Credit KFM 2026/006 (102455)`
  - Format: `[Prefix] [Number] ([Constant Value])`
  - Period: `DDMMYYYY To DDMMYYYY`

### Files to change
- `Modules/ReportsCustomized/Resources/views/index.blade.php`
- `Modules/ReportsCustomized/Resources/views/statement/show.blade.php`
- `Modules/ReportsCustomized/Resources/views/partials/sales_table_rows.blade.php`

---

## Issue 5 — LIOC Statement: New Tab "List LIOC Statements"

**Location:** `Modules/ReportsCustomized/Resources/views/index.blade.php`
**Status:** 🔲 PENDING

### What to do
- Add new tab "List LIOC Statements" (2nd tab, after "Add LIOC Statement")
- Standard toolbar: Export to CSV, Export to Excel, Column Visibility, Export to PDF, Print
- Copy design/functionality from `resources/views/customer_statement/partials/list_customer_statements.blade.php`
- Data source: "Add LIOC Statements" records

**Filters required:**
- Date Range (system standard daterangepicker, current month default)
- Statement No (dropdown, select2, all by default)
- Customer (dropdown, select2, all by default)
- Vehicle Order No (dropdown, select2, all by default)

### Files to change
- `Modules/ReportsCustomized/Resources/views/index.blade.php` — add new tab
- New partial: `Modules/ReportsCustomized/Resources/views/partials/list_lioc_statements.blade.php`
- `Modules/ReportsCustomized/Http/Controllers/ReportsCustomizedController.php` — data endpoint

---

## Issue 6 — Report Footer on Every Page

**Location:** All LIOC Statement pages/reports
**Status:** 🔲 PENDING

### What to do
- Show "Report Footer" at the bottom of every page as set in:
  `Super Admin Module / Super Admin Settings / Application Settings / Report footer`

### Files to change
- `Modules/ReportsCustomized/Resources/views/statement/show.blade.php`
- `Modules/ReportsCustomized/Resources/views/index.blade.php`
- Any print/export views

---

## Issue 7 — Super Admin / All Business / Manage Page: New "Customized Reports" Section

**Location:** `Modules/Superadmin/Resources/views/business/manage.blade.php`
**Status:** 🔲 PENDING

### What to do
- Add new section "Customized Reports" — same design/functionality as "Manufacturing Module" section
- Separate permission toggle for each tab page

### Files to change
- `Modules/Superadmin/Resources/views/business/manage.blade.php`
- `Modules/Superadmin/Http/Controllers/BusinessController.php` — handle save of customized_reports permission
- Business model / permissions table (if new column needed)

---

## Issue 8 — User Management / Roles: New "Customized Reports" Section

**Location:** `resources/views/role/create.blade.php` and `resources/views/role/edit.blade.php`
**Status:** 🔲 PENDING

### What to do
- Add new "Customized Reports" permissions section in both Add Role and Edit Role pages
- Permissions to include:
  - Edit (only permitted users can edit "List LIOC Statements")
- Show this section only when "Customized Reports" module is enabled in Super Admin / All Business / Manage

### Files to change
- `resources/views/role/create.blade.php`
- `resources/views/role/edit.blade.php`
- `app/Http/Controllers/RoleController.php` — handle new permissions

---

## Implementation Order

| # | Issue | Priority | Complexity |
|---|-------|----------|------------|
| 2 | Rename sidebar "129 Reports" → "LIOC Statement" | High | Low |
| 4 | Rename first tab + fix Qty/Totals/Sections | High | Medium |
| 3 | New tab "Prefix & Numbers" | High | Medium |
| 5 | New tab "List LIOC Statements" | High | High |
| 6 | Report footer on every page | Medium | Low |
| 1 | Sub Customer field on Vehicle No page | Medium | High |
| 7 | Super Admin Customized Reports section | Medium | Medium |
| 8 | Roles Customized Reports permissions | Medium | Medium |

---

## Change Log

| Date | Issue | Status | Notes |
|------|-------|--------|-------|
| 2026-03-21 | Issue 2 | ✅ DONE | Sidebar renamed "129 Reports" → "LIOC Statement" |
| 2026-03-21 | Issue 4 | ✅ DONE | Tab 1 renamed "Add LIOC Statement"; BILL REF format fixed; column order fixed (S/No, Name, Amount, Order Date, Vehicle/Order No, Description); Amount column no right-border; date formatted dd.mm.yyyy; total row updated; unwanted duplicate tab-content div removed |
| 2026-03-21 | Issue 3 | ✅ DONE | Tab renamed "Prefix & Numbers" (last tab); migration added description_constant_details + created_by; create/edit forms updated; DataTable 7 columns (Action, Date & Time, Prefix, Statement Starting Number, Constant Value, Description Constant Details, Added User); edit form fix to use update route; store/update controller updated |
| 2026-03-21 | Issue 6 | ✅ DONE | Report footer shown from session('business.report_footer') in statement/show.blade.php |
