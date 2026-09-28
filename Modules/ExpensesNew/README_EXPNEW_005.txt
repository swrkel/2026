Expenses-New EXPNEW_005 - Enterprise Financial Control Platform

Install notes:
1. Copy Modules/ExpensesNew into the Laravel Modules folder.
2. Include Routes/web_expnew005.php from the module route loader or merge with the module web route file.
3. Run Database/sql/EXPNEW_005_budget_policy_tax_recurring.sql in every tenant database.
4. Publish/copy Resources/assets/css/expnew005.css and Resources/assets/js/expnew005.js into the public module assets path if your deployment does not auto-publish module assets.

This parcel only adds standalone Expenses-New files with expnew_ database tables. It does not overwrite the existing Expenses module.
