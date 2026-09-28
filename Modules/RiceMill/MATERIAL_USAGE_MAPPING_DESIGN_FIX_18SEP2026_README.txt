Rice Mill - Material Usage Mapping Design Correction - 18 Sep 2026

Changes:
1. Settings > Material Usage Mapping now follows the approved reference layout only.
2. Packaging Material rows are read-only display fields with the actual material name/unit visible.
3. Usage per Bag fields remain aligned one-to-one with Packaging Material rows.
4. Save Mapping remains at the lower-right of the mapping area.
5. The Saved Material Usage table was removed from the Settings tab because it was not part of the approved design.
6. Existing mapping data and standalone Material Usage Mapping functionality are not deleted or changed.
7. No database/SQL changes are required.

After deployment:
php artisan optimize:clear
php artisan view:clear
