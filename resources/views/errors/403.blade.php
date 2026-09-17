@extends('layouts.app')

@section('title', 'Access Restricted (403) - Zacma AI Platform')

@section('content')
<div class="min-h-[75vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full bg-white rounded-3xl border border-slate-200 shadow-xl p-8 text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-500 border border-amber-200 flex items-center justify-center text-2xl mx-auto shadow-sm">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <div>
            <span class="text-xs font-black uppercase tracking-widest text-amber-600 bg-amber-50 px-3 py-1 rounded-full border border-amber-200">
                403 &bull; Authorization Required
            </span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-3">
                Access Restricted to This Portal
            </h1>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                {{ $exception->getMessage() ?: 'Your current session does not possess the administrative privileges required to access this command section.' }}
            </p>
        </div>

        @auth
            <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200 text-left text-xs space-y-1.5">
                <div class="text-[11px] uppercase font-bold text-slate-400">Current Session:</div>
                <div class="font-bold text-slate-900 flex items-center justify-between">
                    <span>{{ Auth::user()->name }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] uppercase font-black bg-blue-100 text-blue-800 border border-blue-200">
                        {{ Auth::user()->role }}
                    </span>
                </div>
                <div class="text-slate-500 text-[11px]">{{ Auth::user()->email }}</div>
            </div>

            <div class="space-y-2 pt-2">
                <a href="{{ route('login.quick', 'super_admin') }}" class="w-full py-3 px-4 bg-slate-900 hover:bg-black text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 shadow-sm">
                    <i class="fa-solid fa-crown text-amber-400"></i>
                    <span>Switch to Super Admin Command Center</span>
                </a>

                <a href="{{ Auth::user()->getDashboardUrl() }}" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Return to My Portal Dashboard</span>
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full py-2 px-4 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition">
                        Sign Out & Switch Account
                    </button>
                </form>
            </div>
        @else
            <div class="pt-2">
                <a href="{{ route('login.quick', 'super_admin') }}" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition flex items-center justify-center space-x-2 shadow-sm">
                    <i class="fa-solid fa-crown"></i>
                    <span>Sign In as Super Admin</span>
                </a>
            </div>
        @endauth
    </div>
</div>
@endsection