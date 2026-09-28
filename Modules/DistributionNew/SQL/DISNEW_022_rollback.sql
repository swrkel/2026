DROP TABLE IF EXISTS `disnew_deployment_verifications`;
DROP TABLE IF EXISTS `disnew_reconciliation_exceptions`;
DROP TABLE IF EXISTS `disnew_collection_controls`;
DROP TABLE IF EXISTS `disnew_profitability_lines`;
DROP TABLE IF EXISTS `disnew_profitability_runs`;
DELETE FROM permissions WHERE name IN ('distribution_new.operational_polish.view','distribution_new.profitability.view','distribution_new.collection_controls.view','distribution_new.reconciliation_exceptions.view','distribution_new.deployment_verification.view');
