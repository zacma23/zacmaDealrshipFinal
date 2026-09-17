<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SmmCustomerPortalController;
use App\Http\Controllers\SmmResellerPortalController;
use App\Http\Controllers\SmmSuperAdminController;
use App\Http\Controllers\SuperAdminController;
use Illuminate\Support\Facades\Route;

// ==========================================
// PUBLIC SMM MARKETPLACE & INFO
// ==========================================

Route::get('/', [MarketplaceController::class, 'index'])->name('home');
Route::get('/services', [SmmCustomerPortalController::class, 'services'])->name('services.public');
Route::get('/api-docs', [SmmResellerPortalController::class, 'apiDocs'])->name('api-docs.public');

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/login/quick/{role}', [AuthController::class, 'quickLogin'])->name('login.quick');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/verify-otp', [AuthController::class, 'showVerifyOtp'])->name('otp.verify');
Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('otp.verify.submit');

// ==========================================
// USER PROFILE & ACCOUNT SETTINGS
// ==========================================

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::delete('/profile/avatar', [ProfileController::class, 'removeAvatar'])->name('profile.avatar.remove');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Portal entry route
    Route::get('/dashboard', function () {
        return redirect(auth()->user()->getDashboardUrl());
    })->name('dashboard');
});

// ==========================================
// SUPER ADMIN SMM COMMAND CENTER
// ==========================================

Route::prefix('super-admin')->middleware(['auth', 'role:SUPER_ADMIN'])->group(function () {
    Route::get('/', [SmmSuperAdminController::class, 'dashboard'])->name('super-admin.dashboard');
    Route::get('/smm', [SmmSuperAdminController::class, 'dashboard'])->name('super-admin.smm.dashboard');
    Route::get('/smm/platforms', [SmmSuperAdminController::class, 'platforms'])->name('super-admin.smm.platforms');
    Route::post('/smm/platforms', [SmmSuperAdminController::class, 'storePlatform'])->name('super-admin.smm.platforms.store');
    Route::post('/smm/categories', [SmmSuperAdminController::class, 'storeCategory'])->name('super-admin.smm.categories.store');
    Route::get('/smm/services', [SmmSuperAdminController::class, 'services'])->name('super-admin.smm.services');
    Route::post('/smm/services', [SmmSuperAdminController::class, 'storeService'])->name('super-admin.smm.services.store');
    Route::get('/smm/providers', [SmmSuperAdminController::class, 'providers'])->name('super-admin.smm.providers');
    Route::post('/smm/providers', [SmmSuperAdminController::class, 'storeProvider'])->name('super-admin.smm.providers.store');
    Route::post('/smm/providers/{provider}/test', [SmmSuperAdminController::class, 'testProvider'])->name('super-admin.smm.providers.test');
    Route::get('/smm/orders', [SmmSuperAdminController::class, 'orders'])->name('super-admin.smm.orders');
    Route::post('/smm/orders/{order}/sync', [SmmSuperAdminController::class, 'syncOrder'])->name('super-admin.smm.orders.sync');
    Route::post('/smm/orders/{order}/refund', [SmmSuperAdminController::class, 'refundOrder'])->name('super-admin.smm.orders.refund');
    Route::get('/smm/wallets', [SmmSuperAdminController::class, 'wallets'])->name('super-admin.smm.wallets');
    Route::post('/smm/wallets/{wallet}/adjust', [SmmSuperAdminController::class, 'adjustWallet'])->name('super-admin.smm.wallets.adjust');
    Route::get('/smm/child-panels', [SmmSuperAdminController::class, 'childPanels'])->name('super-admin.smm.child-panels');
    Route::get('/smm/tickets', [SmmSuperAdminController::class, 'tickets'])->name('super-admin.smm.tickets');
    Route::post('/smm/tickets/{ticket}/reply', [SmmSuperAdminController::class, 'replyTicket'])->name('super-admin.smm.tickets.reply');

    // Users & Access Management
    Route::get('/users', [SuperAdminController::class, 'users'])->name('super-admin.users.index');
    Route::post('/users/{user}/toggle', [SuperAdminController::class, 'toggleUserStatus'])->name('super-admin.users.toggle');
    Route::post('/impersonate/{user}', [SuperAdminController::class, 'impersonate'])->name('super-admin.impersonate');

    // Settings & Audit Logs
    Route::get('/settings', [SuperAdminController::class, 'settings'])->name('super-admin.settings.index');
    Route::post('/settings', [SuperAdminController::class, 'updateSettings'])->name('super-admin.settings.update');
    Route::get('/audit-logs', [SuperAdminController::class, 'auditLogs'])->name('super-admin.audit-logs.index');
});

// Stop Impersonation
Route::match(['get', 'post'], '/super-admin/stop-impersonation', [SuperAdminController::class, 'stopImpersonation'])->middleware(['auth'])->name('super-admin.stop-impersonation');

// ==========================================
// RESELLER & AGENCY HUB
// ==========================================

