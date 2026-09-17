@extends(Auth::user()->isStaff() || Auth::user()->isSuperAdmin() ? 'layouts.admin' : 'layouts.app')

@section('title', 'Profile Settings')

@section('content')
<div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="text-xs uppercase tracking-wider font-bold text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full">
                    User Settings
                </span>
                <span class="text-xs font-semibold text-slate-500">• {{ $user->role }}</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 mt-1">Profile & Security Settings</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage your personal profile picture, contact credentials, and account password.</p>
        </div>

        <div class="flex items-center gap-3">
            @if($org)
                <span class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-3.5 py-2 rounded-xl flex items-center gap-1.5">
                    <i class="fa-solid fa-building text-emerald-600"></i>
                    <span>{{ $org->name }}</span>
                </span>
            @endif
            <a href="{{ $user->getDashboardUrl() }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-300 bg-white px-3.5 py-2 rounded-xl transition">
                &larr; Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-4 text-xs font-medium flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 text-xs font-medium shadow-sm space-y-1">
            <div class="flex items-center gap-2 font-bold">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside pl-5 space-y-0.5 text-rose-700">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        <!-- Col 1: Profile Picture Card & Account Overview -->
        <div class="space-y-6">
            <!-- Profile Photo Card -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 text-center space-y-4">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider text-left pb-2 border-b border-slate-100">
                    Profile Picture
                </h3>

                <div class="relative inline-block group">
                    <img 
                        id="avatar-preview" 
                        src="{{ $user->getAvatarUrl() }}" 
                        alt="{{ $user->name }}" 
                        class="w-28 h-28 rounded-full object-cover mx-auto ring-4 ring-slate-100 border-2 border-white shadow-md transition group-hover:opacity-90"
                    >
                    <label for="avatar-input" class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-700 text-white w-8 h-8 rounded-full flex items-center justify-center cursor-pointer shadow-lg transition transform hover:scale-110" title="Upload new photo">
                        <i class="fa-solid fa-camera text-xs"></i>
                    </label>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-slate-900">{{ $user->name }}</h4>
                    <p class="text-[11px] text-slate-500">{{ $user->email }}</p>
                    <div class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $user->role }}
                    </div>
                </div>

                <!-- Avatar Upload Form -->
                <form action="{{ route('profile.avatar.update') }}" method="POST" enctype="multipart/form-data" class="space-y-3 pt-2">
                    @csrf
                    <input 
                        type="file" 
                        id="avatar-input" 
                        name="avatar" 
                        accept="image/png,image/jpeg,image/webp,image/gif" 
                        class="hidden"
                        onchange="handleAvatarSelect(this)"
                    >
                    <button 
                        type="button" 
                        onclick="document.getElementById('avatar-input').click()" 
                        class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2"
                    >
                        <i class="fa-solid fa-upload text-[11px]"></i>
                        <span>Choose Photo</span>
                    </button>

                    <button 
                        id="save-avatar-btn" 
                        type="submit" 
                        class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow transition hidden"
                    >
                        Save Photo
                    </button>
                </form>

                @if($user->avatar)
                    <form action="{{ route('profile.avatar.remove') }}" method="POST" class="pt-1">
                        @csrf
                        @method('DELETE')
                        <button 
                            type="submit" 
                            onclick="return confirm('Remove your custom photo and reset to default initials?')"
                            class="text-[11px] text-rose-500 hover:text-rose-700 font-semibold"
                        >
                            <i class="fa-solid fa-trash-can mr-1"></i> Remove photo
                        </button>
                    </form>
                @endif

                <p class="text-[10px] text-slate-400">Allowed JPG, PNG, WebP up to 5MB. Clear face photo recommended.</p>
            </div>

            <!-- Organization & Account Metadata -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 space-y-3 text-xs">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider pb-2 border-b border-slate-100">
                    Account Details
                </h3>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Business / Org</span>
                    <span class="font-bold text-slate-800">{{ $org?->name ?? 'Personal Account' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Member Since</span>
                    <span class="font-bold text-slate-800">{{ $user->created_at->format('M d, Y') }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Last Login</span>
                    <span class="font-bold text-slate-800">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Active now' }}</span>
                </div>

                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Security / 2FA</span>
                    <span class="font-bold text-emerald-600 flex items-center gap-1">
                        <i class="fa-solid fa-shield-halved"></i> Verified
                    </span>
                </div>
            </div>
        </div>

        <!-- Col 2 & 3: Profile Information & Password Forms -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Profile Details Form -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base font-bold text-slate-900">Personal Information</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Update your personal details and how your contact information appears on the platform.</p>
                </div>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Full Name <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                            <input 
                                type="text" 
                                name="name" 
                                value="{{ old('name', $user->name) }}" 
                                required 
                                class="w-full text-xs border border-slate-300 rounded-xl pl-9 pr-4 py-3 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Email Address <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-envelope absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                                <input 
                                    type="email" 
                                    name="email" 
                                    value="{{ old('email', $user->email) }}" 
                                    required 
                                    class="w-full text-xs border border-slate-300 rounded-xl pl-9 pr-4 py-3 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Phone Number</label>
                            <div class="relative">
                                <i class="fa-solid fa-phone absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                                <input 
                                    type="text" 
                                    name="phone" 
                                    value="{{ old('phone', $user->phone) }}" 
                                    placeholder="+251 911 000 000"
                                    class="w-full text-xs border border-slate-300 rounded-xl pl-9 pr-4 py-3 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button 
                            type="submit" 
                            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow transition transform hover:scale-[1.01]"
                        >
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Change Form -->
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-6 sm:p-8 space-y-6">
                <div class="border-b border-slate-100 pb-4">
                    <h2 class="text-base font-bold text-slate-900">Change Password</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Ensure your account uses a strong, unique password to prevent unauthorized access.</p>
                </div>

                <form action="{{ route('profile.password.update') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Current Password <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                            <input 
                                type="password" 
                                name="current_password" 
                                required 
                                placeholder="••••••••"
                                class="w-full text-xs border border-slate-300 rounded-xl pl-9 pr-4 py-3 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">New Password (min. 8 characters) <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-key absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                                <input 
                                    type="password" 
                                    name="password" 
                                    required 
                                    placeholder="••••••••"
                                    class="w-full text-xs border border-slate-300 rounded-xl pl-9 pr-4 py-3 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                >
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Confirm New Password <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i class="fa-solid fa-check-double absolute left-3.5 top-3.5 text-slate-400 text-xs"></i>
                                <input 
                                    type="password" 
                                    name="password_confirmation" 
                                    required 
                                    placeholder="••••••••"
                                    class="w-full text-xs border border-slate-300 rounded-xl pl-9 pr-4 py-3 outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
                                >
                            </div>
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex justify-end">
                        <button 
                            type="submit" 
                            class="px-6 py-3 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow transition transform hover:scale-[1.01]"
                        >
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function handleAvatarSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
            document.getElementById('save-avatar-btn').classList.remove('hidden');
        }
        reader.readAsDataURL(file);
    }
}
</script>
@endsection
