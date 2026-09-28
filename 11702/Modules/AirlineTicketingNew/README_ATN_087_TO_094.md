# ATN-087 to ATN-094 — 8 Section Parcel

## ATN-087 Performance Optimization
- Shared business/location/store query scoping
- Date-range query helpers
- Safe pagination limits

## ATN-088 Cache Engine
- Business-specific cache keys
- Lookup caching
- Cache invalidation helpers

## ATN-089 Queue Optimization
- Queue execution history
- Runtime and failure tracking
- Structured payload storage

## ATN-090 Diagnostics
- Required table checks
- Environment information
- Business record counts
- Diagnostics administration page

## ATN-091 Health Monitoring
- Stuck notifications
- Stuck workflows
- Failed queue executions
- Health incident generation

## ATN-092 Upgrade Manager
- Module migration execution
- Cache cleanup
- Upgrade history and failure tracking

## ATN-093 Installer
- Module migration installer
- Installation status
- Installation Artisan command

## ATN-094 Deployment Utilities
- PHP and environment validation
- Writable directory checks
- App-key validation
- Performance indexes
- Deployment validation Artisan command

## Installation
1. Merge over ATN-001 through ATN-086.
2. Include `Routes/performance-admin.php`.
3. Register the installer and deployment console commands.
4. Run the migration or master SQL.
5. Assign the included permissions.
6. Publish the CSS/JS and merge language files.
7. Run `php artisan optimize:clear`.
