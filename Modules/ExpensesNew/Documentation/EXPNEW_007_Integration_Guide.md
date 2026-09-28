# EXPNEW_007 Integration Guide

Use `ExpenseIntegrationBridgeService::postCost()` for optional cost postings from other standalone modules.

The service stores received postings first, then a later processor can convert them to finalized expenses after validation, budget checks and approval workflow.

No module should directly write to core Expenses-New tables.
