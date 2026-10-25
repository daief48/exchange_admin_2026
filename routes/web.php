<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Demo Fast Switcher (Allows testing different roles seamlessly).
// Always registered so route() lookups stay stable, but AuthController hard-aborts
// with 403 unless demo mode is active (local env or APP_DEMO_SWITCHER=true).
Route::post('/switch-role', [AuthController::class, 'switchRole'])->name('switch.role');

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Module 3 & 4: Executive Dashboard & Analytics
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');

    // Module 1 & 6: Expenses CRUD & Raw Data Export
    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/my-depot', [ExpenseController::class, 'myDepot'])->name('my-depot.expenses');
    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
    Route::get('/expenses/{expense}/pdf', [ExpenseController::class, 'pdf'])->name('expenses.pdf');
    Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->name('expenses.show');

    // Module 2: Budgeting & Variance Engine
    Route::get('/budgets', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/budgets', [BudgetController::class, 'store'])->name('budgets.store');

    // Master Data & User Administration (Restricted to Head Office Admin)
    Route::prefix('master')->name('master.')->middleware('can:manage-master-data')->group(function () {
        Route::get('/', [MasterDataController::class, 'index'])->name('index');
        Route::post('/depots', [MasterDataController::class, 'storeDepot'])->name('depots.store');
        Route::post('/businesses', [MasterDataController::class, 'storeBusiness'])->name('businesses.store');
        Route::post('/expense-heads', [MasterDataController::class, 'storeExpenseHead'])->name('expense-heads.store');
        Route::post('/users', [MasterDataController::class, 'storeUser'])->name('users.store');
        Route::post('/users/{user}/reset-password', [MasterDataController::class, 'resetPassword'])->name('users.reset-password');

        // Bulk Upload: templates + Excel/CSV import for every master entity
        Route::get('/template/{type}', [MasterDataController::class, 'downloadTemplate'])->name('template');
        Route::post('/bulk-import/{type}', [MasterDataController::class, 'bulkImport'])->name('bulk-import');
    });
});
