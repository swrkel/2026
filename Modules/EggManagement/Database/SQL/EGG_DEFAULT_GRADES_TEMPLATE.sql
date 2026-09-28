-- Replace @BUSINESS_ID before running, or insert through Egg Management > Configuration > Egg Grades.
SET @BUSINESS_ID = 1;
INSERT INTO egg_grades (business_id,code,name,min_weight_g,max_weight_g,sort_order,active,created_at,updated_at) VALUES
(@BUSINESS_ID,'J','Jumbo',70.00,NULL,10,1,NOW(),NOW()),
(@BUSINESS_ID,'XL','Extra Large',63.00,69.99,20,1,NOW(),NOW()),
(@BUSINESS_ID,'L','Large',56.00,62.99,30,1,NOW(),NOW()),
(@BUSINESS_ID,'M','Medium',49.00,55.99,40,1,NOW(),NOW()),
(@BUSINESS_ID,'S','Small',NULL,48.99,50,1,NOW(),NOW()),
(@BUSINESS_ID,'R','Reject',NULL,NULL,90,1,NOW(),NOW())
ON DUPLICATE KEY UPDATE name=VALUES(name),min_weight_g=VALUES(min_weight_g),max_weight_g=VALUES(max_weight_g),sort_order=VALUES(sort_order),active=VALUES(active),updated_at=NOW();
