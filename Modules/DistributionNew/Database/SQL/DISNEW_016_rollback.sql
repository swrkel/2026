DROP TABLE IF EXISTS disnew_audit_results;
DROP TABLE IF EXISTS disnew_audit_checks;
DELETE FROM permissions WHERE name IN ('disnew.audit.view','disnew.audit.run');
