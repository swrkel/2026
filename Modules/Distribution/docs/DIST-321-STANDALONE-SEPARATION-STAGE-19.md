# DIST-321 - Distribution Standalone Separation Stage 19

## Scope
Final dependency verification and repository-layer preparation.

## Included
- Distribution repository layer for Customers, Products, Transactions, Payments, and Business Locations.
- Distribution dependency classifier support class.
- Safe replacement of selected direct `\App\...` references with existing Distribution entity wrappers.
- Added missing Distribution entity wrappers for account transactions and purchase lines.
- Dependency verification CSV for remaining separation work.

## Safety
This stage keeps existing table/business behaviour because wrappers extend the same ERP models. It only changes code ownership references inside the Distribution module.

## Changed files
- Distribution/Entities/DistributionSalesAgent.php
- Distribution/Entities/DistributionFreeIssueLog.php
- Distribution/Entities/DistributionTransaction.php
- Distribution/Entities/DistributionAccount.php
- Distribution/Entities/DistributionAccountType.php
- Distribution/Entities/DistributionTransactionPayment.php
- Distribution/Entities/DistributionBusiness.php
- Distribution/Entities/DistributionTaxRate.php
- Distribution/Entities/DistributionVehicleMeters.php
- Distribution/Entities/DistributionVehicles.php
- Distribution/Entities/DistributionBusinessLocation.php
- Distribution/Entities/DistributionSystem.php
- Distribution/Entities/DistributionFreeIssue.php
- Distribution/Entities/DistributionContact.php
- Distribution/Entities/DistributionSalesOrder.php
- Distribution/Entities/DistributionUser.php
- Distribution/Entities/DistributionContactLedger.php
- Distribution/Entities/DistributionNumberingPrefix.php
- Distribution/Entities/DistributionExpenseCategory.php
- Distribution/Entities/DistributionInvoice.php
- Distribution/Entities/VatDistributionInvoice.php
- Distribution/Entities/DistributionOpeningBalance.php
- Distribution/Entities/DistributionCustomer.php
- Distribution/Entities/DistributionAccountTransaction.php
- Distribution/Entities/DistributionPurchaseLine.php
- Distribution/Repositories/DistributionBaseRepository.php
- Distribution/Repositories/DistributionCustomerRepository.php
- Distribution/Repositories/DistributionProductRepository.php
- Distribution/Repositories/DistributionTransactionRepository.php
- Distribution/Repositories/DistributionPaymentRepository.php
- Distribution/Repositories/DistributionBusinessLocationRepository.php
- Distribution/Support/Dependency/DistributionDependencyClassifier.php
- Distribution/Http/Controllers/VatDistributionInvoiceController.php
- Distribution/Http/Controllers/DistributionInvoiceListController.php
- Distribution/Http/Controllers/DistributionRouteUserMapController.php
- Distribution/Http/Controllers/DistributionSalesOrderController.php
- Distribution/Http/Controllers/SettingController.php
- Distribution/Http/Controllers/DistributionInvoiceController.php
- Distribution/Http/Controllers/DistributionDailySummaryController.php

## Remaining references found
86 remaining direct external references in scan. These will be handled in the next stage where safe.
