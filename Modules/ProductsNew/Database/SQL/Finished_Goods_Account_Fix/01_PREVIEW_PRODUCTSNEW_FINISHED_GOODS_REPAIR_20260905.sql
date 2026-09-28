/*
 Products New - Finished Goods Account repair PREVIEW ONLY
 05 Sep 2026

 Purpose
 -------
 Products created by Products New must use the business Finished Goods Account,
 including Fuel-category products. This preview does not change data.

 Run ONLY in the intended TENANT database.
*/

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

SELECT 'Businesses represented by Products New but missing Finished Goods Account' AS check_name,
       COUNT(DISTINCT p.business_id) AS affected_businesses
FROM products_new_product_meta m
JOIN products p ON p.id = m.product_id
LEFT JOIN pn_fga_map f ON f.business_id = p.business_id
WHERE f.fga_id IS NULL;

SELECT 'Products New product masters not pointing to Finished Goods Account' AS check_name,
       COUNT(*) AS rows_to_repair
FROM pn_products
WHERE COALESCE(CAST(stock_type AS UNSIGNED),0) <> fga_id;

SELECT p.business_id,
       COALESCE(a.name, CONCAT('Account #', p.stock_type)) AS current_stock_account,
       COUNT(*) AS product_count
FROM pn_products p
LEFT JOIN accounts a ON a.id = CAST(p.stock_type AS UNSIGNED)
WHERE COALESCE(CAST(p.stock_type AS UNSIGNED),0) <> p.fga_id
GROUP BY p.business_id, current_stock_account
ORDER BY p.business_id, product_count DESC;

SELECT 'Line-linked purchase/opening-stock Finance rows to reclassify' AS check_name,
       COUNT(*) AS rows_to_repair,
       COALESCE(SUM(at.amount),0) AS value_to_reclassify
FROM account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN purchase_lines pl ON pl.id = at.purchase_line_id
JOIN pn_products pp ON pp.product_id = pl.product_id AND pp.business_id = olda.business_id
WHERE olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> pp.fga_id;

SELECT 'Line-linked sale stock Finance rows to reclassify' AS check_name,
       COUNT(*) AS rows_to_repair,
       COALESCE(SUM(at.amount),0) AS value_to_reclassify
FROM account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN transaction_sell_lines sl ON sl.id = at.sell_line_id
JOIN pn_products pp ON pp.product_id = sl.product_id AND pp.business_id = olda.business_id
WHERE olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> pp.fga_id;

SELECT 'Product-specific opening-stock Finance rows to reclassify' AS check_name,
       COUNT(*) AS rows_to_repair,
       COALESCE(SUM(at.amount),0) AS value_to_reclassify
FROM account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN transactions t ON t.id = at.transaction_id
JOIN pn_products pp ON pp.product_id = t.opening_stock_product_id AND pp.business_id = olda.business_id
WHERE t.type = 'opening_stock'
  AND olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> pp.fga_id;

/* Transaction-level stock rows are safe only when EVERY product line belongs to Products New. */
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

SELECT 'Safe transaction-level purchase/opening stock rows to reclassify' AS check_name,
       COUNT(*) AS rows_to_repair,
       COALESCE(SUM(at.amount),0) AS value_to_reclassify
FROM account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN pn_safe_purchase_transactions s ON s.transaction_id = at.transaction_id AND s.business_id = olda.business_id
WHERE at.purchase_line_id IS NULL
  AND olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> s.fga_id;

SELECT 'Safe transaction-level sale stock rows to reclassify' AS check_name,
       COUNT(*) AS rows_to_repair,
       COALESCE(SUM(at.amount),0) AS value_to_reclassify
FROM account_transactions at
JOIN accounts olda ON olda.id = at.account_id
JOIN pn_safe_sale_transactions s ON s.transaction_id = at.transaction_id AND s.business_id = olda.business_id
WHERE at.sell_line_id IS NULL
  AND olda.name IN ('Stock Account','Raw Material Account','Finished Goods Account')
  AND at.account_id <> s.fga_id;

SELECT 'PREVIEW COMPLETED - NO DATA CHANGED' AS result;
