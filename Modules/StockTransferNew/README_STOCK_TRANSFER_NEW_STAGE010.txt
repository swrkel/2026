StockTransferNew_STN_010
========================
Stage: Scheduling + Recurring Transfers + Minimum Stock Replenishment

This package adds the next standalone operational layer for StockTransfer-New:
- Transfer scheduling calendar/list
- Recurring transfer plans
- Minimum stock replenishment rules
- Auto proposal generation service
- Proposal review and conversion to draft transfer
- Schedule execution log
- POS style screens and language entries
- Separated tenant SQL for create/alter/insert

No product master is duplicated. Product data must continue to come through the existing standalone Products module / product lookup bridge.
