/*
 Products New - Finished Goods Account repair EXECUTE
 05 Sep 2026

 BACKUP THE TENANT DATABASE FIRST.
 Run ONLY in the intended TENANT database.

 This repair is deliberately scoped to products recorded in
 products_new_product_meta. It does not change unrelated core products.
 It reclassifies only stock-account rows that can be linked safely to those
 products. Ambiguous mixed-product transaction rows are not guessed.
*/

SET @pn_old_fk := @@SESSION.FOREIGN_KEY_CHECKS;
SET SESSION FOREIGN_KEY_CHECKS = 0;
START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS pn_fga_map;
CREATE TEMPORARY TABLE pn_fga_map AS
SELECT a.business_id, a.id AS fga_id
FROM accounts a
WHERE a.name = 'Finished Goods Account'
  AND COALESCE(a.is_closed, 0) = 0
  AND a.id = (
      SELECT a2.id
      FROM accounts a2
      WHERE a2.business_id = a.business_id
        AND a2.name = 'Finished Goods Account'
        AND COALESCE(a2.is_closed, 0) = 0
      ORDER BY CASE WHEN COALESCE(a2.location_id, 'all') = 'all' THEN 0 ELSE 1 END,
               COALESCE(a2.is_main_account, 0) DESC,
               a2.id
      LIMIT 1
  );

DROP TEMPORARY TABLE IF EXISTS pn_products;
CREATE TEMPORARY TABLE pn_products AS
SELECT DISTINCT p.id AS product_id, p.business_id, p.stock_type, f.fga_id
FROM products_new_product_meta m
JOIN products p ON p.id = m.product_id
JOIN pn_fga_map f ON f.business_id = p.business_id;
ALTER TABLE pn_products ADD PRIMARY KEY (product_id), ADD KEY (business_id), ADD KEY (fga_id);

/* 1. Product master: future purchase/sale/opening-stock posting goes to FGA. */
UPDATE products p
JOIN pn_products pp ON pp.product_id = p.id
SET p.stock_type = CAST(pp.fga_id AS CHAR),
    p.updated_at = COALESCE(p.updated_at, NOW())
WHERE COALESCE(CAST(p.stock_type AS UNSIGNED),0) <> pp.fga_id;
SET @pn_product_master_rows := ROW_COUNT();

/* 2. Existing line-linked purchase/opening-stock entries. */
UPDATE account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN purchase_lines pl ON pl.id = at.purchase_line_id
JOIN pn_products pp ON pp.product_id = pl.product_id AND pp.business_id = olda.business_id
SET at.account_id = pp.fga_id,
    at.business_id = COALESCE(at.business_id, pp.business_id),
    at.updated_at = NOW()
WHERE olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> pp.fga_id;
SET @pn_purchase_line_rows := ROW_COUNT();

/* 3. Existing line-linked sale stock entries. */
UPDATE account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN transaction_sell_lines sl ON sl.id = at.sell_line_id
JOIN pn_products pp ON pp.product_id = sl.product_id AND pp.business_id = olda.business_id
SET at.account_id = pp.fga_id,
    at.business_id = COALESCE(at.business_id, pp.business_id),
    at.updated_at = NOW()
WHERE olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> pp.fga_id;
SET @pn_sale_line_rows := ROW_COUNT();

/* 4. Product-specific legacy opening-stock entries. */
UPDATE account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN transactions t ON t.id = at.transaction_id
JOIN pn_products pp ON pp.product_id = t.opening_stock_product_id AND pp.business_id = olda.business_id
SET at.account_id = pp.fga_id,
    at.business_id = COALESCE(at.business_id, pp.business_id),
    at.updated_at = NOW()
WHERE t.type = 'opening_stock'
  AND olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> pp.fga_id;
SET @pn_opening_rows := ROW_COUNT();

/* 5. Transaction-level rows: only transactions where ALL lines are Products New. */
DROP TEMPORARY TABLE IF EXISTS pn_safe_purchase_transactions;
CREATE TEMPORARY TABLE pn_safe_purchase_transactions AS
SELECT t.id AS transaction_id, t.business_id, MIN(pp.fga_id) AS fga_id
FROM transactions t
JOIN purchase_lines pl ON pl.transaction_id = t.id
LEFT JOIN pn_products pp ON pp.product_id = pl.product_id AND pp.business_id = t.business_id
WHERE t.type IN ('purchase','purchase_return','opening_stock','purchase_transfer')
GROUP BY t.id, t.business_id
HAVING COUNT(*) = COUNT(pp.product_id)
   AND COUNT(DISTINCT pp.fga_id) = 1;
ALTER TABLE pn_safe_purchase_transactions ADD PRIMARY KEY (transaction_id);

UPDATE account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN pn_safe_purchase_transactions s ON s.transaction_id = at.transaction_id AND s.business_id = olda.business_id
SET at.account_id = s.fga_id,
    at.business_id = COALESCE(at.business_id, s.business_id),
    at.updated_at = NOW()
WHERE at.purchase_line_id IS NULL
  AND olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> s.fga_id;
SET @pn_purchase_tx_rows := ROW_COUNT();

DROP TEMPORARY TABLE IF EXISTS pn_safe_sale_transactions;
CREATE TEMPORARY TABLE pn_safe_sale_transactions AS
SELECT t.id AS transaction_id, t.business_id, MIN(pp.fga_id) AS fga_id
FROM transactions t
JOIN transaction_sell_lines sl ON sl.transaction_id = t.id
LEFT JOIN pn_products pp ON pp.product_id = sl.product_id AND pp.business_id = t.business_id
WHERE t.type IN ('sell','sell_return')
GROUP BY t.id, t.business_id
HAVING COUNT(*) = COUNT(pp.product_id)
   AND COUNT(DISTINCT pp.fga_id) = 1;
ALTER TABLE pn_safe_sale_transactions ADD PRIMARY KEY (transaction_id);

UPDATE account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN pn_safe_sale_transactions s ON s.transaction_id = at.transaction_id AND s.business_id = olda.business_id
SET at.account_id = s.fga_id,
    at.business_id = COALESCE(at.business_id, s.business_id),
    at.updated_at = NOW()
WHERE at.sell_line_id IS NULL
  AND olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> s.fga_id;
SET @pn_sale_tx_rows := ROW_COUNT();

COMMIT;
SET SESSION FOREIGN_KEY_CHECKS = @pn_old_fk;

SELECT
    @pn_product_master_rows AS product_master_rows_repaired,
    @pn_purchase_line_rows AS purchase_line_finance_rows_reclassified,
    @pn_sale_line_rows AS sale_line_finance_rows_reclassified,
    @pn_opening_rows AS opening_stock_finance_rows_reclassified,
    @pn_purchase_tx_rows AS safe_purchase_transaction_rows_reclassified,
    @pn_sale_tx_rows AS safe_sale_transaction_rows_reclassified,
    'PRODUCTS NEW FINISHED GOODS REPAIR COMPLETED' AS result;

SELECT p.business_id, COUNT(*) AS products_still_not_fga
FROM products_new_product_meta m
JOIN products p ON p.id = m.product_id
LEFT JOIN pn_fga_map f ON f.business_id = p.business_id
WHERE f.fga_id IS NULL OR COALESCE(CAST(p.stock_type AS UNSIGNED),0) <> f.fga_id
GROUP BY p.business_id;
