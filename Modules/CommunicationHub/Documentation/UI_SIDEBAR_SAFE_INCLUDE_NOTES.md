# Communication Hub Sidebar Safe Include Notes

The Communication Hub menu is now owned by the module:

- `Modules/CommunicationHub/Config/menu.php`
- `Modules/CommunicationHub/Resources/views/partials/sidebar.blade.php`

To show it in the ERP sidebar, the preferred safe integration is only one include line in the main sidebar layout:

```php
@includeIf('communicationhub::partials.sidebar')
```

Do not hardcode Communication Hub submenu items inside the main sidebar. Future menu changes should be made only inside this module.

This package does not replace the main sidebar file to avoid breaking existing menus.
