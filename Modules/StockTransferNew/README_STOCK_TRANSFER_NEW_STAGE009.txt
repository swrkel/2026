StockTransferNew STN_009 - Advanced Reporting

Added:
- AdvancedReportController
- StockTransferAdvancedReportService
- Product-wise transfer analysis
- Location-wise transfer analysis
- Store-wise transfer analysis
- Vehicle / driver analysis
- User-wise accountability report
- Monthly transfer trend
- Exception report for delayed, rejected, returned, short/excess transfers
- CSV export route for all advanced reports
- POS-style report cards and toolbar filters
- Permission SQL for advanced reports and report export

Notes:
- Product details are not duplicated. Reports use product_id/variation_id and can be enhanced through the existing standalone Products bridge.
- Multi-tenant safety is preserved by filtering all reports by current business_id.
