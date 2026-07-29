<?php

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CustomerImportController;
use App\Http\Controllers\Admin\SalesplayAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\CustomerReportController;
use App\Http\Controllers\Reports\InventoryReportController;
use App\Http\Controllers\Reports\MonthlyReportController;
use App\Http\Controllers\Reports\PaymentTypeReportController;
use App\Http\Controllers\Reports\PnlReportController;
use App\Http\Controllers\Reports\ProductReportController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Reports\StockAdjustmentController;
use App\Http\Controllers\Reports\YearlyReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('company')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [SalesReportController::class, 'index'])->name('sales.index');
        Route::get('/sales/export/{format}', [SalesReportController::class, 'export'])->name('sales.export');
        Route::post('/sales/email', [SalesReportController::class, 'email'])->middleware('throttle:6,1')->name('sales.email');
        Route::get('/sales/{receipt}', [SalesReportController::class, 'show'])->name('sales.show');
        Route::get('/monthly', [MonthlyReportController::class, 'index'])->name('monthly');
        Route::get('/monthly/export/{format}', [MonthlyReportController::class, 'export'])->name('monthly.export');
        Route::post('/monthly/email', [MonthlyReportController::class, 'email'])->middleware('throttle:6,1')->name('monthly.email');
        Route::get('/yearly', [YearlyReportController::class, 'index'])->name('yearly');
        Route::get('/yearly/export/{format}', [YearlyReportController::class, 'export'])->name('yearly.export');
        Route::post('/yearly/email', [YearlyReportController::class, 'email'])->middleware('throttle:6,1')->name('yearly.email');
        Route::get('/products', [ProductReportController::class, 'index'])->name('products');
        Route::get('/products/export/{format}', [ProductReportController::class, 'export'])->name('products.export');
        Route::post('/products/email', [ProductReportController::class, 'email'])->middleware('throttle:6,1')->name('products.email');
        Route::get('/payment-types', [PaymentTypeReportController::class, 'index'])->name('payment-types.index');
        Route::get('/payment-types/export/{format}', [PaymentTypeReportController::class, 'export'])->name('payment-types.export');
        Route::post('/payment-types/email', [PaymentTypeReportController::class, 'email'])->middleware('throttle:6,1')->name('payment-types.email');
        Route::get('/customers', [CustomerReportController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerReportController::class, 'show'])->name('customers.show');
        Route::get('/inventory', [InventoryReportController::class, 'index'])->name('inventory.index');
        Route::get('/inventory/adjustment', [StockAdjustmentController::class, 'create'])->name('inventory.adjustment.create');
        Route::post('/inventory/adjustment', [StockAdjustmentController::class, 'store'])->name('inventory.adjustment.store');
        Route::post('/inventory/reset', [StockAdjustmentController::class, 'resetAll'])->name('inventory.reset');
        Route::get('/pnl', [PnlReportController::class, 'index'])->name('pnl');
        Route::get('/pnl/export/{format}', [PnlReportController::class, 'export'])->name('pnl.export');
        Route::post('/pnl/email', [PnlReportController::class, 'email'])->middleware('throttle:6,1')->name('pnl.email');
    });

    Route::middleware('company')->group(function () {
        Route::resource('expenses', ExpenseController::class)->except('show');
    });

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('companies', CompanyController::class)->except('show');
        Route::resource('salesplay-accounts', SalesplayAccountController::class)->except('show');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/import', [CustomerImportController::class, 'create'])->name('import.create');
        Route::post('/import', [CustomerImportController::class, 'store'])->name('import.store');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
