DROP TABLE IF EXISTS disnew_customer_delivery_tracking_views;
DROP TABLE IF EXISTS disnew_customer_complaints;
DROP TABLE IF EXISTS disnew_customer_return_request_lines;
DROP TABLE IF EXISTS disnew_customer_return_requests;
DROP TABLE IF EXISTS disnew_customer_portal_users;
DELETE FROM permissions WHERE name LIKE 'distributionnew.customer_portal.%';
