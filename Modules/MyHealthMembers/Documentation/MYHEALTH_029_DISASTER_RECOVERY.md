# MYHEALTH_029 – Disaster Recovery & Business Continuity

This release adds a standalone Disaster Recovery area inside the My Health module.

Included:
- Disaster Recovery dashboard
- Backup centre
- Restore centre
- System health monitoring
- Recovery test register foundation
- Disaster recovery reports
- Sidebar/menu entries
- Separate routes, controllers, service, entities, views and migration

Run after replacing files:

```bash
php artisan optimize:clear
php artisan migrate
```
