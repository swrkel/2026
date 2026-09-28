# STN_038 - AI Replenishment Review

This parcel adds a controlled review layer for AI-assisted replenishment suggestions.

## Important
- This does not duplicate the standalone Products module.
- Product ID/SKU/name are only referenced for display and review.
- Recommendations must be approved before they can be converted into transfer work.
- Every approve/reject/return action is logged in `stn_ai_recommendation_logs`.

## SQL
Run tenant database SQL only:
1. STN_038_CREATE_TABLES.sql
2. STN_038_ALTER_TABLES.sql
3. STN_038_INSERT_PERMISSIONS.sql
