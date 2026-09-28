## 2026_05_08_000001_add_mechanical_meter_fields_to_meter_sales_table

### Up

```sql
ALTER TABLE `meter_sales`
  ADD COLUMN `mechanical_last_meter` DECIMAL(15,3) NULL AFTER `closing_meter`,
  ADD COLUMN `mechanical_digital_last_meter` DECIMAL(15,3) NULL AFTER `mechanical_last_meter`,
  ADD COLUMN `mechanical_meter_difference` DECIMAL(15,3) NULL AFTER `mechanical_digital_last_meter`;
```

### Down

```sql
ALTER TABLE `meter_sales`
  DROP COLUMN `mechanical_meter_difference`,
  DROP COLUMN `mechanical_digital_last_meter`,
  DROP COLUMN `mechanical_last_meter`;
```
