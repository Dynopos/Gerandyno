<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Reports\MonthlyReportController;
use App\Http\Controllers\Reports\ProductReportController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Reports\YearlyReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('company')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/sales', [SalesReportController::class, 'index'])->name('sales.index');
        Route::get('/sales/{receipt}', [SalesReportController::class, 'show'])->name('sales.show');
        Route::get('/monthly', [MonthlyReportController::class, 'index'])->name('monthly');
        Route::get('/yearly', [YearlyReportController::class, 'index'])->name('yearly');
        Route::get('/products', [ProductReportController::class, 'index'])->name('products');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
