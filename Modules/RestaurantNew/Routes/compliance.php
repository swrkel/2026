<?php

use Illuminate\Support\Facades\Route;
use Modules\RestaurantNew\Http\Controllers\Compliance\NutritionAllergenController;

Route::middleware(['web', 'auth', 'restaurantnew.access'])->prefix('restaurant-new/compliance')->name('restaurantnew.compliance.')->group(function () {
    Route::get('/', [NutritionAllergenController::class, 'dashboard'])->name('dashboard');
    Route::get('/allergens', [NutritionAllergenController::class, 'allergens'])->name('allergens.index');
    Route::post('/allergens', [NutritionAllergenController::class, 'storeAllergen'])->name('allergens.store');
    Route::get('/dietary-tags', [NutritionAllergenController::class, 'dietaryTags'])->name('dietary-tags.index');
    Route::post('/dietary-tags', [NutritionAllergenController::class, 'storeDietaryTag'])->name('dietary-tags.store');
    Route::get('/nutrition', [NutritionAllergenController::class, 'nutritionProfiles'])->name('nutrition.index');
    Route::post('/nutrition', [NutritionAllergenController::class, 'storeNutritionProfile'])->name('nutrition.store');
    Route::get('/checks', [NutritionAllergenController::class, 'complianceChecks'])->name('checks.index');
    Route::post('/checks', [NutritionAllergenController::class, 'storeComplianceCheck'])->name('checks.store');
    Route::get('/warnings', [NutritionAllergenController::class, 'customerWarnings'])->name('warnings.index');
    Route::get('/reports', [NutritionAllergenController::class, 'reports'])->name('reports.index');
});
