<?php

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Be\AuthController;
use App\Http\Controllers\Be\BranchController;
use App\Http\Controllers\Be\DashboardController;
use App\Http\Controllers\Be\FinanceController;
use App\Http\Controllers\Be\FinancialReportController;
use App\Http\Controllers\Be\LandingSectionController;
use App\Http\Controllers\Be\PickupController;
use App\Http\Controllers\Be\ShipmentController;
use App\Http\Controllers\Be\UserController;
use App\Http\Controllers\Fe\DashboardController as FeDashboardController;
use App\Http\Controllers\Fe\HomeController;
use App\Http\Controllers\Fe\OrderController;
use App\Http\Controllers\Fe\ProfileController;
use App\Http\Controllers\Fe\TrackingController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/track', [TrackingController::class, 'show'])
    ->middleware('throttle:tracking')
    ->name('track.show');
Route::post('/pickup-request', [PickupController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('pickup.store');

// Public Auth
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Customer Dashboard & Profile
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [FeDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Shipment Booking
    Route::get('/order/create', [OrderController::class, 'create'])->name('order.create');
    Route::post('/order/store', [OrderController::class, 'store'])->name('order.store');
    Route::get('/order/{pickup}/confirmation', [OrderController::class, 'confirmation'])->name('order.confirmation');
    Route::post('/order/{pickup}/reschedule', [OrderController::class, 'reschedule'])->name('order.reschedule');
    Route::post('/order/{pickup}/cancel', [OrderController::class, 'cancel'])->name('order.cancel');
    Route::post('/order/{pickup}/payment-proof', [OrderController::class, 'replacePaymentProof'])->name('order.payment-proof');
});

// API for Cascading Dropdowns
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/locations/provinsi', [LocationController::class, 'provinsi'])->name('locations.provinsi');
    Route::get('/locations/kota', [LocationController::class, 'kota'])->name('locations.kota');
    Route::get('/calculate-rate', [LocationController::class, 'calculateRate'])->name('calculate-rate');
    Route::get('/public/track/{trackingNumber}', [TrackingController::class, 'apiShow'])
        ->middleware('throttle:tracking-api')
        ->name('public.track');
});

// Staff Backend (Protected from Customers)
Route::group(['prefix' => 'be', 'middleware' => ['auth', 'be.staff'], 'as' => 'be.'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/search', [DashboardController::class, 'search'])->name('search');

    // Shipment manifest: manajer cabang + kurir (bukan admin pusat)
    Route::middleware('shipment.hub')->group(function () {
        Route::post('shipments/manifest-dispatch', [ShipmentController::class, 'manifestDispatch'])->name('shipments.manifest-dispatch');
        Route::get('shipments/manifests/{manifest}/print', [ShipmentController::class, 'printManifest'])->name('shipments.manifest-print');
        Route::resource('shipments', ShipmentController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])->name('shipments.status');
        Route::post('shipments/{shipment}/hub-scan', [ShipmentController::class, 'hubScan'])->name('shipments.hub-scan');
        Route::post('shipments/{shipment}/assign-delivery', [ShipmentController::class, 'assignDeliveryCourier'])->name('shipments.assign-delivery');
        Route::post('shipments/{shipment}/exception', [ShipmentController::class, 'recordException'])->name('shipments.exception');
        Route::get('shipments/{shipment}/receipt', [ShipmentController::class, 'printReceipt'])->name('shipments.receipt');
    });

    // Pickup queue: cabang (manajer / kasir) - bukan admin pusat
    Route::middleware('pickup.hub')->group(function () {
        Route::get('pickups', [PickupController::class, 'index'])->name('pickups.index');
        Route::get('pickups/{pickup}', [PickupController::class, 'show'])->name('pickups.show');
        Route::post('pickups/{pickup}/assign', [PickupController::class, 'assign'])->name('pickups.assign');
        Route::post('pickups/{pickup}/status', [PickupController::class, 'updateStatus'])->name('pickups.status');
        Route::post('pickups/{pickup}/payment', [PickupController::class, 'updatePayment'])->name('pickups.payment');
        Route::post('pickups/{pickup}/transfer', [PickupController::class, 'verifyTransfer'])->name('pickups.transfer');
        Route::post('pickups/{pickup}/activate-shipment', [PickupController::class, 'activateShipment'])->name('pickups.activate-shipment');
        Route::get('pickups/{pickup}/receipt', [PickupController::class, 'printReceipt'])->name('pickups.receipt');
    });

    // Manager Finance
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('financial-reports', [FinancialReportController::class, 'index'])->name('financial-reports.index');
    Route::get('financial-reports/pdf', [FinancialReportController::class, 'pdf'])->name('financial-reports.pdf');

    // Payments
    Route::post('payments/{payment}/upload-proof', [PaymentController::class, 'uploadProof'])->name('payments.upload-proof');
    Route::post('payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::get('payments/{payment}/proof', [PaymentController::class, 'printProof'])->name('payments.proof');

    // Platform control: Admin Utama only
    Route::middleware('be.admin')->group(function () {
        Route::post('landing-sections/seed-defaults', [LandingSectionController::class, 'seedDefaults'])->name('landing-sections.seed-defaults');
        Route::resource('landing-sections', LandingSectionController::class)->except(['show']);

        Route::resource('branches', BranchController::class);
        Route::post('branches/{branch}/assign-manager', [BranchController::class, 'assignManager'])->name('branches.assign-manager');
    });

    // Personnel Management - admin kelola manager, manager kelola cashier/courier, kasir read-only
    // Allow backend staff to view personnel (admin + hub staff). Detailed actions are protected by 'personnel.manager'.
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::middleware('personnel.manager')->group(function () {
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
