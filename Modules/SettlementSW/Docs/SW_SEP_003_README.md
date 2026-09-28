# SW_SEP_003 Service Separation

This package adds the Settlement SW service layer using the requested naming convention:

- `SettlementSwDashboardService`
- `SettlementSwPaymentService`
- `SettlementSwMeterSalesService`
- `SettlementSwCustomerPaymentService`
- `SettlementSwExpenseService`
- `SettlementSwOtherIncomeService`
- `SettlementSwOtherSalesService`
- `SettlementSwCreditSaleService`
- `SettlementSwPreviewService`
- `SettlementSwValidationService`

This is a safe service-foundation package. It does not remove existing controller behavior yet, so current Settlement SW functionality should remain unchanged. The next packages can gradually move controller business logic into these services.
