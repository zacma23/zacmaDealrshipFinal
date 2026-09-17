@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-center">
            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-xl font-bold mx-auto mb-3">
                Z
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Sign In to Your Account</h2>
            <p class="text-xs text-slate-500 mt-1">Access Super Admin, Dealer CRM, or Customer Dashboard</p>
        </div>

        <form action="{{ route('login.submit') }}" method="POST" class="space-y-4">
            @csrf

            @if ($errors->any())
                <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-xs space-y-1">
                    @foreach ($errors->all() as $err)
                        <div class="flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-exclamation text-rose-500"></i>
                            <span>{{ $err }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1">Email or Phone Number</label>
                <input type="text" name="login" value="{{ old('login') }}" required autofocus placeholder="admin@zacma.com or +251..." class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
            </div>

            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="text-xs font-semibold text-slate-700">Password</label>
                </div>
                <input type="password" name="password" required placeholder="••••••••" class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center space-x-2 text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm shadow transition">
                Sign In
            </button>
        </form>

        <!-- Quick Portals Access -->
        <div class="pt-4 border-t border-slate-100">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block text-center mb-2.5">
                Instant 1-Click Access
            </span>
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('login.quick', 'super_admin') }}" class="p-2.5 bg-slate-900 hover:bg-black text-white rounded-xl text-center text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm col-span-2">
                    <i class="fa-solid fa-crown text-amber-400"></i>
                    <span>Super Admin Command Center</span>
                </a>
                <a href="{{ route('login.quick', 'dealer') }}" class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl text-center text-xs font-semibold transition flex items-center justify-center space-x-1 border border-blue-200">
                    <i class="fa-solid fa-car"></i>
                    <span>Auto Dealer GM</span>
                </a>
                <a href="{{ route('login.quick', 'agent') }}" class="p-2 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl text-center text-xs font-semibold transition flex items-center justify-center space-x-1 border border-amber-200">
                    <i class="fa-solid fa-id-badge"></i>
                    <span>Sales Agent</span>
                </a>
                <a href="{{ route('login.quick', 'property') }}" class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-xl text-center text-xs font-semibold transition flex items-center justify-center space-x-1 border border-emerald-200">
                    <i class="fa-solid fa-building"></i>
                    <span>Real Estate</span>
                </a>
                <a href="{{ route('login.quick', 'customer') }}" class="p-2 bg-teal-50 hover:bg-teal-100 text-teal-800 rounded-xl text-center text-xs font-semibold transition flex items-center justify-center space-x-1 border border-teal-200">
                    <i class="fa-solid fa-user"></i>
                    <span>Customer Buyer</span>
                </a>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-100 text-center text-xs text-slate-500">
            Don't have an account?
            <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-800 ml-1">Create an account</a>
        </div>
    </div>
</div>
@endsection
