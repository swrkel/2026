# CUS_SEP_002 Customer Payment Actions Separation v2

## Purpose
Separates Customer Register payment/refund/deposit action handling into dedicated Customers module controllers, views, and a small service layer.

## Included Actions
- Pay Due Amount
- Advance Payment
- Security Deposit
- Refund Deposit
- Refund Payment
- Cheque Return

## Important Safety Note
This package keeps posting conservative. It does not alter ledger/accounting posting logic until account mapping is verified. It separates UI/controller routing first to avoid breaking correct existing Contact/Petro/Finance behavior.

## Replace Paths
Upload the included files to the same paths under `Modules/Customers`.

## After Upload
Run:

```bash
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
```

## Test
1. Customers Module → Customer Register.
2. Open Action menu.
3. Test each popup:
   - Pay Due Amount
   - Advance Payment
   - Security Deposit
   - Refund Deposit
   - Refund Payment
   - Cheque Return
4. Confirm each popup opens from Customers module routes.
5. Confirm Contact module pages are not changed.
