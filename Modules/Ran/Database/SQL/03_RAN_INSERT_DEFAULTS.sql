-- Ran Module default records. Safe to rerun.
INSERT IGNORE INTO `ran_metals`
(`business_id`,`code`,`name`,`symbol`,`default_density`,`is_active`,`created_at`,`updated_at`)
VALUES
(0,'GOLD','Gold','Au',19.320000,1,NOW(),NOW()),
(0,'SILVER','Silver','Ag',10.490000,1,NOW(),NOW()),
(0,'PLATINUM','Platinum','Pt',21.450000,1,NOW(),NOW());

INSERT INTO `ran_document_templates`
(`business_id`,`document_type`,`name`,`paper_size`,`orientation`,`is_default`,`is_active`,`created_at`,`updated_at`)
SELECT 0,'sale','Standard Jewellery Invoice','A4','portrait',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ran_document_templates` WHERE `business_id`=0 AND `document_type`='sale' AND `name`='Standard Jewellery Invoice');

INSERT INTO `ran_document_templates`
(`business_id`,`document_type`,`name`,`paper_size`,`orientation`,`is_default`,`is_active`,`created_at`,`updated_at`)
SELECT 0,'purchase','Purchase Receipt','A4','portrait',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ran_document_templates` WHERE `business_id`=0 AND `document_type`='purchase' AND `name`='Purchase Receipt');

INSERT INTO `ran_document_templates`
(`business_id`,`document_type`,`name`,`paper_size`,`orientation`,`is_default`,`is_active`,`created_at`,`updated_at`)
SELECT 0,'production_order','Production Work Order','A4','portrait',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ran_document_templates` WHERE `business_id`=0 AND `document_type`='production_order' AND `name`='Production Work Order');

INSERT INTO `ran_document_templates`
(`business_id`,`document_type`,`name`,`paper_size`,`orientation`,`is_default`,`is_active`,`created_at`,`updated_at`)
SELECT 0,'stock_transfer','Stock Transfer Note','A4','portrait',1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `ran_document_templates` WHERE `business_id`=0 AND `document_type`='stock_transfer' AND `name`='Stock Transfer Note');

INSERT IGNORE INTO `ran_document_template_sections`
(`business_id`,`template_id`,`section_key`,`title`,`sort_order`,`enabled_by_default`,`allow_print`,`allow_sms`,`allow_email`,`allow_whatsapp`,`created_at`,`updated_at`)
SELECT 0,t.id,s.section_key,s.title,s.sort_order,1,1,1,1,1,NOW(),NOW()
FROM `ran_document_templates` t
JOIN (
    SELECT 'sale' document_type,'header' section_key,'Business Header' title,10 sort_order UNION ALL
    SELECT 'sale','customer','Customer Details',20 UNION ALL
    SELECT 'sale','items','Jewellery Details',30 UNION ALL
    SELECT 'sale','weights','Weight Summary',40 UNION ALL
    SELECT 'sale','charges','Price and Charges',50 UNION ALL
    SELECT 'sale','payments','Payment Details',60 UNION ALL
    SELECT 'sale','terms','Terms and Notes',70 UNION ALL
    SELECT 'sale','signature','Authorised Signature',80 UNION ALL
    SELECT 'purchase','header','Business Header',10 UNION ALL
    SELECT 'purchase','supplier','Supplier Details',20 UNION ALL
    SELECT 'purchase','items','Purchased Jewellery and Materials',30 UNION ALL
    SELECT 'purchase','weights','Weight Summary',40 UNION ALL
    SELECT 'purchase','totals','Purchase Totals',50 UNION ALL
    SELECT 'purchase','signature','Authorised Signature',60 UNION ALL
    SELECT 'production_order','header','Business Header',10 UNION ALL
    SELECT 'production_order','artisan','Artisan Details',20 UNION ALL
    SELECT 'production_order','design','Design and Specification',30 UNION ALL
    SELECT 'production_order','materials','Planned Materials',40 UNION ALL
    SELECT 'production_order','instructions','Production Instructions',50 UNION ALL
    SELECT 'production_order','approval','Approval',60 UNION ALL
    SELECT 'stock_transfer','header','Business Header',10 UNION ALL
    SELECT 'stock_transfer','route','From and To Stores',20 UNION ALL
    SELECT 'stock_transfer','items','Transferred Jewellery',30 UNION ALL
    SELECT 'stock_transfer','weights','Weight Summary',40 UNION ALL
    SELECT 'stock_transfer','dispatch','Dispatch Details',50 UNION ALL
    SELECT 'stock_transfer','receipt','Receipt Confirmation',60
) s ON s.document_type=t.document_type
WHERE t.business_id=0;
