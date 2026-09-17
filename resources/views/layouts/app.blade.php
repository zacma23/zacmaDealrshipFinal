<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Zacma SMM | #1 Social Media Marketing Wholesale Cloud & Reseller SaaS')</title>

    <!-- Tailwind & Alpine Standalone Bundles (cPanel & offline compatible) -->
    <script src="{{ asset('js/tailwind.min.js') }}"></script>
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        [x-cloak] { display: none !important; }
        :root {
            --primary: {{ isset($currentTenant) && isset($currentTenant->branding_colors['primary']) ? $currentTenant->branding_colors['primary'] : '#2563EB' }};
        }
    </style>
</head>
<body class="h-full flex flex-col font-sans text-slate-800 antialiased">
    @if(session()->has('impersonator_id'))
        <div class="bg-amber-500 text-slate-950 px-4 py-2 text-xs sm:text-sm font-semibold flex items-center justify-between shadow-md z-50 sticky top-0">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-user-secret text-base"></i>
                <span><strong>Impersonation Active:</strong> Viewing platform as <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->email }} &bull; Role: {{ Auth::user()->role }}).</span>
            </div>
            <a href="{{ route('super-admin.stop-impersonation') }}" class="bg-slate-950 hover:bg-black text-white px-3 py-1 rounded text-xs font-bold transition flex items-center space-x-1 shadow-sm">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Exit & Return to Super Admin</span>
            </a>
        </div>
    @endif

    <!-- Navbar -->
    <header class="bg-slate-950 text-white border-b border-slate-800 sticky top-0 z-40" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center space-x-8">
                    <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                        @if(isset($currentTenant) && $currentTenant->logo)
                            <img src="{{ asset('storage/' . $currentTenant->logo) }}" alt="{{ $currentTenant->name }}" class="h-9 w-auto">
                        @else
                            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-extrabold text-lg shadow-md shadow-blue-500/20 group-hover:scale-105 transition">
                                <i class="fa-solid fa-bolt text-sm"></i>
                            </div>
                        @endif
                        <span class="font-extrabold text-xl tracking-tight text-white flex items-center gap-1.5">
                            {{ isset($currentTenant) && $currentTenant->brand_name ? $currentTenant->brand_name : 'Zacma' }}
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-400 bg-blue-500/20 px-2 py-0.5 rounded-full border border-blue-400/30">SMM</span>
                        </span>
                    </a>

                    <nav class="hidden md:flex space-x-1 items-center">
                        <a href="{{ route('home') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-white hover:bg-slate-900 px-3 py-2 rounded-lg transition {{ request()->routeIs('home') ? 'text-blue-400 bg-slate-900' : '' }}">Home</a>
                        <a href="{{ route('services.public') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-white hover:bg-slate-900 px-3 py-2 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('services.public') ? 'text-blue-400 bg-slate-900' : '' }}">
                            <i class="fa-solid fa-list-check text-indigo-400"></i>
                            <span>Services Catalog</span>
                        </a>
                        <a href="{{ route('customer.smm.new-order') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-white hover:bg-slate-900 px-3 py-2 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('customer.smm.new-order') ? 'text-blue-400 bg-slate-900' : '' }}">
                            <i class="fa-solid fa-cart-plus text-emerald-400"></i>
                            <span>New Order</span>
                        </a>
                        <a href="{{ route('api-docs.public') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-white hover:bg-slate-900 px-3 py-2 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('api-docs.public') ? 'text-blue-400 bg-slate-900' : '' }}">
                            <i class="fa-solid fa-code text-blue-400"></i>
                            <span>SMM v2 API</span>
                        </a>
                        <a href="{{ route('reseller.child-panel') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-white hover:bg-slate-900 px-3 py-2 rounded-lg transition flex items-center gap-1.5 {{ request()->routeIs('reseller.child-panel') ? 'text-blue-400 bg-slate-900' : '' }}">
                            <i class="fa-solid fa-globe text-pink-400"></i>
                            <span>Child Panels</span>
                        </a>
                    </nav>
                </div>

                <div class="hidden md:flex items-center space-x-3">
                    @auth
                        <!-- Live Wallet Widget -->
                        @php $userWallet = Auth::user()->wallet; @endphp
                        <a href="{{ route('customer.smm.wallet') }}" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 border border-slate-700/80 rounded-xl text-xs flex items-center gap-2 transition" title="View Digital Wallet & Transactions">
                            <i class="fa-solid fa-wallet text-emerald-400"></i>
                            <span class="font-bold text-white font-mono">${{ number_format($userWallet?->balance ?? 0, 2) }}</span>
                            <span class="text-[10px] text-emerald-400 font-bold bg-emerald-950 px-1.5 py-0.5 rounded border border-emerald-800/60">+ Top Up</span>
                        </a>

                        @if(Auth::user()->isSuperAdmin())
                            <a href="{{ route('super-admin.smm.dashboard') }}" class="text-xs font-bold text-purple-300 hover:text-white bg-purple-950/70 hover:bg-purple-900 border border-purple-800/80 px-3 py-1.5 rounded-xl flex items-center space-x-1.5 transition">
                                <i class="fa-solid fa-crown text-purple-400"></i>
                                <span>Super Admin</span>
                            </a>
                        @endif

                        @if(Auth::user()->isReseller() || Auth::user()->isSuperAdmin())
                            <a href="{{ route('reseller.dashboard') }}" class="text-xs font-bold text-blue-300 hover:text-white bg-blue-950/70 hover:bg-blue-900 border border-blue-800/80 px-3 py-1.5 rounded-xl flex items-center space-x-1.5 transition">
                                <i class="fa-solid fa-network-wired text-blue-400"></i>
                                <span>Reseller Hub</span>
                            </a>
                        @endif

                        <a href="{{ route('customer.smm.dashboard') }}" class="text-xs font-bold text-white bg-blue-600 hover:bg-blue-500 px-3.5 py-1.5 rounded-xl flex items-center space-x-1.5 transition shadow-sm">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>Client Portal</span>
                        </a>

                        <a href="{{ route('profile.edit') }}" class="flex items-center space-x-2 pl-2 border-l border-slate-800 hover:opacity-80 transition" title="Profile Settings">
                            <img src="{{ Auth::user()->getAvatarUrl() }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full object-cover border border-slate-700">
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-xs text-slate-400 hover:text-rose-400 ml-1 p-1" title="Logout">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-xs font-bold uppercase tracking-wider text-slate-300 hover:text-white px-3 py-2">Sign In</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-4 py-2 text-xs font-bold uppercase tracking-wider text-white bg-blue-600 hover:bg-blue-500 rounded-xl shadow-md transition">
                            Sign Up Free
                        </a>
                    @endauth
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex md:hidden">
                    <button @click="mobileOpen = !mobileOpen" class="text-slate-400 hover:text-white p-2">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div x-show="mobileOpen" x-cloak class="md:hidden border-t border-slate-800 bg-slate-950 px-4 pt-2 pb-4 space-y-2">
            <a href="{{ route('home') }}" class="block px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-900 rounded">Home</a>
            <a href="{{ route('services.public') }}" class="block px-3 py-2 text-sm font-bold text-blue-400 hover:bg-slate-900 rounded flex items-center gap-2">
                <i class="fa-solid fa-list-check"></i> Services Catalog
            </a>
            <a href="{{ route('customer.smm.new-order') }}" class="block px-3 py-2 text-sm font-bold text-emerald-400 hover:bg-slate-900 rounded flex items-center gap-2">
                <i class="fa-solid fa-cart-plus"></i> New Order
            </a>
            <a href="{{ route('api-docs.public') }}" class="block px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-900 rounded">SMM v2 API Docs</a>
            <a href="{{ route('reseller.child-panel') }}" class="block px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-900 rounded">White-Label Child Panels</a>

            @auth
                <div class="pt-3 border-t border-slate-800 space-y-2">
                    <a href="{{ route('customer.smm.dashboard') }}" class="block px-3 py-2 text-sm font-bold text-white bg-blue-600 rounded-xl flex items-center gap-2">
                        <i class="fa-solid fa-gauge-high"></i> Client Portal
                    </a>
                    @if(Auth::user()->isReseller() || Auth::user()->isSuperAdmin())
                        <a href="{{ route('reseller.dashboard') }}" class="block px-3 py-2 text-sm font-bold text-blue-300 hover:bg-slate-900 rounded flex items-center gap-2">
                            <i class="fa-solid fa-network-wired"></i> Reseller Hub
                        </a>
                    @endif
                    @if(Auth::user()->isSuperAdmin())
                        <a href="{{ route('super-admin.smm.dashboard') }}" class="block px-3 py-2 text-sm font-bold text-purple-300 hover:bg-slate-900 rounded flex items-center gap-2">
                            <i class="fa-solid fa-crown"></i> Super Admin Command
                        </a>
                    @endif
                    <a href="{{ route('customer.smm.wallet') }}" class="block px-3 py-2 text-sm font-bold text-emerald-400 hover:bg-slate-900 rounded flex items-center gap-2">
                        <i class="fa-solid fa-wallet"></i> Wallet (${{ number_format(Auth::user()->wallet?->balance ?? 0, 2) }})
                    </a>
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-2 text-sm text-slate-300 hover:bg-slate-900 rounded">Profile Settings</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 text-sm font-semibold text-rose-400 hover:bg-slate-900 rounded">Log Out</button>
                    </form>
                </div>
            @else
                <div class="pt-3 border-t border-slate-800 grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="block text-center py-2 text-sm font-bold text-slate-300 bg-slate-900 rounded-lg">Sign In</a>
                    <a href="{{ route('register') }}" class="block text-center py-2 text-sm font-bold text-white bg-blue-600 rounded-lg">Sign Up</a>
                </div>
            @endauth
        </div>
    </header>

    <!-- Global Flash Messages -->
    @if(session('success'))
        <div class="bg-emerald-500 text-white px-4 py-2.5 text-center text-xs sm:text-sm font-semibold shadow-sm flex items-center justify-center space-x-2">
            <i class="fa-solid fa-circle-check"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-600 text-white px-4 py-2.5 text-center text-xs sm:text-sm font-semibold shadow-sm flex items-center justify-center space-x-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Content Area -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-950 text-slate-400 py-14 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="space-y-4">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-blue-600 flex items-center justify-center text-white font-extrabold text-sm">
                        <i class="fa-solid fa-bolt text-xs"></i>
                    </div>
                    <span class="text-white font-extrabold text-lg tracking-tight">Zacma SMM Cloud</span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Wholesale Social Media Marketing SaaS and child-panel white-label infrastructure. Automated delivery nodes for digital marketing agencies, influencers, and resellers worldwide.
                </p>
                <div class="flex space-x-4 text-slate-400 text-sm">
                    <i class="fa-brands fa-telegram hover:text-white cursor-pointer" title="Telegram"></i>
                    <i class="fa-brands fa-whatsapp hover:text-white cursor-pointer" title="WhatsApp"></i>
                    <i class="fa-brands fa-discord hover:text-white cursor-pointer" title="Discord"></i>
                    <i class="fa-brands fa-x-twitter hover:text-white cursor-pointer" title="X"></i>
                </div>
            </div>

            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-wider mb-4">Supported Platforms</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('services.public') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-brands fa-instagram text-pink-400"></i> Instagram Followers & Likes</a></li>
                    <li><a href="{{ route('services.public') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-brands fa-tiktok text-slate-300"></i> TikTok Views & Viral Growth</a></li>
                    <li><a href="{{ route('services.public') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-brands fa-youtube text-red-500"></i> YouTube Subscribers & Watch Time</a></li>
                    <li><a href="{{ route('services.public') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-brands fa-telegram text-blue-400"></i> Telegram Channel Members</a></li>
                    <li><a href="{{ route('services.public') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-brands fa-x-twitter text-slate-300"></i> X (Twitter) Reposts & Followers</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-wider mb-4">Reseller & Cloud Tools</h4>
                <ul class="space-y-2 text-xs">
                    <li><a href="{{ route('api-docs.public') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-solid fa-code text-indigo-400"></i> SMM v2 REST API Spec</a></li>
                    <li><a href="{{ route('reseller.child-panel') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-solid fa-globe text-pink-400"></i> White-Label Child Panels</a></li>
                    <li><a href="{{ route('customer.smm.bulk-orders') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-solid fa-layer-group text-purple-400"></i> Mass Order Submissions</a></li>
                    <li><a href="{{ route('customer.smm.wallet') }}" class="hover:text-white flex items-center gap-1.5"><i class="fa-solid fa-wallet text-emerald-400"></i> Double-Entry Ledger</a></li>
                </ul>
            </div>

            <div>
                <h4 class="text-white text-xs font-bold uppercase tracking-wider mb-4">Instant Deposit Gateways</h4>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="bg-slate-900 border border-slate-800 text-slate-300 px-2.5 py-1 rounded-lg">Stripe / Card</span>
                    <span class="bg-slate-900 border border-slate-800 text-slate-300 px-2.5 py-1 rounded-lg">Crypto (USDT)</span>
                    <span class="bg-slate-900 border border-slate-800 text-slate-300 px-2.5 py-1 rounded-lg">PayPal</span>
                    <span class="bg-slate-900 border border-slate-800 text-slate-300 px-2.5 py-1 rounded-lg">Telebirr</span>
                    <span class="bg-slate-900 border border-slate-800 text-slate-300 px-2.5 py-1 rounded-lg">SantimPay</span>
                    <span class="bg-slate-900 border border-slate-800 text-slate-300 px-2.5 py-1 rounded-lg">Chapa</span>
                </div>
                <div class="mt-4 text-[11px] text-slate-500">
                    Funds credited automatically upon confirmation with zero transaction fees.
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10 pt-6 border-t border-slate-900 text-center text-xs text-slate-500 flex flex-col sm:flex-row justify-between items-center gap-3">
            <div>&copy; {{ date('Y') }} Zacma SMM Marketplace & Reseller Cloud. All rights reserved.</div>
            <div class="flex items-center space-x-4">
                <a href="{{ route('api-docs.public') }}" class="hover:text-slate-300">API</a>
                <span>&bull;</span>
                <a href="{{ route('services.public') }}" class="hover:text-slate-300">Services</a>
                <span>&bull;</span>
                <a href="{{ route('customer.smm.tickets') }}" class="hover:text-slate-300">Support</a>
            </div>
        </div>
    </footer>

    <!-- Universal AI Assistant Widget -->
    <x-ai-chat-widget />
</body>
</html>
