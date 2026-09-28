# Suppliers Module - Standalone Separation V1

Scope:
- Suppliers Module must own supplier pages and supplier-facing functionality inside `Modules/Suppliers`.
- Do not depend on main-system Contact controllers, Contact views, Contact utilities, or Contact routes.
- Supplier records still read/write tenant business data tables such as `contacts`, `transactions`, `transaction_payments`, `supplier_product_mappings`, `products`, and `business_locations`, using Suppliers module entity wrappers/services only.

Completed in this parcel:
1. Removed the remaining active `App\TransactionPayment` dependency from Suppliers Issue Payment Details.
2. Rebuilt Issued Payment Details through DB/query logic inside the Suppliers module controller.
3. Fixed Issued Payment Details view to use the Suppliers module layout and `suppliers_content` section.
4. Added Suppliers standalone User Activity page to replace the old Contact User Activity dependency.
5. Added Suppliers routes for:
   - `/suppliers/issue-payment-details`
   - `/suppliers/issued-payment-details`
   - `/suppliers/user-activity`
6. Added dashboard navigation buttons for Issued Payment Details and Contact User Activity.
7. Kept all controller/view namespaces under `Modules\Suppliers` and `suppliers::`.

Important note:
- This is safe for the final plan to remove main-system Contact supplier page files because the Suppliers module now has its own route/controller/view for the supplier pages mentioned in S349.
- Do not remove shared tenant database tables such as `contacts`; only remove old main-system Contact files after testing confirms the Suppliers module pages are complete.
