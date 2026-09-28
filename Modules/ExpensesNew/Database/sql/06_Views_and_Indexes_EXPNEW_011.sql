-- EXPNEW_011 Views and Indexes
CREATE OR REPLACE VIEW expnew_v_report_catalog AS SELECT code,title,category,is_active FROM expnew_report_definitions;
