# Acceptance Test Checklist

1. Enable StockTakingNew and clear caches.
2. Confirm the sidebar entry is visible only for an enabled business.
3. Confirm a disabled business receives 403/404 through the application’s global sidebar-access middleware.
4. Create sessions for two different businesses and confirm neither can access the other’s IDs.
5. Test location-level stock and store-level stock separately.
6. Prepare snapshot and confirm all active stock-enabled products appear.
7. Test blind count: system quantity is hidden on the count entry screen.
8. Enter partial counts, save, refresh, and confirm values persist.
9. Create a variance and verify recount is required using configured thresholds.
10. Recount, submit, approve and post.
11. Confirm `stk_inventory_movements` has one record per posted line.
12. When shared posting is enabled, confirm `variation_location_details` or `variation_store_details` matches the final count.
13. Import a CSV count file and verify success/failure totals.
14. Print and download each document type.
15. Generate SMS, email and WhatsApp secure links; confirm expiry and download permissions.
16. Run variance/progress/accuracy/audit reports with date, location and store filters.
17. Create each schedule frequency and run `php artisan stock-taking-new:run-schedules` in a tenant context.
18. Confirm legacy `/stocktaking` pages and tables remain unchanged.
