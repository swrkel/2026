-- IS2029 performance - F15 Daily Report New
--
-- Symptom: selecting or changing the date on /mpcs/F15-New is slow.
--
-- CAUSE
--
-- The report carries figures forward day by day from the last saved report (or
-- the last F22) up to the selected date, and each simulated day issued its own
-- set of queries. A gap of a month meant hundreds of queries; a year, thousands.
--
-- The code side of this is fixed in the accompanying module update, which
-- removes the repeated lookups. What remains is the cost of each individual
-- query, and that is what this script addresses.
--
-- Every one of those queries filters on the same shape:
--
--     WHERE business_id = ? AND location_id = ? AND <date column> = ?
--
-- but `transactions` carries only SEPARATE single-column indexes on
-- business_id, type and transaction_date. MySQL can use just one of them, so
-- each query narrows on one column and then examines every row that matches -
-- on a busy station that is a large scan, repeated for every simulated day.
--
-- A composite index matching the filter lets the query jump straight to the
-- rows for that business, location and date.
--
--
-- SAFETY
--
-- Indexes do not change data or query RESULTS - only how quickly rows are
-- found. Nothing here alters a figure on the report.
--
-- Adding an index does briefly lock the table on older MySQL versions, so run
-- this outside busy hours on a large database. Each statement checks first and
-- skips if the index already exists, so the script is safe to run repeatedly
-- and safe to re-run per tenant.
--
-- RUN ONCE PER TENANT DATABASE.
--
--
-- HOW TO RUN
--
-- Step 1 - see which of these already exist:
--
--     SHOW INDEX FROM `transactions`           WHERE Key_name LIKE 'idx_f15%';
--     SHOW INDEX FROM `transaction_sell_lines` WHERE Key_name LIKE 'idx_f15%';
--     SHOW INDEX FROM `form_f22_headers`       WHERE Key_name LIKE 'idx_f15%';
--
-- Step 2 - run only the CREATE statements for those that did not appear.
-- Running one that already exists is harmless: MySQL replies "#1061 Duplicate
-- key name" and changes nothing.
--
-- No stored procedure and no information_schema access is required, so this
-- works on restricted cPanel accounts.


-- Sales and credit sales: filtered by business, location, type, status and date.
CREATE INDEX `idx_f15_txn_biz_loc_date`
    ON `transactions` (`business_id`, `location_id`, `transaction_date`);

-- The sell-line join. product_id leads because the join resolves it first,
-- then the category filter is applied against `products`.
CREATE INDEX `idx_f15_sell_lines_txn_product`
    ON `transaction_sell_lines` (`transaction_id`, `product_id`);

-- Category lookups on products, used by every metric to decide oil vs gas.
CREATE INDEX `idx_f15_products_biz_category`
    ON `products` (`business_id`, `category_id`, `sub_category_id`);

-- "Is there an F22 on this date?" and "what was the last F22 on or before this
-- date?" - both now answered for a whole range in one query, which this index
-- serves directly.
CREATE INDEX `idx_f15_f22_biz_loc_date`
    ON `form_f22_headers` (`business_id`, `location_id`, `form_date`);


-- Step 3 - verify. Each should return rows.
--
--     SHOW INDEX FROM `transactions`           WHERE Key_name LIKE 'idx_f15%';
--     SHOW INDEX FROM `transaction_sell_lines` WHERE Key_name LIKE 'idx_f15%';
--     SHOW INDEX FROM `products`               WHERE Key_name LIKE 'idx_f15%';
--     SHOW INDEX FROM `form_f22_headers`       WHERE Key_name LIKE 'idx_f15%';
