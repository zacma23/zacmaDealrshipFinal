@extends('layouts.admin')

@section('title', 'Reseller API & Developer Documentation')

@section('content')
<div class="space-y-6" x-data="{ activeTab: 'curl' }">
    <!-- Header & Generate Key -->
    <div class="bg-gradient-to-r from-slate-900 via-blue-950 to-slate-900 rounded-2xl p-6 text-white shadow-lg flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/20 text-blue-300 text-xs font-semibold mb-2">
                <i class="fa-solid fa-code"></i> Standard SMM v2 Protocol
            </div>
            <h2 class="text-2xl font-black tracking-tight">Reseller API & Automation</h2>
            <p class="text-slate-300 text-xs sm:text-sm mt-1 max-w-2xl leading-relaxed">
                Seamlessly connect your own custom website, child panel, or third-party CRM using the industry-standard SMM API v2 specification.
            </p>
        </div>
        <form method="POST" action="{{ route('reseller.api-docs.generate-key') }}" class="flex items-center gap-2 bg-white/10 p-1.5 rounded-xl backdrop-blur-md">
            @csrf
            <input type="text" name="name" placeholder="Key Label (e.g. Production Panel)" class="text-xs bg-transparent border-0 text-white placeholder-slate-400 px-3 py-1.5 focus:outline-none focus:ring-0">
            <button type="submit" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-lg text-xs shadow transition whitespace-nowrap">
                <i class="fa-solid fa-key mr-1"></i> Generate API Key
            </button>
        </form>
    </div>

    <!-- Active API Keys Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
        <h3 class="font-bold text-slate-800 text-sm mb-3 flex items-center gap-2">
            <i class="fa-solid fa-shield-keyhole text-blue-600"></i> Your Authorized API Keys
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200 uppercase tracking-wider text-[11px]">
                        <th class="py-2.5 px-4">Key Name</th>
                        <th class="py-2.5 px-4">Token Preview</th>
                        <th class="py-2.5 px-4">Last Used</th>
                        <th class="py-2.5 px-4">Created</th>
                        <th class="py-2.5 px-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($apiKeys as $key)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 font-bold text-slate-800">{{ $key->name }}</td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                <span class="bg-slate-100 px-2 py-0.5 rounded border border-slate-200">{{ $key->key_prefix }}••••••••••••••••</span>
                            </td>
                            <td class="py-3 px-4 text-slate-500">
                                {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never used' }}
                            </td>
                            <td class="py-3 px-4 text-slate-400">
                                {{ $key->created_at->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                <form method="POST" action="{{ route('reseller.api-docs.revoke-key', $key) }}" class="inline">
                                    @csrf
                                    <button type="submit" onclick="return confirm('Revoke this API Key permanently?')" class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-600 font-semibold rounded text-xs transition">
                                        <i class="fa-solid fa-trash-can mr-1"></i> Revoke
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-6 text-slate-400">
                                No active API keys generated yet. Create one above to start automating orders.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- API Technical Specification Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Endpoints Catalog -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h3 class="font-bold text-slate-800 text-sm">Endpoint Overview</h3>
                    <span class="px-2 py-0.5 bg-blue-50 text-blue-700 font-mono text-[11px] rounded font-bold">POST {{ url('/api/v2') }}</span>
                </div>

                <div class="space-y-4 text-xs">
                    <!-- Action: services -->
                    <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between font-mono font-bold text-blue-700">
                            <span>action=services</span>
                            <span class="text-slate-400 text-[10px]">Returns full service catalog & rates</span>
                        </div>
                        <div class="text-slate-600 mt-2">
                            <strong>Parameters:</strong> <code>key</code> (string, required), <code>action="services"</code>
                        </div>
                    </div>

                    <!-- Action: add -->
                    <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between font-mono font-bold text-emerald-700">
                            <span>action=add</span>
                            <span class="text-slate-400 text-[10px]">Create new order</span>
                        </div>
                        <div class="text-slate-600 mt-2 space-y-1">
                            <div><strong>Parameters:</strong> <code>key</code>, <code>action="add"</code>, <code>service</code> (ID), <code>link</code> (URL/handle), <code>quantity</code> (int)</div>
                            <div class="text-slate-400 text-[11px]">Optional: <code>runs</code> (int), <code>interval</code> (minutes) for drip-feed.</div>
                        </div>
                    </div>

                    <!-- Action: status -->
                    <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between font-mono font-bold text-purple-700">
                            <span>action=status</span>
                            <span class="text-slate-400 text-[10px]">Check single or bulk order progress</span>
                        </div>
                        <div class="text-slate-600 mt-2">
                            <strong>Parameters:</strong> <code>key</code>, <code>action="status"</code>, <code>order</code> (ID) OR <code>orders</code> (comma-separated IDs)
                        </div>
                    </div>

                    <!-- Action: balance -->
                    <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200">
                        <div class="flex items-center justify-between font-mono font-bold text-amber-700">
                            <span>action=balance</span>
                            <span class="text-slate-400 text-[10px]">Fetch reseller wallet balance</span>
                        </div>
                        <div class="text-slate-600 mt-2">
                            <strong>Parameters:</strong> <code>key</code>, <code>action="balance"</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Code Snippets -->
        <div class="bg-slate-900 text-slate-200 rounded-xl border border-slate-800 shadow-sm p-5 flex flex-col">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                <h3 class="font-bold text-white text-sm">Implementation Examples</h3>
                <div class="flex gap-1.5">
                    <button @click="activeTab = 'curl'" :class="activeTab === 'curl' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400'" class="px-2.5 py-1 rounded text-xs font-bold transition">cURL</button>
                    <button @click="activeTab = 'python'" :class="activeTab === 'python' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400'" class="px-2.5 py-1 rounded text-xs font-bold transition">Python</button>
                    <button @click="activeTab = 'php'" :class="activeTab === 'php' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400'" class="px-2.5 py-1 rounded text-xs font-bold transition">PHP</button>
                </div>
            </div>

            <!-- cURL snippet -->
            <div x-show="activeTab === 'curl'" class="flex-1 font-mono text-[11px] leading-relaxed text-slate-300 overflow-x-auto">
<pre class="bg-slate-950 p-4 rounded-lg border border-slate-800 text-emerald-400">
# Place a new order via cURL
curl -X POST "{{ url('/api/v2') }}" \
  -d "key=YOUR_API_KEY" \
  -d "action=add" \
  -d "service=1" \
  -d "link=https://instagram.com/sampleprofile" \
  -d "quantity=1000"

# Check order status
curl -X POST "{{ url('/api/v2') }}" \
  -d "key=YOUR_API_KEY" \
  -d "action=status" \
  -d "order=1001"
</pre>
            </div>

            <!-- Python snippet -->
            <div x-show="activeTab === 'python'" class="flex-1 font-mono text-[11px] leading-relaxed text-slate-300 overflow-x-auto">
<pre class="bg-slate-950 p-4 rounded-lg border border-slate-800 text-blue-300">
import requests

API_URL = "{{ url('/api/v2') }}"
API_KEY = "YOUR_API_KEY"

# Check Wallet Balance
res = requests.post(API_URL, data={
    "key": API_KEY,
    "action": "balance"
})
print("Balance:", res.json())

# Place SMM Order
order = requests.post(API_URL, data={
    "key": API_KEY,
    "action": "add",
    "service": 1,
    "link": "https://instagram.com/sampleprofile",
    "quantity": 1000
})
print("Order Response:", order.json())
</pre>
            </div>

            <!-- PHP snippet -->
            <div x-show="activeTab === 'php'" class="flex-1 font-mono text-[11px] leading-relaxed text-slate-300 overflow-x-auto">
<pre class="bg-slate-950 p-4 rounded-lg border border-slate-800 text-purple-300">
&lt;?php
$ch = curl_init("{{ url('/api/v2') }}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'key' => 'YOUR_API_KEY',
    'action' => 'add',
    'service' => 1,
    'link' => 'https://instagram.com/sampleprofile',
    'quantity' => 1000
]);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "Order ID: " . ($response['order'] ?? $response['error']);
</pre>
            </div>
        </div>
    </div>
</div>
@endsection
