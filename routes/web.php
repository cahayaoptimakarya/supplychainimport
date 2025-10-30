<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UomController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Basic health check route for debugging blank page on '/'
Route::get('/healthz', function () {
    return response('OK', 200);
});

Route::get('/dashboard', function () {
    return redirect()->route('admin.masterdata.items.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Admin area
Route::middleware(['auth', 'verified'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.masterdata.items.index');
    })->name('dashboard');

    Route::prefix('masterdata')->as('masterdata.')->group(function () {
        // Items DataTables AJAX endpoint
        Route::get('/items/data', [ItemController::class, 'data'])->name('items.data');
        // Items CRUD
        Route::resource('items', ItemController::class)->except(['show'])->names('items');

        // Categories DataTables AJAX endpoint
        Route::get('/categories/data', [CategoryController::class, 'data'])->name('categories.data');
        // Categories CRUD
        Route::resource('categories', CategoryController::class)->except(['show'])->names('categories');
        Route::get('/uom', [UomController::class, 'index'])->name('uom.index');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    });
});
