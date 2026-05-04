<?php

use App\Http\Controllers\AdminAccessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuperUserController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'screen'])->name('dashboard');

Route::middleware(['auth', 'screen'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'screen'])->group(function () {
    Route::get('/access', [AdminAccessController::class, 'index'])->name('access.index');
    Route::post('/groups', [AdminAccessController::class, 'storeGroup'])->name('groups.store');
    Route::put('/groups/{group}/screens', [AdminAccessController::class, 'updateGroupScreens'])->name('groups.screens.update');
    Route::put('/groups/{group}/buttons', [AdminAccessController::class, 'updateGroupButtons'])->name('groups.buttons.update');
    Route::put('/groups/{group}/db-access', [AdminAccessController::class, 'updateGroupDbAccess'])->name('groups.db.update');
    Route::put('/groups/{group}/users', [AdminAccessController::class, 'updateGroupUsers'])->name('groups.users.update');
    Route::get('/super-users', [SuperUserController::class, 'index'])->name('super-users.index');
    Route::post('/super-users/grant', [SuperUserController::class, 'grant'])->name('super-users.grant');
    Route::delete('/super-users/{user}', [SuperUserController::class, 'revoke'])->name('super-users.revoke');
});

Route::get('/groups', [AdminAccessController::class, 'index'])
    ->middleware(['auth', 'screen'])
    ->name('groups.index');

require __DIR__.'/auth.php';
