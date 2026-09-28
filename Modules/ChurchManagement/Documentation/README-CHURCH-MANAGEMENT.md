# Church Management — new module

**Dashboard, Members, Families, Donations, Attendance, Events, Settings.**

Built to the ERP Dashboard Standard used by the POS Dashboard — hero band, KPI
tiles, cards, toolbar, table and button treatments, same palette and spacing.

---

## What "standalone" means here

You confirmed the module uses the **ERP login and session**, so staff sign in
once and reach Church Management from the same sidebar as everything else.

Everything below that line is the module's own:

- Its own tables, all prefixed `chc_`
- Its own controllers, routes, views, translations and configuration
- Its own copy of the ERP standard styles
- **No reference to any other module.** Removing POS, Customers, HelpGuide or
  any other module cannot change how this one behaves or looks.

The single point of contact with the host application is
`Http/Controllers/Concerns/ChurchTenantContext.php`, which reads the signed-in
user and the business/location from the session. If the module is ever moved to
its own authentication, that is the one file to change.

## Files

```
Modules/ChurchManagement/
├── module.json, composer.json, start.php
├── Config/config.php                    route prefix, table prefix, code prefixes
├── Config/menu.php                      sidebar entries
├── Providers/ChurchManagementServiceProvider.php
├── Routes/web.php
├── Http/Controllers/
│   ├── Concerns/ChurchTenantContext.php business + location scoping
│   ├── DashboardController.php
│   ├── MemberController.php
│   ├── FamilyController.php
│   └── SettingsController.php
├── Database/Migrations/2026_09_01_000001_create_church_management_tables.php
├── Database/SQL/CHURCH_MANAGEMENT_MASTER_INSTALL.sql
├── Resources/views/
│   ├── layouts/app.blade.php
│   ├── partials/erp-standard-styles.blade.php
│   ├── partials/nav.blade.php
│   ├── partials/install_notice.blade.php
│   ├── dashboard/index.blade.php
│   ├── members/index.blade.php
│   ├── families/index.blade.php
│   └── settings/index.blade.php
└── Resources/lang/en/lang.php
```

## Schema

The module shares the **existing tenant database** rather than taking its own
connection. Three new tables, all prefixed `chc_`, which is what keeps them
apart from the hundred-odd other modules living beside them:

**`chc_members`** — the congregation roll. Names, contact details, family link,
gender, dates of birth / joining / baptism / confirmation, occupation, notes,
and a membership status.

`membership_status` is `member`, `visitor`, `inactive` or `departed`. It is a
status rather than a deletion because a congregation's history matters and a
departed member may return.

`full_name` is stored rather than assembled on read, so the list can sort and
search on one indexed column instead of a `CONCAT` no index can serve. It is
rebuilt on every write, so it cannot drift from the two parts it comes from.

**`chc_families`** — households, with an optional head chosen from members.

**`chc_settings`** — key/value per business, so a later phase can add a setting
without a migration on every tenant.

The prefix is set once in `Config/config.php`. Controllers never write a table
name directly — they call `ChurchTenantContext::table('members')`, which
prepends it. Changing the value moves the whole module, though note it does not
rename tables that already exist.

**No foreign keys**, matching the other vertical modules here. Relationships are
enforced in application code, so installing onto a tenant carrying legacy or
partial data cannot fail on a constraint.

## Shared core data

You confirmed the module may use the common Business, Users, Roles and Locations
data, and it now does - but only through the shared tables, never through
another module.

**Permissions.** `Config/module_permissions.php` declares four page keys, which
is what the Role screen and Super Admin > Manage build their checkboxes from.
Every route is gated with Laravel's own `can:` middleware, so it works with the
`spatie/laravel-permission` setup already installed without the module knowing
anything about it.

This closed a real gap. Before, the routes were gated on `auth` alone, so any
signed-in user of the business could open the congregation roll - names,
addresses, phone numbers, dates of birth - by typing a URL. Write actions carry
the permission of the page they belong to: a user who may not see the roll must
not be able to post to it either.

**Locations.** A member can be assigned a business location, and the dropdown
appears only where the business actually has locations - a single-site
congregation is not asked to choose between one option.

Location *filtering* is opt-in via `churchmanagement.scope_by_location`, and is
off by default. A congregation is usually one site even when the business runs
several, so a member belongs to the business rather than a branch; filtering by
default would hide every member from a user whose session carries a location,
which is most of them. Businesses genuinely running separate congregations per
location switch it on, and then each location sees only its own roll.

**Added By.** The member list shows who created each record, resolved from
`users` in one query for the whole page rather than one per row.

## Decisions worth knowing

**Deleting a family does not delete its members.** Their `family_id` is cleared
so they stay on the roll, unattached, and can be moved to another household.

