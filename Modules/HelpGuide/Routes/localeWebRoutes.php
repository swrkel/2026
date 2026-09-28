<?php

use Illuminate\Support\Facades\Route;

/**
 * HelpGuide frontend routes.
 *
 * Important: register the actual frontend route set only once. Previously the
 * same controller actions were registered twice (with and without {locale}),
 * which caused action()/route() to resolve to a locale route without a locale
 * parameter and break every shared layout.
 */
Route::prefix('helpguide')->group(function () {
    Route::pattern('locale', '[a-zA-Z]{2}');

    // Canonical HelpGuide routes and route names (frontend, frontend.article, ...).
    Route::middleware('frontend')->group(function () {
        require module_path('HelpGuide', 'Routes/web.php');
    });

    // JavaScript translations.
    Route::get('lang.js', [
        'uses' => 'LanguageController@langJs',
        'file' => 'frontend_js',
    ])->name('frontend.lang');

    // Backward-compatible locale entry URL. It does not register the complete
    // frontend route collection a second time, so route/action names remain
    // unambiguous. Locale middleware/session can continue handling the locale.
    Route::get('{locale}', [\Modules\HelpGuide\Http\Controllers\RouteClosures\LocalewebroutesRouteController::class, 'handle1'])->name('frontend.locale');
});
