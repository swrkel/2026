# DISNEW 018 Scanner Testing Checklist

1. Confirm sidebar shows Distribution New > Scanner Dashboard.
2. Open Barcode Loading and scan/type a barcode then press Enter.
3. Confirm accepted scans appear in the scan table.
4. Confirm unknown barcode creates exception scan, not a crash.
5. Create Bin Location and move product into bin using scanner flow.
6. Run warehouse / vehicle stock verification.
7. Confirm raw SQL can run on tenant DB without fixed database name.
8. Confirm rollback SQL only removes DISNEW_018 tables.
