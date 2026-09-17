<?php

namespace App\Providers;

use App\Models\CrmLead;
use App\Models\Inquiry;
use App\Models\Listing;
use App\Policies\CrmLeadPolicy;
use App\Policies\InquiryPolicy;
use App\Policies\ListingPolicy;
use App\Services\Payments\ChapaPaymentGateway;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, ChapaPaymentGateway::class);
        $this->app->singleton(PaymentService::class, function ($app) {
            return new PaymentService($app->make(PaymentGatewayInterface::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Policies
        Gate::policy(Listing::class, ListingPolicy::class);
        Gate::policy(CrmLead::class, CrmLeadPolicy::class);
        Gate::policy(Inquiry::class, InquiryPolicy::class);

        if ($this->app->environment('production') || !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL'])) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Use SingleCookieSessionHandler for stateless, bounded cookie sessions on serverless
        if ($this->app->bound('session')) {
            $this->app->make('session')->extend('cookie', function ($app) {
                return new \App\Services\Session\SingleCookieSessionHandler(
                    $app->make('cookie'),
                    (int) (config('session.lifetime') ?: 120),
                    (bool) config('session.expire_on_close', false),
                    'zacma_session_data'
                );
            });
        }
    }
}
