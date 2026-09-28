<?php
// Add this once in routes/web.php and routes/tenant.php if module auto provider loading is not enabled:
if (file_exists(base_path('Modules/ExpensesNew/Routes/web.php'))) {
    Route::middleware(['web', 'auth'])
        ->prefix('expenses-new')
        ->as('expensesnew.')
        ->namespace('Modules\\ExpensesNew\\Http\\Controllers')
        ->group(base_path('Modules/ExpensesNew/Routes/web.php'));
}
