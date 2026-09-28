-- Add 'source' column to pump_operator_meter_sales table
-- to distinguish pump-closing entries from payment-page entries

ALTER TABLE `pump_operator_meter_sales`
ADD COLUMN `source` VARCHAR(20) NULL DEFAULT NULL AFTER `testing_qty`;

-- Backfill: tag payment-flow records
UPDATE `pump_operator_meter_sales` poms
INNER JOIN `pump_operator_meter_sale_details` pomsd ON pomsd.sale_id = poms.id
INNER JOIN `pump_operator_assignments` poa ON poa.pump_operator_other_sale_id = pomsd.id
SET poms.source = 'payment';

-- Backfill: tag remaining records as closing
UPDATE `pump_operator_meter_sales`
SET `source` = 'closing'
WHERE `source` IS NULL;
