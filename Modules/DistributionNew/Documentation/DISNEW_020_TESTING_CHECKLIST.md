# DISNEW 020 Testing Checklist

1. Run the Stage 20 migration or SQL on the tenant database.
2. Assign `distributionnew.customer_portal.*` permissions.
3. Register customer portal users in `disnew_customer_portal_users`.
4. Open `/distribution-new/customer/dashboard`.
5. Verify customer can place an order and it goes to pending approval.
6. Verify invoice/statement/delivery pages are visible.
7. Submit a return request and complaint.
8. Verify all pages follow the POS-style card/table layout.
9. Confirm customer cannot see another customer's records.
