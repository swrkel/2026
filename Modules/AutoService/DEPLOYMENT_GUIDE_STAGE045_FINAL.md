# AutoService Stage 045 Deployment Guide

## 1. Backup first
Take backups of:
- Application files
- Tenant database(s)
- Central/master database

## 2. Upload files
Replace/merge the `Modules/AutoService` folder from this package into the server.

## 3. Run SQL
Recommended order:
1. Run incremental SQL files in sequence if the server is not updated stage by stage.
2. Or run the Stage 045 master SQL if this is a fresh/clean AutoService deployment.
3. Run tenant SQL on every tenant DB that will use AutoService.
4. Run central vehicle registry SQL only on the central DB where applicable.

## 4. Clear Laravel cache
Run your usual deployment cache clear process:
- route cache clear
- config cache clear
- view cache clear
- application cache clear

## 5. Validate from UI
Check:
- Sidebar visibility
- Permissions
- Dashboard
- Workshop command centre
- Job card workflow
- Estimate workflow
- Parts and labour
- Billing/delivery
- Customer portal
- Vehicle history
- Reports
- Fleet/AMC features
- Audit/diagnostics pages

## 6. Server testing feedback
Record issues with:
- URL
- Screenshot
- Error log
- Logged-in user/business/location
- Steps to reproduce

Further fixes should be delivered as stabilization packages only.
