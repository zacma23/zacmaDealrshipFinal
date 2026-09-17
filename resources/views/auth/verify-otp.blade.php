@extends('layouts.app')

@section('content')
<div class="min-h-[70vh] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-6 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm text-center">
        <div class="w-12 h-12 bg-blue-100 text-blue-600 rounded-2xl flex items-center justify-center text-xl mx-auto">
            <i class="fa-solid fa-mobile-screen"></i>
        </div>
        <h2 class="text-xl font-bold text-slate-900">Enter Verification Code</h2>
        <p class="text-xs text-slate-500">A 6-digit OTP code has been generated for your phone or email.</p>

        <form action="{{ route('otp.verify.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <input type="text" name="otp" required maxlength="6" autofocus placeholder="123456" class="w-48 mx-auto text-center text-2xl tracking-widest font-mono border-2 border-slate-300 rounded-xl p-3 outline-none focus:border-blue-600">
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl text-sm shadow transition">
                Verify OTP
            </button>
        </form>
    </div>
</div>
@endsection
