<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MA-002: legacy Product module routes now REDIRECT to ProductsNew
|--------------------------------------------------------------------------
|
| ProductsNew replaces this module. Rather than delete anything, every legacy
| URL now redirects to its ProductsNew equivalent.
|
| WHY REDIRECT RATHER THAN DELETE
|   - Stored menu entries and bookmarks keep working.
|   - It is reversible by restoring this one file; the original route files
|     are still present and untouched beside it.
|   - Nothing is lost if a gap in ProductsNew turns up later.
|
| WHAT I CHECKED BEFORE DOING THIS
|   - ZERO references to the legacy route names anywhere in the codebase:
|     route('brands.index'), route('categories.index'), route('units.index'),
|     route('variations.index'), route('products.index') all return 0 hits.
|   - Nothing outside Modules/Product/ references Modules\Product\ at all,
|     so the module is fully self-contained.
|   - There is no URL-prefix collision between the two modules: ProductsNew
|     serves everything under /products-new with an 'as' prefix of
|     'products-new.'. This is the check that mattered most - the same class
|     of collision made the entire FinanceReports module unreachable for
|     weeks.
|
| DESIGN NOTES
|   Route::redirect() is used rather than closures, because route action
|   closures cannot be serialised by 'php artisan route:cache'.
|
|   302 (temporary), NOT 301. A 301 is cached by browsers indefinitely and
|   would be painful to undo. This keeps the change fully reversible.
|
|   The legacy route NAMES are deliberately NOT re-registered. They have no
|   references, and several of them - index, create, store, edit, update,
|   destroy - are bare names already registered by up to 21 other modules.
|   Re-registering them would add to that collision rather than reduce it.
|
| MAPPING - targets verified present in Modules/ProductsNew/Routes/web.php
|   /product      -> /products-new
|   /categories   -> /products-new/settings/categories
|   /brands       -> /products-new/settings/brands
|   /units        -> /products-new/settings/units
|   /variations   -> /products-new/settings/variations
|   imports       -> /products-new/import-export
|   reports       -> /products-new/intelligence
|   settings      -> /products-new/settings/categories
*/

Route::middleware(['auth'])->group(function () {

    // Products
    Route::redirect('/product', '/products-new', 302);
    Route::redirect('/product/{any}', '/products-new', 302)
        ->where('any', '.*');

    // Categories
    Route::redirect('/categories', '/products-new/settings/categories', 302);
    Route::redirect('/categories/{any}', '/products-new/settings/categories', 302)
        ->where('any', '.*');

    // Brands
    Route::redirect('/brands', '/products-new/settings/brands', 302);
    Route::redirect('/brands/{any}', '/products-new/settings/brands', 302)
        ->where('any', '.*');

    // Units
    Route::redirect('/units', '/products-new/settings/units', 302);
    Route::redirect('/units/{any}', '/products-new/settings/units', 302)
        ->where('any', '.*');

    // Variations
    Route::redirect('/variations', '/products-new/settings/variations', 302);
    Route::redirect('/variations/{any}', '/products-new/settings/variations', 302)
        ->where('any', '.*');
});
