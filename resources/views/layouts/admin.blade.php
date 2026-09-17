<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Zacma SMM Portal')</title>

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
<body class="h-full flex overflow-hidden font-sans text-slate-100 antialiased" x-data="{ sidebarOpen: false }">
    @if(session()->has('impersonator_id'))
        <div class="fixed top-0 left-0 right-0 bg-amber-500 text-slate-950 px-4 py-2 text-xs font-semibold flex items-center justify-between shadow-md z-50">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-user-secret text-base"></i>
                <span><strong>Impersonation Active:</strong> Viewing as <strong>{{ Auth::user()->name }}</strong> ({{ Auth::user()->email }} &bull; Role: {{ Auth::user()->role }}).</span>
            </div>
            <a href="{{ route('super-admin.stop-impersonation') }}" class="bg-slate-950 hover:bg-black text-white px-3 py-1 rounded text-xs font-bold transition flex items-center space-x-1 shadow-sm">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Exit & Return to Super Admin</span>
            </a>
        </div>
    @endif

    <!-- Mobile Sidebar Backdrop -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/60 md:hidden backdrop-blur-sm"></div>

    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-950 border-r border-slate-800 transition-transform duration-200 ease-in-out md:translate-x-0 md:static md:flex md:flex-col shrink-0 {{ session()->has('impersonator_id') ? 'pt-9' : '' }}">
        <!-- Logo -->
        <div class="h-16 flex items-center px-6 border-b border-slate-800">
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-extrabold text-base shadow-sm group-hover:scale-105 transition">
                    <i class="fa-solid fa-bolt text-xs"></i>
                </div>
                <span class="font-extrabold text-white tracking-tight text-lg">Zacma <span class="text-blue-400 text-xs font-bold uppercase tracking-wider bg-blue-500/20 px-1.5 py-0.5 rounded border border-blue-400/30">SMM</span></span>
            </a>
        </div>

        <!-- Tenant / Portal Badge -->
        <div class="px-6 py-3 bg-slate-900/60 border-b border-slate-800">
            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Active Role & Tenant</div>
            <div class="text-xs font-bold text-white truncate flex items-center gap-1.5 mt-0.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>{{ Auth::user()->isSuperAdmin() ? 'Global Super Admin' : (Auth::user()->organization ? Auth::user()->organization->name : 'Zacma Client') }}</span>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            @if(Auth::user()->isSuperAdmin())
                <div class="text-[10px] uppercase tracking-wider text-purple-400 font-extrabold px-3 py-1">Super Admin Central Command</div>
                <a href="{{ route('super-admin.smm.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.dashboard') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-gauge-high w-4 text-purple-300"></i>
                    <span>SMM Command Center</span>
                </a>
                <a href="{{ route('super-admin.smm.platforms') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.platforms*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-share-nodes w-4 text-indigo-400"></i>
                    <span>Platforms & Taxonomy</span>
                </a>
                <a href="{{ route('super-admin.smm.services') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.services*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-layer-group w-4 text-blue-400"></i>
                    <span>Services & Pricing</span>
                </a>
                <a href="{{ route('super-admin.smm.providers') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.providers*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-network-wired w-4 text-emerald-400"></i>
                    <span>Upstream Gateways</span>
                </a>
                <a href="{{ route('super-admin.smm.orders') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.orders*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-boxes-stacked w-4 text-amber-400"></i>
                    <span>Global Orders Audit</span>
                </a>
                <a href="{{ route('super-admin.smm.wallets') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.wallets*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-wallet w-4 text-teal-400"></i>
                    <span>Liquidity & Wallets</span>
                </a>
                <a href="{{ route('super-admin.smm.child-panels') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.child-panels*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-globe w-4 text-pink-400"></i>
                    <span>Child Panels</span>
                </a>
                <a href="{{ route('super-admin.smm.tickets') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.smm.tickets*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-headset w-4 text-rose-400"></i>
                    <span>Support Tickets</span>
                </a>
                <a href="{{ route('super-admin.users.index') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.users*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-users-gear w-4 text-slate-400"></i>
                    <span>Users & Access</span>
                </a>
                <a href="{{ route('super-admin.audit-logs.index') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('super-admin.audit-logs*') ? 'bg-purple-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-shield-halved w-4 text-slate-400"></i>
                    <span>System Audit Logs</span>
                </a>
            @endif

            @if(Auth::user()->isReseller() || Auth::user()->isSuperAdmin())
                <div class="text-[10px] uppercase tracking-wider text-blue-400 font-extrabold px-3 py-1 mt-4">Reseller & Agency Hub</div>
                <a href="{{ route('reseller.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('reseller.dashboard') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-chart-line w-4 text-blue-400"></i>
                    <span>Wholesale Overview</span>
                </a>
                <a href="{{ route('reseller.services') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('reseller.services') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-tags w-4 text-emerald-400"></i>
                    <span>Wholesale Rate List</span>
                </a>
                <a href="{{ route('reseller.child-panel') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('reseller.child-panel*') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-globe w-4 text-pink-400"></i>
                    <span>Child Panel Settings</span>
                </a>
                <a href="{{ route('reseller.customers') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('reseller.customers*') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-users w-4 text-teal-400"></i>
                    <span>Agency Clients</span>
                </a>
                <a href="{{ route('reseller.orders') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('reseller.orders') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-receipt w-4 text-amber-400"></i>
                    <span>Reseller Orders</span>
                </a>
                <a href="{{ route('reseller.api-docs') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('reseller.api-docs*') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                    <i class="fa-solid fa-code w-4 text-indigo-400"></i>
                    <span>SMM v2 API & Keys</span>
                </a>
            @endif

            <div class="text-[10px] uppercase tracking-wider text-emerald-400 font-extrabold px-3 py-1 mt-4">SMM Client Portal</div>
            <a href="{{ route('customer.smm.dashboard') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('customer.smm.dashboard') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                <i class="fa-solid fa-house w-4 text-slate-400"></i>
                <span>Portal Overview</span>
            </a>
            <a href="{{ route('customer.smm.new-order') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('customer.smm.new-order') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                <i class="fa-solid fa-cart-plus w-4 text-emerald-400"></i>
                <span>New Instant Order</span>
            </a>
            <a href="{{ route('customer.smm.services') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('customer.smm.services') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                <i class="fa-solid fa-list-check w-4 text-indigo-400"></i>
                <span>Services Catalog</span>
            </a>
            <a href="{{ route('customer.smm.orders.index') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('customer.smm.orders*') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                <i class="fa-solid fa-clock-rotate-left w-4 text-amber-400"></i>
                <span>My Orders History</span>
            </a>
            <a href="{{ route('customer.smm.bulk-orders') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('customer.smm.bulk-orders') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                <i class="fa-solid fa-layer-group w-4 text-purple-400"></i>
                <span>Mass / Bulk Order</span>
            </a>
            <a href="{{ route('customer.smm.wallet') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('customer.smm.wallet') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                <i class="fa-solid fa-wallet w-4 text-teal-400"></i>
                <span>Deposit & Wallet</span>
            </a>
            <a href="{{ route('customer.smm.tickets') }}" class="flex items-center space-x-3 px-3 py-2 text-xs font-semibold rounded-xl hover:bg-slate-800/80 hover:text-white transition {{ request()->routeIs('customer.smm.tickets*') ? 'bg-blue-600 text-white shadow' : 'text-slate-300' }}">
                <i class="fa-solid fa-headset w-4 text-rose-400"></i>
                <span>Support Tickets</span>
            </a>
        </nav>

        <!-- User Profile footer in sidebar -->
        <div class="p-3 border-t border-slate-800 flex items-center justify-between bg-slate-900/70">
            <div class="flex items-center space-x-2.5 overflow-hidden">
                <img src="{{ Auth::user()->getAvatarUrl() }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full object-cover border border-slate-700">
                <div class="truncate">
                    <div class="text-xs font-bold text-white truncate">{{ Auth::user()->name }}</div>
                    <div class="text-[10px] text-slate-400 truncate">{{ Auth::user()->role }}</div>
                </div>
            </div>
            <div class="flex items-center space-x-1">
                <a href="{{ route('profile.edit') }}" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition" title="Profile Settings">
                    <i class="fa-solid fa-gear text-xs"></i>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-400 hover:bg-slate-800 rounded-lg transition" title="Logout">
                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-900 {{ session()->has('impersonator_id') ? 'pt-9' : '' }}">
        <!-- Top Navigation Bar -->
        <header class="h-16 bg-slate-950/90 border-b border-slate-800 flex items-center justify-between px-4 sm:px-6 shrink-0 z-10 backdrop-blur-md">
            <div class="flex items-center space-x-3">
                <button @click="sidebarOpen = true" class="md:hidden text-slate-400 hover:text-white p-2">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <div class="flex items-center gap-2">
                    <a href="{{ route('home') }}" class="text-xs font-bold text-slate-400 hover:text-blue-400 flex items-center gap-1">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Storefront</span>
                    </a>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Wallet Quick Widget -->
                @php $currentWallet = Auth::user()->wallet; @endphp
                <a href="{{ route('customer.smm.wallet') }}" class="px-3 py-1.5 bg-slate-900 border border-slate-800 hover:border-slate-700 rounded-xl text-xs font-bold flex items-center gap-2 transition">
                    <i class="fa-solid fa-wallet text-emerald-400"></i>
                    <span class="text-white font-mono">${{ number_format($currentWallet?->balance ?? 0, 2) }}</span>
                    <span class="text-[10px] text-blue-400 font-bold bg-blue-950 px-1.5 py-0.5 rounded border border-blue-800/60">+ Add Funds</span>
                </a>

                <a href="{{ route('customer.smm.new-order') }}" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl text-xs transition shadow flex items-center gap-1.5">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span class="hidden sm:inline">New Order</span>
                </a>
            </div>
        </header>

        <!-- Flash messages -->
        @if(session('success'))
            <div class="bg-emerald-500 text-white px-4 py-2 text-center text-xs font-semibold shadow-sm flex items-center justify-center space-x-2 shrink-0">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="bg-rose-600 text-white px-4 py-2 text-center text-xs font-semibold shadow-sm flex items-center justify-center space-x-2 shrink-0">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-900 text-slate-100">
            @yield('content')
        </main>
    </div>

    <!-- Universal AI Assistant Widget -->
    <x-ai-chat-widget />
</body>
</html>
