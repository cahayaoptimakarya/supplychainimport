<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UomController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\SupplierCategoryController;
use App\Http\Controllers\Admin\WarehouseController;
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
Route::middleware(['auth', 'verified', 'menu.permission'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.masterdata.items.index');
    })->name('dashboard');

    Route::prefix('masterdata')->as('masterdata.')->group(function () {
        // Items DataTables AJAX endpoint
        Route::get('/items/data', [ItemController::class, 'data'])->name('items.data');
        // Items Import CSV
        Route::post('/items/import', [ItemController::class, 'import'])->name('items.import');
        // Items CRUD
        Route::resource('items', ItemController::class)->except(['show'])->names('items');

        // Categories DataTables AJAX endpoint
        Route::get('/categories/data', [CategoryController::class, 'data'])->name('categories.data');
        // Categories CRUD
        Route::resource('categories', CategoryController::class)->except(['show'])->names('categories');

        // Supplier Categories DataTables AJAX endpoint
        Route::get('/supplier-categories/data', [SupplierCategoryController::class, 'data'])->name('supplier-categories.data');
        // Supplier Categories CRUD
        Route::resource('supplier-categories', SupplierCategoryController::class)->except(['show'])->names('supplier-categories');

        // Suppliers DataTables AJAX endpoint
        Route::get('/suppliers/data', [SupplierController::class, 'data'])->name('suppliers.data');
        // Suppliers CRUD
        Route::resource('suppliers', SupplierController::class)->except(['show'])->names('suppliers');

        // Warehouses DataTables AJAX endpoint
        Route::get('/warehouses/data', [WarehouseController::class, 'data'])->name('warehouses.data');
        // Warehouses CRUD
        Route::resource('warehouses', WarehouseController::class)->except(['show'])->names('warehouses');
        // UOM DataTables AJAX endpoint
        Route::get('/uom/data', [UomController::class, 'data'])->name('uom.data');
        // UOM CRUD
        Route::resource('uom', UomController::class)->except(['show'])->names('uom');
        // Users DataTables
        Route::get('/users/data', [AdminUserController::class, 'data'])->name('users.data');
        // Users CRUD
        Route::resource('users', AdminUserController::class)->except(['show'])->names('users');

        // Roles DataTables
        Route::get('/roles/data', [RoleController::class, 'data'])->name('roles.data');
        // Roles CRUD
        Route::resource('roles', RoleController::class)->except(['show'])->names('roles');

        // Menus DataTables
        Route::get('/menus/data', [MenuController::class, 'data'])->name('menus.data');
        // Menus CRUD
        Route::resource('menus', MenuController::class)->except(['show'])->names('menus');

        // Permissions management
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
        Route::get('/permissions/{role}/edit', [PermissionController::class, 'edit'])->name('permissions.edit');
        Route::put('/permissions/{role}', [PermissionController::class, 'update'])->name('permissions.update');
    });

    // Procurement & Logistics
    Route::prefix('procurement')->as('procurement.')->group(function () {
        // Purchase Orders
        Route::get('/purchase-orders/data', [\App\Http\Controllers\Admin\PurchaseOrderController::class, 'data'])->name('purchase-orders.data');
        Route::resource('purchase-orders', \App\Http\Controllers\Admin\PurchaseOrderController::class)->except(['show'])->names('purchase-orders');

        // Shipments
        Route::get('/shipments/data', [\App\Http\Controllers\Admin\ShipmentController::class, 'data'])->name('shipments.data');
        Route::resource('shipments', \App\Http\Controllers\Admin\ShipmentController::class)->except(['show'])->names('shipments');

        // Warehouse Receipts (GRN)
        Route::get('/receipts/data', [\App\Http\Controllers\Admin\WarehouseReceiptController::class, 'data'])->name('receipts.data');
        Route::resource('receipts', \App\Http\Controllers\Admin\WarehouseReceiptController::class)->only(['index','create','store'])->names('receipts');
    });
});
