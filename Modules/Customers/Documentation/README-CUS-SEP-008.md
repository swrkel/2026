# CUS_SEP_008 - Customer Settings & Configuration Separation

This package separates Customer settings/configuration ownership into `Modules/Customers`.

Included:
- Customer Numbering
- Customer Defaults
- Customer Preferences
- Dealer Portal Settings
- Customer Credit Settings
- Customer Notification Settings

The settings are saved under `business.common_settings.customers_module` to avoid new table dependencies and to keep the change safe for existing tenants.

No Petro, PetroPD, Finance, Contact, Dealer Portal transaction, ledger or settlement logic was changed.
