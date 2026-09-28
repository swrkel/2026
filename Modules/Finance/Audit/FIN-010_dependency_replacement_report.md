# FIN-010 Finance Main-System Dependency Removal Report

This package performs safe direct Finance dependency replacement where possible. It does not remove legacy main-system controllers yet.

## Added Finance-owned transitional entity bridges

- `Modules/Finance/Entities/BusinessLocation.php`
- `Modules/Finance/Entities/User.php`
- `Modules/Finance/Entities/Transaction.php`
- `Modules/Finance/Entities/TransactionPayment.php`
- `Modules/Finance/Entities/Customer.php`
- `Modules/Finance/Entities/Contact.php`
- `Modules/Finance/Entities/System.php`
- `Modules/Finance/Entities/SiteSettings.php`

## Scope of code changes

- Replaced direct model imports such as `App\BusinessLocation`, `App\Account`, `App\AccountType`, `App\AccountTransaction`, `App\Transaction`, `App\Contact`, and similar with Finance module entity namespaces where safe.
- Replaced selected fully-qualified `App\...::` calls inside Finance views/controllers with `Modules\Finance\Entities\...::`.
- Added `Modules/Finance/Utils/FinanceSystemSettings.php` for Finance-owned system-setting access.
- Existing bridge controllers that extend legacy main controllers are intentionally kept for FIN-011 after UAT.

## Remaining direct dependencies requiring later safe migration

- `Entities/AccountTransaction.php:5` `use App\Utils\TransactionUtil;`
- `Entities/BusinessLocation.php:10` `class BusinessLocation extends \App\BusinessLocation`
- `Entities/User.php:10` `class User extends \App\User`
- `Entities/Transaction.php:10` `class Transaction extends \App\Transaction`
- `Entities/TransactionPayment.php:10` `class TransactionPayment extends \App\TransactionPayment`
- `Entities/Customer.php:10` `class Customer extends \App\Customer`
- `Entities/Contact.php:10` `class Contact extends \App\Contact`
- `Entities/System.php:10` `class System extends \App\System`
- `Entities/SiteSettings.php:10` `class SiteSettings extends \App\SiteSettings`
- `Resources/views/chequer/default_settings.blade.php:166` `<button type="button" class="btn btn-default bg-white btn-flat btn-modal" data-href="{{ action('\App\Http\Controllers\Chequer\DefaultFontsController@create') }}" title="@lang('unit.add_unit')" data-container=".view_modal">`
- `Http/Controllers/Journal/JournalController.php:5` `use App\Http\Controllers\JournalController as BaseJournalController;`
- `Http/Controllers/Deposits/DepositModuleController.php:5` `use App\Http\Controllers\DepositModuleController as BaseDepositModuleController;`
- `Http/Controllers/Deposits/DepositsController.php:5` `use App\Http\Controllers\DepositsController as BaseDepositsController;`
- `Http/Controllers/Payments/CustomerPaymentController.php:5` `use App\Http\Controllers\CustomerPaymentController as BaseCustomerPaymentController;`
- `Http/Controllers/Payments/CustomerPaymentBulkController.php:5` `use App\Http\Controllers\CustomerPaymentBulkController as BaseCustomerPaymentBulkController;`
- `Http/Controllers/Payments/TransactionPaymentController.php:5` `use App\Http\Controllers\TransactionPaymentController as BaseTransactionPaymentController;`
- `Http/Controllers/Payments/CustomerPaymentSimpleController.php:5` `use App\Http\Controllers\CustomerPaymentSimpleController as BaseCustomerPaymentSimpleController;`
- `Http/Controllers/Accounts/AccountListController.php:5` `use App\Http\Controllers\Controller;`
- `Http/Controllers/Accounts/AccountTypeController.php:5` `use App\Http\Controllers\AccountTypeController as BaseAccountTypeController;`
- `Http/Controllers/Accounts/AccountGroupController.php:5` `use App\Http\Controllers\AccountGroupController as BaseAccountGroupController;`
- `Http/Controllers/Accounts/AccountController.php:5` `use App\Http\Controllers\AccountController as BaseAccountController;`
- `Http/Controllers/Cheques/CancelCheque.php:5` `use App\Http\Controllers\CancelCheque as BaseCancelCheque;`
- `Http/Controllers/Cheques/PostdatedChequeController.php:5` `use App\Http\Controllers\PostdatedChequeController as BasePostdatedChequeController;`
- `Http/Controllers/Cheques/RealizedChequeController.php:5` `use App\Http\Controllers\RealizedChequeController as BaseRealizedChequeController;`
- `Http/Controllers/Cheques/DeletedChequeController.php:5` `use App\Http\Controllers\DeletedChequeController as BaseDeletedChequeController;`
- `Http/Controllers/Cheques/ChequeWriteController.php:5` `use App\Http\Controllers\ChequeWriteController as BaseChequeWriteController;`
- `Http/Controllers/Reports/AccountReportsController.php:5` `use App\Http\Controllers\AccountReportsController as BaseAccountReportsController;`
- `Http/Controllers/Expenses/ExpenseCategoryController.php:5` `use App\Http\Controllers\ExpenseCategoryController as BaseExpenseCategoryController;`
- `Http/Controllers/Expenses/ExpenseCategoryNumberController.php:5` `use App\Http\Controllers\ExpenseCategoryNumberController as BaseExpenseCategoryNumberController;`
- `Http/Controllers/Expenses/ExpenseController.php:5` `use App\Http\Controllers\ExpenseController as BaseExpenseController;`
- `Http/Controllers/Expenses/ExpenseCategoryCodeController.php:5` `use App\Http\Controllers\ExpenseCategoryCodeController as BaseExpenseCategoryCodeController;`
- `Http/Controllers/Settings/AccountSettingController.php:5` `use App\Http\Controllers\AccountSettingController as BaseAccountSettingController;`
- `Http/Controllers/Settings/DefaultAccountGroupController.php:5` `use App\Http\Controllers\DefaultAccountGroupController as BaseDefaultAccountGroupController;`
- `Http/Controllers/Settings/DefaultAccountTypeController.php:5` `use App\Http\Controllers\DefaultAccountTypeController as BaseDefaultAccountTypeController;`
- `Http/Controllers/Settings/DefaultAccountController.php:5` `use App\Http\Controllers\DefaultAccountController as BaseDefaultAccountController;`

## Important
No SQL, ledger posting, journal calculation, cheque calculation, account-book total, or settlement logic was changed.
