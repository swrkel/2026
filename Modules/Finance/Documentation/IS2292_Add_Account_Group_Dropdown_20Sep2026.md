# IS2292 – Finance Module – Add Accounts – 20 Sep 2026

## Reported issue
Finance -> List Accounts -> Add Account:
After selecting Account Type, Account Group could not be selected and the dropdown showed "Unable to load Account Groups".

## Root cause in the supplied code
The Add Account modal was already loaded from Finance, but changing Account Type triggered a second AJAX request to `/finance/account-groups/by-type/{id}`. That extra dependency could fail or be intercepted by a legacy/stale Finance route, leaving the dropdown unusable. The dropdown therefore depended on a separate network/route round-trip even though all required Finance data is available when the modal opens.

## Fix
1. The Add Account modal now receives a compact Account Group map when the modal itself is loaded.
2. Selecting Account Type switches Account Groups locally and instantly; no second AJAX request is required.
3. The same protection is applied to Edit Account so changing Account Type there cannot recreate the same issue.
4. Child Account Types inherit Account Groups from their parent.
5. Selecting a top-level Account Type also includes Account Groups configured on its immediate child Account Types, matching existing Finance data structures.
6. Existing routes/controllers are left unchanged; the Add/Edit forms no longer depend on the Account Group lookup route for normal operation.

## Preserved functionality
- Account Group changes remain independent. Account Number behavior is superseded by the 20 Sep 2026 Super Admin source-of-truth implementation documented in `SUPERADMIN_ACCOUNT_NUMBER_SOURCE_20SEP2026.md`.
- One-click Add Account Save remains unchanged.
- S769 Deposit calendar and widened Deposit popup remain unchanged.
- Existing Account Group maintenance and Account Settings remain unchanged.

## Database
No SQL or migration is required for IS2292.

## Deployment
Replace the Finance module (or changed files) and run:

    php artisan optimize:clear

Then hard refresh the browser once (Ctrl+F5).
