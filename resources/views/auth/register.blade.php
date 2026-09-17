@extends('layouts.app')

@section('content')
<div class="min-h-[80vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
        <div class="text-center">
            <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-xl font-bold mx-auto mb-3">
                Z
            </div>
            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Create an Account</h2>
            <p class="text-xs text-slate-500 mt-1">Join Zacma as a buyer or marketplace seller</p>
        </div>

        <form action="{{ route('register.submit') }}" method="POST" class="space-y-4">
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
                <label class="text-xs font-semibold text-slate-700 block mb-1">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="John Doe" class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="name@example.com" class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
            </div>

            <div>
                <label class="text-xs font-semibold text-slate-700 block mb-1">Phone Number (optional)</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+251911..." class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-1">Account Role</label>
                    <select name="role" class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none bg-white">
                        <option value="CUSTOMER">Buyer / Customer</option>
                        <option value="SELLER">Marketplace Seller</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-1">Organization</label>
                    <select name="organization_id" class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none bg-white">
                        @foreach($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-1">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-700 block mb-1">Confirm</label>
                    <input type="password" name="password_confirmation" required placeholder="••••••••" class="w-full text-xs border border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
                </div>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm shadow transition mt-2">
                Complete Registration
            </button>
        </form>

        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-800 ml-1">Sign in</a>
        </div>
    </div>
</div>
@endsection
