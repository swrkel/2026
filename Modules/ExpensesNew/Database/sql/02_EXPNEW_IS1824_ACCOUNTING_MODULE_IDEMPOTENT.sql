-- Expenses-New IS1824
-- Adds the Accounting Module selection to each expense.
-- Safe to run repeatedly on every tenant database.

SET @expnew_sql = IF(
  EXISTS(
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'expnew_expenses'
      AND COLUMN_NAME = 'accounting_module'
  ),
  'SELECT 1',
  'ALTER TABLE `expnew_expenses` ADD COLUMN `accounting_module` VARCHAR(50) NULL'
);

PREPARE expnew_stmt FROM @expnew_sql;
EXECUTE expnew_stmt;
DEALLOCATE PREPARE expnew_stmt;
