# Implementation Scope

## Operator access

- Main-login entry for Pumper Dashboard New
- Choose Your Business flow
- Pump Operator Display 2
- Independent passcode login
- Attempt throttling and blocked-login management
- Module-owned operator sessions
- Operator passcode management

## Pump and shift operations

- Petro PD operator mapping
- Shift creation/import and monitoring
- Pump assignment
- Receive and confirm pump
- Current meter readings and history
- Two-step pump closing
- Closed-pump statement
- Shift summary and print
- Reconciliation-aware shift closing

## Collections and transactions

- Cash, card, cheque, and credit payments
- Cash denomination details
- Multiple card lines
- Payment editing, history, voiding, printing, and summary
- Credit customer/order/vehicle confirmation and locking
- Other sales with line items
- Stock unloading with supplier, store, tank, and product validation
- Day entries and settlement references
- Daily collections

## Control and reporting

- Operator ledger
- Shortage recoveries
- Excess commissions
- Operator notes and documents
- Print history
- Audit trail
- Administrator dashboard
- Shift, payment, meter, other-sales, unload, day-entry, collection, ledger, shortage, commission, print, and audit reports
- CSV export
- Petro PD integration monitor and retry processing

## Database ownership

The release defines 31 module-owned tables. Every new table starts with `pone_`. The existing Petro PD `pump_operators` table is read as the shared operator master; its data is not duplicated as a second master.
