# Petro Direct-New Installation

Petro Direct-New is a clean-room module. It uses only `pdirectnew_` tables for its own operational records and uses the application's current layout and shared master data through dedicated services.

## Upload

Extract the release ZIP in the Laravel project root, the folder containing `artisan`. The final module path must be:

`Modules/PetroDirectNew/module.json`

Do not extract it inside another module folder.

## Enable and clear caches

```bash
php artisan module:enable PetroDirectNew
php artisan optimize:clear
composer dump-autoload -o
```

## Tenant database installation

Run this file in every tenant database:

`Modules/PetroDirectNew/SQL/00_MASTER_INSTALL_PETRO_DIRECT_NEW.sql`

For an earlier preview installation, also run:

`Modules/PetroDirectNew/SQL/04_UPGRADE_EXISTING_INSTALLATION.sql`

Verify with:

`Modules/PetroDirectNew/SQL/03_VERIFY_INSTALLATION.sql`

Assign permissions through the role page. The optional reviewed Admin assignment script is:

`Modules/PetroDirectNew/SQL/05_OPTIONAL_ASSIGN_PERMISSIONS_TO_ADMIN_ROLES.sql`

Never run `99_UNINSTALL_PETRO_DIRECT_NEW_DANGER.sql` during installation.
