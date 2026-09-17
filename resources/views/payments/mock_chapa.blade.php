<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chapa Secure Payment Simulator — Ethiopia</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-emerald-950 via-slate-900 to-slate-950 text-white min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-900/90 border border-emerald-500/30 rounded-2xl p-6 shadow-2xl backdrop-blur-md">
        <!-- Chapa Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-4 mb-5">
            <div class="flex items-center gap-2">
                <div class="h-8 w-8 bg-emerald-500 rounded-lg flex items-center justify-center font-black text-slate-950 text-sm">
                    CH
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-white">Chapa Payment Simulator</h1>
                    <p class="text-[11px] text-emerald-400 font-medium">Official Ethiopia Payment Partner</p>
                </div>
            </div>
            <span class="text-xs bg-emerald-500/20 text-emerald-300 font-mono px-2 py-0.5 rounded border border-emerald-500/30">TEST MODE</span>
        </div>

        <!-- Order Summary -->
        <div class="bg-slate-800/60 rounded-xl p-4 mb-5 border border-slate-700/60">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-slate-400">Order Reference</span>
                <span class="text-xs font-mono text-white font-medium">{{ $payment->transaction_reference }}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-slate-400">Subscription Plan</span>
                <span class="text-xs text-emerald-400 font-bold">{{ $payment->plan?->name ?? 'Premium Plan' }}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-slate-400">Customer</span>
                <span class="text-xs text-white">{{ $payment->user?->name }} ({{ $payment->user?->email }})</span>
            </div>
            <div class="border-t border-slate-700/80 pt-2.5 mt-2.5 flex justify-between items-baseline">
                <span class="text-sm font-medium text-slate-300">Total Payable</span>
                <span class="text-2xl font-black text-emerald-400">ETB {{ number_format((float)$payment->amount, 2) }}</span>
            </div>
        </div>

        <!-- Payment Method Selector -->
        <div class="mb-5">
            <p class="text-xs text-slate-400 font-medium mb-2.5">Select Ethiopian Payment Method:</p>
            <div class="grid grid-cols-2 gap-2.5">
                <label class="border border-emerald-500/60 bg-emerald-950/40 rounded-xl p-3 flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="method" value="telebirr" checked class="text-emerald-500 focus:ring-0">
                    <span class="text-xs font-bold text-white">Telebirr (ኢትዮ ቴሌኮም)</span>
                </label>
                <label class="border border-slate-700 bg-slate-800/40 rounded-xl p-3 flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="method" value="cbe" class="text-emerald-500 focus:ring-0">
                    <span class="text-xs font-bold text-white">CBE Birr / Bank</span>
                </label>
            </div>
        </div>

        <!-- Action Form -->
        <form method="POST" action="{{ route('payment.mock-chapa.complete', ['reference' => $payment->transaction_reference]) }}">
            @csrf
            <button type="submit" class="w-full bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold py-3 rounded-xl transition duration-150 shadow-lg shadow-emerald-500/20 text-sm flex items-center justify-center gap-2">
                <span>Confirm & Pay ETB {{ number_format((float)$payment->amount, 2) }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
        </form>

        <p class="text-[11px] text-slate-500 text-center mt-3">
            Secure 256-bit encrypted simulation. In production, requests redirect to Chapa's bank gateway.
        </p>
    </div>
</body>
</html>

