8051 - Idle Banner safe integration correction
Date: 06 Sep 2026

Reason for this correction
--------------------------
The previous 8051 implementation registered InjectIdleBanner in Laravel's global
web middleware group and modified the completed HTML response by inserting the
idle runtime before </body>.

This is unsafe for this application because some legacy/report screens return
complex HTML and contain large inline JavaScript/print fragments. Post-processing
those responses can split script/HTML content, which can make JavaScript appear as
visible text and prevent later JavaScript such as date-range controls from running.

What changed
------------
1. Providers/SuperadminServiceProvider.php
   - Removed global web-middleware registration for idle-banner injection.
   - Adds the idle-banner runtime through the existing Blade @stack('javascript')
     while layouts.partials.javascripts is rendered.
   - Skips AJAX/JSON responses.
   - Adds the runtime once per request only.

2. Http/Middleware/InjectIdleBanner.php
   - Converted to a no-op/pass-through compatibility middleware.
   - It no longer reads or modifies response HTML.
   - Retained so an older cached middleware list cannot break pages during rollout.

Unchanged
---------
- Standalone Super Admin > Banners Management page.
- banner_idle_settings database table and saved settings.
- tenant/business targeting.
- central + tenant banner selection.
- banner rotation/display-duration logic.
- idle-banner JSON payload endpoint.
- BusinessController.php and Manage New.
- payment, stock, accounting, settlement and report business logic.

Database
--------
No SQL or migration is required for this correction.
Do NOT recreate/reset banner_idle_settings.

Deploy
------
Copy the two files into Modules/Superadmin preserving their paths, then run:

php artisan optimize:clear

Recommended checks
------------------
1. Hard refresh the browser (Ctrl+F5).
2. Open a page where JavaScript text previously appeared. Confirm no code is shown.
3. Test that page's date/date-range control and Apply action.
4. Test a second report page with a date range.
5. Temporarily set idle banner time to 1 minute and confirm the banner still appears.
6. Move mouse/press a key and confirm the idle banner closes normally.
