# Membership-New MEMNEW_002

This package continues the standalone Membership-New module.

Added foundations:
1. Linked businesses/outlets
2. Cross-business points earn/redeem
3. Outlet + product-category point rules
4. Shareholding
5. Dividend batch/payment calculation
6. Member-to-customer sync map
7. Printable/scannable membership identity card
8. Purchase bridge endpoints for POS/Sales integration

Tables added:
- mn_linked_businesses
- mn_point_rules
- mn_point_transactions
- mn_share_holdings
- mn_dividend_batches
- mn_dividend_payments
- mn_identity_cards
- mn_customer_maps

Important:
- This does not force changes into POS/Sales/Contacts modules yet.
- The bridge endpoints are ready so other modules can call Membership-New without duplicating membership logic.
- The next package should add richer UI forms and final integration hooks after checking the latest real ERP code.
