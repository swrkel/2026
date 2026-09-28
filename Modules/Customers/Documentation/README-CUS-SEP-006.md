# CUS_SEP_006 Customer Workflow & Approval Separation

This package moves customer workflow/approval functions into `Modules/Customers`.

## Included

- Customer Approval Workflow
- Customer Credit Approval
- Customer Status Changes
- Customer Activation / Deactivation
- Customer Workflow History
- Approval Audit Trail

## New Tables

Run:

`Modules/Customers/Database/sql/CUS_SEP_006_customer_workflow_approval_tables.sql`

in the tenant database before testing.

## Safety Notes

This package does not change Petro, PetroPD, Finance, Distribution Dealer Portal, or customer ledger posting logic.
