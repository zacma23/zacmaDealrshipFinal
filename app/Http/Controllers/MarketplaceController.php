<?php

namespace App\Http\Controllers;

use App\Models\SmmCategory;
use App\Models\SmmOrder;
use App\Models\SmmPlatform;
use App\Models\SmmService;
use App\Models\User;
use App\Services\SmmPricing\SmmPricingEngine;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index(Request $request, SmmPricingEngine $pricingEngine)
    {
        $tenant = app()->has('current_organization') ? app('current_organization') : null;

        $platforms = SmmPlatform::with(['categories' => function ($q) {
            $q->where('is_active', true)->withCount(['services' => function ($sq) {
                $sq->where('status', 'active');
            }]);
        }])
        ->where('is_active', true)
        ->orderBy('sort_order')
        ->get();

        $featuredServices = SmmService::with(['platform', 'category'])
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $stats = [
            'total_orders' => SmmOrder::count() + 124590,
            'total_users' => User::count() + 4820,
            'services_count' => SmmService::where('status', 'active')->count(),
            'avg_speed' => '< 60 sec',
            'uptime' => '99.99%',
        ];

        return view('marketplace.index', compact('platforms', 'featuredServices', 'stats', 'tenant'));
    }
}
