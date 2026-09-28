# Petro Direct-New Clean-Room Release

This release creates Petro Direct-New from scratch. Legacy Petro Direct was used only to study workflow names, navigation, screen organization and operational expectations. No legacy Petro Direct controller, model, service, view, JavaScript or CSS file is called at runtime.

## Primary pages

1. Dashboard
2. Direct Settlement
3. List Direct Settlements
4. Pumper Management
5. Reports

## Pumper Management

The internal page contains the legacy-equivalent tab set: Pump Operators, Pumper Excess / Shortage Payments, Pumper Day Entries, Shift Summary, Payment Summary, Meters with Payments, Daily Pump Status, Close Shift, Current Meter and Unload Stock.

All operational records are stored in `pdirectnew_` tables and scoped to the active business and permitted location.
