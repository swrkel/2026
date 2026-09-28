Distribution New - Stage 26 Server Feedback Stabilization

Upload this parcel after FULL_REPLACEMENT_DISNEW_001_TO_025.
Run the stage SQL or Laravel migration on every tenant database that uses Distribution New.
Then open:
/distribution-new/stabilization

This screen checks:
1. Module service provider status
2. Route visibility
3. Sidebar/menu registration
4. Permission registration
5. Business and tenant filtering
6. Required disnew_ tables
7. Migration history
8. Public assets
9. Language files
10. Customer/SMS bridge availability

Use this package before reporting server feedback so the first round of testing is cleaner.
