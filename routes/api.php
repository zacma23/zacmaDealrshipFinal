<?php

use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BuyerRequirementController;
use App\Http\Controllers\Api\CrmLeadController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\ListingAiController;
use App\Http\Controllers\Api\ListingController;
use App\Http\Controllers\Api\MarketplaceController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\VehicleCatalogController;
use Illuminate\Support\Facades\Route;

$healthHandler = function () {
    return response()->json([
        'status' => 'healthy',
        'platform' => 'Zacma Dealership & Marketplace + CRM SaaS (Ethiopia)',
        'version' => '1.1.0',
        'api_versions' => ['v1'],
        'timestamp' => now()->toIso8601String(),
    ]);
};
Route::get('/health', $healthHandler);
Route::get('/v1/health', $healthHandler);

// Closure to register all core Dealership & Marketplace routes (v1 and root api)
$registerMarketplaceApiRoutes = function () {
    // ==========================================
    // AUTHENTICATION (Sanctum Tokens)
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    // ==========================================
    // PUBLIC MARKETPLACE & DISCOVERY
    // ==========================================
    Route::prefix('marketplace')->group(function () {
        Route::get('/listings', [MarketplaceController::class, 'index']);
        Route::get('/listings/{slug}', [MarketplaceController::class, 'show']);
        Route::get('/categories', [MarketplaceController::class, 'categories']);
        Route::get('/cities', [MarketplaceController::class, 'cities']);
    });

    Route::get('/profile/{username}', [ProfileController::class, 'show']);
    Route::get('/plans', [SubscriptionController::class, 'plans']);

    // Inquiries (rate limited)
    Route::post('/inquiries', [InquiryController::class, 'store'])->middleware('throttle:10,1');

    // Reviews (Public index)
    Route::get('/listings/{listing}/reviews', [ReviewController::class, 'index']);

    // Buyer Requirements Board (Public index)
    Route::get('/buyer-requirements', [BuyerRequirementController::class, 'index']);

    // Vehicle Catalog (Public — for listing form dropdowns)
    Route::get('/vehicle-brands', [VehicleCatalogController::class, 'brands']);
    Route::get('/vehicle-brands/{brand}/models', [VehicleCatalogController::class, 'models']);

    // System-Wide Knowledge-Based AI Assistant
    Route::prefix('ai')->group(function () {
        Route::post('/chat', [\App\Http\Controllers\AiChatController::class, 'chat']);
        Route::get('/history', [\App\Http\Controllers\AiChatController::class, 'history']);
        Route::post('/clear', [\App\Http\Controllers\AiChatController::class, 'clear']);
    });

    // ==========================================
    // AUTHENTICATED USER ROUTES
    // ==========================================
    Route::middleware(['auth:sanctum'])->group(function () {
        // Current User Profile & Quota
        Route::get('/me', [ProfileController::class, 'me']);
        Route::post('/profile', [ProfileController::class, 'update']);

        // Universal Listings (My Listings + CRUD)
        Route::get('/my-listings', [ListingController::class, 'myListings']);
        Route::post('/listings', [ListingController::class, 'store']);
        Route::get('/listings/{listing}', [ListingController::class, 'show']);
        Route::match(['put', 'post'], '/listings/{listing}', [ListingController::class, 'update']);
        Route::delete('/listings/{listing}', [ListingController::class, 'destroy']);
        Route::post('/listings/{listing}/status', [ListingController::class, 'changeStatus']);

        // AI-Assisted Vehicle Listing (suggest only — never creates/modifies listings)
        Route::post('/listings/ai-assist', [ListingAiController::class, 'assistVehicle']);

        // Reviews
        Route::post('/listings/{listing}/reviews', [ReviewController::class, 'store']);

        // Favorites
        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/{listing}/toggle', [FavoriteController::class, 'toggle']);

        // Inbound CRM Leads (Seller Pipeline: New, Contacted, Closed)
        Route::get('/crm/leads', [CrmLeadController::class, 'index']);
        Route::patch('/crm/leads/{lead}/status', [CrmLeadController::class, 'updateStatus']);
        Route::delete('/crm/leads/{lead}', [CrmLeadController::class, 'destroy']);

        // Buyer Requirements (CRM: Post & manage requirements)
        Route::post('/buyer-requirements', [BuyerRequirementController::class, 'store']);
        Route::get('/my-buyer-requirements', [BuyerRequirementController::class, 'myRequirements']);
        Route::patch('/buyer-requirements/{requirement}/status', [BuyerRequirementController::class, 'updateStatus']);

        // In-App Messaging
        Route::get('/messages/conversations', [MessageController::class, 'conversations']);
        Route::get('/messages/thread/{user}', [MessageController::class, 'thread']);
        Route::post('/messages/send', [MessageController::class, 'send']);

        // Subscriptions & Multi-Gateway Checkout (Chapa, Telebirr, CBE, eBirr, SantimPay)
        // Subscriptions & Package Upgrade Requests
        Route::post('/subscriptions/upgrade-request', [SubscriptionController::class, 'requestUpgrade']);
        Route::get('/subscriptions/upgrade-requests', [SubscriptionController::class, 'upgradeHistory']);
        Route::get('/subscriptions/upgrade-requests/current', [SubscriptionController::class, 'currentUpgradeRequest']);
        Route::post('/subscriptions/checkout', [SubscriptionController::class, 'checkout']);
        Route::get('/payments/history', [SubscriptionController::class, 'history']);

        // Super Admin Command Center Endpoints
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [AdminController::class, 'dashboard']);
            Route::get('/users', [AdminController::class, 'users']);
            Route::post('/users/{user}/toggle-status', [AdminController::class, 'toggleUserStatus']);
            Route::get('/pending-listings', [AdminController::class, 'pendingListings']);
            Route::post('/listings/{listing}/approve', [AdminController::class, 'approveListing']);
            Route::post('/listings/{listing}/reject', [AdminController::class, 'rejectListing']);
            Route::get('/categories', [AdminController::class, 'categories']);
            Route::post('/categories', [AdminController::class, 'storeCategory']);
            Route::match(['put', 'post'], '/categories/{category}', [AdminController::class, 'updateCategory']);
            Route::get('/plans', [AdminController::class, 'plans']);
            Route::match(['put', 'post'], '/plans/{plan}', [AdminController::class, 'updatePlan']);
            Route::get('/transactions', [AdminController::class, 'transactions']);

            // Package Upgrade Requests & Verification
            Route::get('/upgrade-requests', [AdminController::class, 'upgradeRequests']);
            Route::post('/upgrade-requests/{upgradeRequest}/approve', [AdminController::class, 'approveUpgradeRequest']);
            Route::post('/upgrade-requests/{upgradeRequest}/reject', [AdminController::class, 'rejectUpgradeRequest']);
            Route::post('/upgrade-requests/{upgradeRequest}/verify-payment', [AdminController::class, 'verifyUpgradePayment']);

            // Legacy single-provider endpoint (kept for backward compat)
            Route::get('/gateway-settings', [AdminController::class, 'gatewaySettings']);
            Route::post('/gateway-settings', [AdminController::class, 'updateGatewaySettings']);

            // Multi-Provider Payment Gateways
            Route::get('/gateways', [AdminController::class, 'gateways']);
            Route::match(['put', 'post'], '/gateways/{gateway}', [AdminController::class, 'updateGateway']);
            Route::post('/gateways/{gateway}/toggle', [AdminController::class, 'toggleGateway']);

            // Vehicle Brand/Model Catalog
            Route::get('/vehicle-brands', [VehicleCatalogController::class, 'adminBrands']);
            Route::post('/vehicle-brands', [VehicleCatalogController::class, 'storeBrand']);
            Route::match(['put', 'post'], '/vehicle-brands/{brand}', [VehicleCatalogController::class, 'updateBrand']);
            Route::get('/vehicle-brands/{brand}/models', [VehicleCatalogController::class, 'adminModels']);
            Route::post('/vehicle-brands/{brand}/models', [VehicleCatalogController::class, 'storeModel']);
            Route::match(['put', 'post'], '/vehicle-models/{model}', [VehicleCatalogController::class, 'updateModel']);
        });
    });
};

// 1. Register under /api/... (Standard MVP compatibility)
$registerMarketplaceApiRoutes();

// 2. Register under /api/v1/... (Specification Requirement)
Route::prefix('v1')->group($registerMarketplaceApiRoutes);

// Payment Webhook (supports /api/webhooks/payment/{provider} and /api/v1/webhooks/payment/{provider})
Route::post('/webhooks/payment/{provider}', [PaymentWebhookController::class, 'handle'])->name('api.payment.webhook');
Route::post('/v1/webhooks/payment/{provider}', [PaymentWebhookController::class, 'handle'])->name('api.v1.payment.webhook');

// SMM Standard API v2 (Preserved for compatibility)
Route::any('/v2', [\App\Http\Controllers\Api\SmmApiController::class, 'handle'])->name('api.v2.smm');
