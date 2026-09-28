MPCS - F15 DAILY REPORT NEW
Date: 1 August 2026

PAGE
- URL: /mpcs/F15-New
- Sidebar: MPCS > F 15 Daily Report - New
- Permission: Uses the existing f15_form permission.
- The existing /mpcs/F15 page is not removed or changed.

INSTALLATION
1. Replace/upload the supplied MPCS module.
2. Run the Laravel migrations in every tenant database:
      php artisan migrate
   OR run this raw SQL file in every tenant database:
      MPCS/Database/SQL/MASTER_MPCS_F15_DAILY_REPORT_NEW.sql
3. Clear application caches:
      php artisan optimize:clear

REPORT RULES IMPLEMENTED
- Today shows transactions for the selected date and selected business location.
- Previous Day shows the previous calendar day's saved/calculated Total Value.
- When F22 is saved on the selected date, Previous Day is reset to 0.00.
- F18 Oil/Gas purchases are separated by the Lubricant/Oil and Gas product categories.
- Oil/Gas purchases from F16/F16A are separated by the same categories.
- Price Increment and Price Reduction come from F17 for the selected date.
- Oil/Gas opening stock is valued at sale price; selected-date F22 is authoritative.
- Oil/Gas Cash Sale = Total Sale - Credit Sale.
- Manual rows are saved for Changes, Changes - 18 F, Damaged, Others and Total Return.
- All subtotal, total, balance and grand-total rows calculate automatically in every column.
- The page includes Save Report and A4 Print Document functions.

DATABASE
New table: mpcs_f15_daily_reports
The table is business-, location- and date-specific and stores manual values, signatures,
notes and the saved Total Value snapshot used by the next day's Previous Day column.
