# Auto Service Stage 042 Deployment Checklist

1. Replace the `Modules/AutoService` folder with the package contents.
2. Run `AutoService/SQL/43_AUTOSERVICE_STAGE042_DEPLOYMENT_DIAGNOSTICS.sql` on each tenant database.
3. Clear Laravel caches if required: route/config/view cache.
4. Open Auto Service > More > Deployment Diagnostics.
5. Confirm missing routes = 0 and missing tables = 0.
6. Test one full job flow: reception, estimate, job card, parts/labour, QC, billing, payment, delivery.
7. Test customer portal: current status, bill, history, parts/accessories history, documents, alerts.
8. Record any server issue in Stage 041 Stabilization Centre.
