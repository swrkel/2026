Stock Transfer-New Stage 015 Production Support

This stage adds support screens used after deployment/testing:
1. Diagnostics dashboard
2. Tenant scope verification
3. Permission/route/asset checklist
4. Safe repair preview screen
5. Guarded repair service for missing settings/permissions/cache flags

Important:
- Run the SQL file on every tenant database where the module is enabled.
- Product lookup continues through the existing standalone Products module bridge.
- Repair actions are intentionally guarded and should be used only by authorized users.