**Member and family codes are generated server-side** as `CM-000001` /
`FM-000001`, derived from the highest existing code rather than a row count —
counting repeats a code as soon as anything is deleted.

**Add and Edit share one form.** The Edit buttons fill it and switch it to the
update route. Two forms would mean two sets of fields and two sets of validation
rules to keep in step, and they always drift.

**Row actions sit on one line.** `nowrap` on both the cell and the group, with
`display:contents` on the delete form so its button takes part in the group's
spacing. This is the same fix as the Help Guide article list.

**Every page renders when the tables are absent**, showing an install notice
rather than a stack trace, so a tenant that has not run the SQL gets an
instruction.

**A business_id is never accepted from a form.** It is stamped on write from the
session, and updates cannot move a row to another business.

## Installation

**1. Deploy the module**

```bash
cd /home/nivasa/public_html/Modules && unzip -o /path/to/ChurchManagement.zip
```

**2. Create the tables — per tenant database**

Either the SQL, which lets you inspect each tenant first:

```
Modules/ChurchManagement/Database/SQL/CHURCH_MANAGEMENT_MASTER_INSTALL.sql
```

Select the tenant database in phpMyAdmin's left-hand panel first, or edit the
`USE` line at the top. Safe to re-run.

Or the migration:

```bash
php artisan module:migrate ChurchManagement
```

Use one or the other — both are guarded, but there is no reason to run both.

**3. Enable the module and clear caches**

```bash
php artisan module:enable ChurchManagement
php artisan route:clear && php artisan view:clear && php artisan config:clear
```

**4. Open it**

`/church-management`

The prefix comes from `Config/config.php`, so the whole module moves with one
setting.

## Testing

- [ ] `/church-management` opens with the hero band and four KPI tiles
- [ ] Before installing the tables, pages show the install notice, not a 500
- [ ] Tabs move between Dashboard, Members, Families and Settings, with the
      current one highlighted
- [ ] Add a member — code generates as `CM-000001`, the row appears in the list
- [ ] Edit that member — the form fills, the title changes, saving updates it
- [ ] Cancel Edit returns the form to Add
- [ ] Search by name, code, phone and email each narrow the list
- [ ] The status and family filters work, including Reset
- [ ] Add a family, set its head from the member list
- [ ] The family's member count links through to that family's members
- [ ] Delete a family — its members remain on the roll without a family
- [ ] Delete a member — the row disappears from the list
- [ ] Dashboard tiles show the right counts and each links to its filtered list
- [ ] A member with a birthday this month appears in the birthdays panel
- [ ] Settings save and persist
- [ ] Row action buttons sit on ONE line
- [ ] Nothing in another module has changed appearance

## Donations, Attendance and Events

**Donations** — tithes, offerings and gifts, with donation types the
congregation defines itself (a table, not a fixed list, and addable from the
donations page so a treasurer mid-entry is not sent to a settings screen).

`member_id` is nullable on purpose: a collection plate is anonymous. Where the
giver is unknown, a written name goes in Donor Name without creating a member
record for a one-off visitor; with neither, it is recorded as *Anonymous* rather
than blank, so a printed list never has an empty donor column.

Amounts are `DECIMAL(22,4)`, matching the money columns elsewhere in the
application — a float would lose cents on an annual total, which is exactly the
number a treasurer checks. They are displayed at the business's own currency
precision.

The list defaults to the current month, and the period total is taken from the
filtered query *before* pagination. Summing the visible rows would total only
the current page — a wrong number that looks plausible.

**Attendance** — services, and the register taken against them. Congregations
record attendance one of two ways and both are supported: a **headcount** on the
service for a room that is counted, or a **register** of who came. Neither is
imposed; a service can carry either or both. Forcing per-member rows would make
the feature unusable for a congregation that only counts the room.

An unmarked member is stored as *no row*, not as absent. "We did not take their
name" and "they were not there" are different facts, and conflating them would
overstate absence.

`(service_id, member_id)` is unique, so a double-submitted register corrects the
rows rather than duplicating them — without it every attendance figure
downstream would drift.

Deleting a service removes its register with it: an attendance row is a fact
about a service, not a record with a life of its own.

**Events** — the church calendar. Times and end dates are optional so a
whole-day or multi-day event needs no invented ones, and `end_date` validates as
*after or equal*, since an event may start and end on the same day.

The list defaults to **upcoming**, ascending — a calendar is consulted to find
what is next far more often than to browse what has passed. Past events sort
descending, most recent first. Either the other way round buries what matters.

## Not yet built

Groups and ministries, sacrament certificates, communications, and reporting or
exports.

The foundation here — scoping trait, layout, design partial, permission
manifest, install pattern — is what those would build on.
