<?php

use Illuminate\Support\Facades\Route;
use Modules\UserManagementNew\Http\Controllers\RoleController;
use Modules\UserManagementNew\Http\Controllers\UserController;

Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
Route::get('/roles/{role}', [RoleController::class, 'show'])->whereNumber('role')->name('roles.show');
Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->whereNumber('role')->name('roles.edit');
Route::put('/roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->name('roles.update');
Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->name('roles.destroy');

/*
 | MA-002 (LA-1135): users.
 |
 | Mirrors the roles block above - same shape, same whereNumber guards, same
 | naming - so the two halves of this module stay consistent.
 |
 | There is no DELETE route by design. destroy() would leave orphaned
 | created_by references across transactions, settlements and ledgers, so a
 | user is DEACTIVATED via toggle-status instead. Say the word if a hard
 | delete is genuinely wanted and I will add it with the cleanup that needs
 | to come with it.
 */
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user')->name('users.show');
Route::get('/users/{user}/edit', [UserController::class, 'edit'])->whereNumber('user')->name('users.edit');
Route::put('/users/{user}', [UserController::class, 'update'])->whereNumber('user')->name('users.update');
Route::put('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->whereNumber('user')->name('users.toggle-status');
