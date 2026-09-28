# Membership New - Membership Settings Tabs - 24 Sep 2026

Added to **Membership New > Membership Settings** while preserving the existing Regions tab:

1. Membership Types
2. Membership Status
3. Renewal Period
   - Daily
   - Weekly
   - Monthly
   - Annually
4. Registration / Renewal Amount
   - Amount is optional.

Each tab allows the user to add a setting and immediately shows the saved entries on the same page. Saves use the existing AJAX/instant-save pattern, and Added By stores/displays the actual user's name only.

Database table added: `mn_setting_options`.

The table is business-scoped and uses the application's active database connection, so it supports businesses hosted in the central database as well as businesses hosted in tenant databases.
