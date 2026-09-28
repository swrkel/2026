/* MASTER AUTOSERVICE SQL UP TO STAGE 023 */


/* Included from 24_AUTOSERVICE_STAGE023_SERVICE_FLOW_QC_DELIVERY_CONTROL.sql */
/*
Auto Service Stage 023 - Service Flow, Technician Assignment, Inspection Checkpoint, QC/Delivery Readiness
Run this in each tenant database. It is safe to run multiple times.
*/

SET @db_name := DATABASE();

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics' AND COLUMN_NAME='location_id'),
    'ALTER TABLE auto_service_job_mechanics ADD COLUMN location_id BIGINT UNSIGNED NULL AFTER business_id, ADD INDEX idx_as_job_mechanics_location_id (location_id)',
    'SELECT "auto_service_job_mechanics.location_id already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_job_mechanics' AND COLUMN_NAME='quality_note'),
    'ALTER TABLE auto_service_job_mechanics ADD COLUMN quality_note TEXT NULL AFTER note',
    'SELECT "auto_service_job_mechanics.quality_note already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs' AND COLUMN_NAME='service_flow_locked'),
    'ALTER TABLE auto_service_jobs ADD COLUMN service_flow_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER job_progress, ADD INDEX idx_as_jobs_service_flow_locked (service_flow_locked)',
    'SELECT "auto_service_jobs.service_flow_locked already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := (SELECT IF(
    EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs')
    AND NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='auto_service_jobs' AND COLUMN_NAME='ready_for_qc_at'),
    'ALTER TABLE auto_service_jobs ADD COLUMN ready_for_qc_at DATETIME NULL AFTER service_flow_locked',
    'SELECT "auto_service_jobs.ready_for_qc_at already exists or table missing" AS info'
));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.service_flow.view', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE name='autoservice.service_flow.view');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'autoservice.service_flow.manage', 'web', NOW(), NOW()
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=@db_name AND TABLE_NAME='permissions')
AND NOT EXISTS (SELECT 1 FROM permissions WHERE name='autoservice.service_flow.manage');

SELECT 'Auto Service Stage 023 SQL completed' AS status;