Route::prefix('reseller')->middleware(['auth', 'role:SUPER_ADMIN,ORGANIZATION_ADMIN,MANAGER,RESELLER,STAFF'])->group(function () {
    Route::get('/', [SmmResellerPortalController::class, 'dashboard'])->name('reseller.dashboard');
    Route::get('/services', [SmmResellerPortalController::class, 'services'])->name('reseller.services');
    Route::get('/customers', [SmmResellerPortalController::class, 'customers'])->name('reseller.customers');
    Route::post('/customers', [SmmResellerPortalController::class, 'storeCustomer'])->name('reseller.customers.store');
    Route::post('/customers/{customer}/toggle', [SmmResellerPortalController::class, 'toggleCustomer'])->name('reseller.customers.toggle');
    Route::post('/customers/{customer}/balance', [SmmResellerPortalController::class, 'adjustCustomerBalance'])->name('reseller.customers.balance');
    Route::post('/customers/bulk-import', [SmmResellerPortalController::class, 'bulkCustomerImport'])->name('reseller.customers.bulk-import');
    Route::get('/orders', [SmmResellerPortalController::class, 'orders'])->name('reseller.orders');
    Route::get('/api-docs', [SmmResellerPortalController::class, 'apiDocs'])->name('reseller.api-docs');
    Route::post('/api-keys', [SmmResellerPortalController::class, 'generateApiKey'])->name('reseller.api-keys.generate');
    Route::post('/api-docs/generate-key', [SmmResellerPortalController::class, 'generateApiKey'])->name('reseller.api-docs.generate-key');
    Route::post('/api-docs/revoke-key/{apiKey}', [SmmResellerPortalController::class, 'revokeApiKey'])->name('reseller.api-docs.revoke-key');
    Route::delete('/api-keys/{apiKey}', [SmmResellerPortalController::class, 'revokeApiKey'])->name('reseller.api-keys.revoke');
    Route::get('/child-panel', [SmmResellerPortalController::class, 'childPanel'])->name('reseller.child-panel');
    Route::post('/child-panel', [SmmResellerPortalController::class, 'updateChildPanel'])->name('reseller.child-panel.update');
});

// ==========================================
// CUSTOMER & CLIENT PORTAL
// ==========================================

Route::prefix('customer')->middleware(['auth'])->group(function () {
    Route::get('/', [SmmCustomerPortalController::class, 'dashboard'])->name('customer.dashboard');
    Route::get('/smm', [SmmCustomerPortalController::class, 'dashboard'])->name('customer.smm.dashboard');
    Route::get('/smm/new-order', [SmmCustomerPortalController::class, 'newOrder'])->name('customer.smm.new-order');
    Route::post('/smm/calculate-price', [SmmCustomerPortalController::class, 'calculatePrice'])->name('customer.smm.calculate-price');
    Route::post('/smm/orders', [SmmCustomerPortalController::class, 'storeOrder'])->name('customer.smm.orders.store');
    Route::get('/smm/services', [SmmCustomerPortalController::class, 'services'])->name('customer.smm.services');
    Route::get('/smm/orders', [SmmCustomerPortalController::class, 'orders'])->name('customer.smm.orders.index');
    Route::get('/smm/my-orders', [SmmCustomerPortalController::class, 'orders'])->name('customer.smm.orders');
    Route::post('/smm/orders/{order}/refill', [SmmCustomerPortalController::class, 'refill'])->name('customer.smm.orders.refill');
    Route::post('/smm/orders/{order}/cancel', [SmmCustomerPortalController::class, 'cancel'])->name('customer.smm.orders.cancel');
    Route::get('/smm/wallet', [SmmCustomerPortalController::class, 'wallet'])->name('customer.smm.wallet');
    Route::post('/smm/wallet/deposit', [SmmCustomerPortalController::class, 'deposit'])->name('customer.smm.wallet.deposit');
    Route::get('/smm/bulk-orders', [SmmCustomerPortalController::class, 'bulkOrders'])->name('customer.smm.bulk-orders');
    Route::post('/smm/bulk-orders', [SmmCustomerPortalController::class, 'processBulkOrders'])->name('customer.smm.bulk-orders.process');
    Route::get('/smm/tickets', [SmmCustomerPortalController::class, 'tickets'])->name('customer.smm.tickets');
    Route::post('/smm/tickets', [SmmCustomerPortalController::class, 'storeTicket'])->name('customer.smm.tickets.store');
    Route::post('/smm/tickets/{ticket}/reply', [SmmCustomerPortalController::class, 'replyTicket'])->name('customer.smm.tickets.reply');
});

// ==========================================
// UNIVERSAL CONTEXTUAL AI CHAT
// ==========================================

Route::post('/ai/chat', [AiChatController::class, 'chat'])->name('ai.chat');
Route::get('/ai/chat/history', [AiChatController::class, 'history'])->name('ai.chat.history');
Route::post('/ai/chat/clear', [AiChatController::class, 'clear'])->name('ai.chat.clear');

// ==========================================
// ETHIOPIA MARKETPLACE & CRM REACT SPA
// ==========================================

Route::get('/marketplace', function () {
    return view('app');
})->name('marketplace.index');

Route::get('/profile/{username}', function ($username) {
    return view('app');
})->name('profile.public');

Route::get('/checkout/mock-chapa/{reference}', function ($reference) {
    $payment = \App\Models\Payment::with(['plan', 'user'])->where('transaction_reference', $reference)->firstOrFail();
    return view('payments.mock_chapa', compact('payment'));
})->name('payment.mock-chapa');

Route::post('/checkout/mock-chapa/{reference}/complete', function ($reference, \App\Services\Payments\PaymentService $service) {
    $result = $service->processWebhookPayment($reference, [
        'status' => 'success',
        'reference' => 'CHAPA-SIM-' . \Illuminate\Support\Str::random(10),
    ]);

    return redirect('/marketplace?payment=success&ref=' . $reference)->with('success', 'Chapa payment completed! Your subscription is now active.');
})->name('payment.mock-chapa.complete');

Route::get('/app/{any?}', function () {
    return view('app');
})->where('any', '.*');

