Distribution New Stage 2

Upload/replace the DistributionNew folder. Run only DISNEW_002_SMS_ORDER_INVOICE_STOCK.sql for this stage. DISNEW_MASTER.sql contains Stage 1 + Stage 2 combined SQL for new tenant databases.

SMS note: this module does not recreate the SMS module. It queues Distribution New events into disnew_sms_logs and bridges to the existing SMS module when the host adapter/table is available.
