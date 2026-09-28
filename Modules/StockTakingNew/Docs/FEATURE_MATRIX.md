# Feature Matrix

| Area | Included |
|---|---|
| Tenant database isolation | Yes — current tenant connection |
| Business isolation | Yes — every record includes and validates `business_id` |
| Location isolation | Yes |
| Store isolation | Yes |
| Legacy Stocktaking dependency | None |
| Module-owned tables | 15, all `stk_` |
| Count methods | Full, Cycle, Spot |
| Count modes | Blind, Open |
| Product scopes | All, Category, Brand, Explicit Product IDs, Templates |
| Recount engine | Threshold-based quantity/value recount |
| Approval | Submit, Approve, Reject, Post |
| Inventory posting | Optional shared inventory update plus mandatory module ledger |
| Reports | Variance, Progress, Accuracy, Audit Trail |
| Export | CSV variance export |
| Documents | Print, PDF stream, PDF download |
| Sharing | Secure expiring links, SMS, Email, WhatsApp |
| Scheduling | Daily, Weekly, Monthly, Quarterly |
| Import | CSV count import |
| Manage page | Automatic module/page/tab discovery, default enabled |
| User permissions | 21 granular permissions |
