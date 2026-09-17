@php
    $initialUser = auth()->check() ? [
        'id' => auth()->id(),
        'name' => auth()->user()->name,
        'username' => auth()->user()->username,
        'email' => auth()->user()->email,
        'role' => auth()->user()->role,
        'is_super_admin' => auth()->user()->isSuperAdmin(),
        'avatar' => auth()->user()->getAvatarUrl(),
        'quota' => [
            'limit' => auth()->user()->getListingLimit(),
            'used' => auth()->user()->getActiveListingCount(),
            'remaining' => max(0, auth()->user()->getListingLimit() - auth()->user()->getActiveListingCount()),
            'can_create' => auth()->user()->canCreateListing(),
            'plan_name' => auth()->user()->activeSubscription?->plan?->name ?? 'Basic',
        ]
    ] : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Zacma Marketplace') }} — Ethiopia Multi-Industry Marketplace & CRM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        window.__APP_NAME__ = "{{ config('app.name', 'Zacma Marketplace') }}";
        window.__INITIAL_USER__ = {!! json_encode($initialUser) !!};
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-900 antialiased min-h-screen">
    <div id="root"></div>
</body>
</html>

