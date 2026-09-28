# DIST-318 - Distribution Standalone Separation Stage 16

## Focus
Language, permission, menu and route ownership finalization foundation.

## Added
- `Support/DistributionPermissionRegistry.php`
- `Support/DistributionMenuRegistry.php`
- `Support/DistributionRouteRegistry.php`

## Updated
- `Config/config.php`
  - Added Distribution-owned route-name map.
  - Added Distribution-owned permission map.
  - Added Distribution-owned menu map.
- `Providers/DistributionServiceProvider.php`
  - Keeps DIST-317 service bindings.
  - Adds route, menu and permission registry bindings.

## Safety note
This stage is non-invasive. It does not change currently working routes, views,
menus, permissions, sales order logic, loading logic, print logic or payment
posting. It only creates module-owned registries so the remaining scattered
main-system menu/permission/route references can be moved in controlled smaller
steps.

## Next stage
DIST-319: final utility ownership and remaining helper cleanup.
