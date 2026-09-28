# 8052 - Stock Adjustment Settings

Implemented against the 06 Sep 2026 StockAdjustmentNew module supplied with the request.

## Accounting Mapping form

- Removed the mapping-level Adjustment Type selector.
- Layout now follows the supplied screenshot:
  - Row 1: Effective From, Category, Sub Category, Stock Account Group.
  - Row 2: Stock Account, Increase - Account to Link, Decrease - Account to Link, Active Mapping.
  - Row 3: Notes and Save/Update Mapping.
- Added `increase_account_id` and `decrease_account_id` to the mapping table.
- Existing historical rows are not rewritten. They continue to use the legacy `adjustment_type` + `account_to_link_id` contract until edited/saved in the new form.

## Accounting Mappings list

- Action menu now contains View and Edit only.
- Delete action and DELETE route removed.
- Removed Adjustment Type and Stock Account Group columns.
- Added Increase Account and Decrease Account after Stock Account.
- Effective From heading is two lines.
- Header font reduced from the module's previous 10px override to 8px.
- View opens a read-only detail overlay including Stock Account Group and Notes.

## Posting compatibility

- New mappings select the Increase Account for stock increases and Decrease Account for stock decreases.
- Legacy mappings remain supported without data conversion.
- Stock Account posting direction remains unchanged.

## Database deployment

Preferred: run Laravel migrations after deploying the module.

Manual alternative for each tenant database:

`Modules/StockAdjustmentNew/SQL/13_8052_Stock_Adjustment_Account_Mapping.sql`

No existing mapping rows are deleted.
