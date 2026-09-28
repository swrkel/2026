# Leads-New Stage 12 RC4 Deployment Checklist

1. Replace only the supplied `Modules/LeadsNew` files.
2. Do not replace the existing old Leads module.
3. Run tenant migrations for the new `leads_new_*` tables.
4. Clear Laravel cache/config/view cache if your deployment process normally does this.
5. Enable Leads-New permission/module flag only for businesses that should see it.
6. Test Dashboard, List, Add, Edit, Follow-up, Documents, Reports, Workflow, Customer 360, and Advanced Search.

No old Leads files are included or changed in this parcel.
