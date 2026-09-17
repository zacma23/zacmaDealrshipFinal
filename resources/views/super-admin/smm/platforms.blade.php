@extends('layouts.admin')

@section('title', 'SMM Platforms & Categories')

@section('content')
<div class="space-y-6" x-data="{ platformModal: false, categoryModal: false, selectedPlatformId: '' }">
    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Social Platforms & Categories</h2>
            <p class="text-xs text-slate-500 mt-0.5">Structure social media networks (e.g., Instagram, YouTube) and their service taxonomy.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="categoryModal = true" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-lg text-xs border border-slate-200 shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-folder-plus"></i> Add Category
            </button>
            <button @click="platformModal = true" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-plus-circle"></i> Add Platform
            </button>
        </div>
    </div>

    <!-- Platforms Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($platforms as $platform)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden flex flex-col justify-between">
                <div>
                    <!-- Platform Header -->
                    <div class="p-5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-lg font-bold">
                                <i class="{{ $platform->icon ?? 'fa-solid fa-share-nodes' }}"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 text-base">{{ $platform->name }}</h3>
                                <div class="text-[11px] font-mono text-slate-400">/{{ $platform->slug }}</div>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $platform->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500' }}">
                            {{ $platform->is_active ? 'Active' : 'Disabled' }}
                        </span>
                    </div>

                    <!-- Categories Inside Platform -->
                    <div class="p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Categories ({{ $platform->categories->count() }})</span>
                            <button @click="selectedPlatformId = '{{ $platform->id }}'; categoryModal = true" class="text-xs text-blue-600 font-bold hover:underline">
                                + Add Category
                            </button>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto">
                            @forelse($platform->categories as $category)
                                <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 hover:bg-slate-100 text-xs transition">
                                    <div class="font-semibold text-slate-700 flex items-center gap-2">
                                        <i class="fa-solid fa-tag text-slate-400 text-[10px]"></i>
                                        <span>{{ $category->name }}</span>
                                    </div>
                                    <span class="text-[10px] font-mono text-slate-400">{{ $category->slug }}</span>
                                </div>
                            @empty
                                <div class="text-xs text-slate-400 py-3 text-center">No categories under this platform yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 border-t border-slate-100 text-right text-xs text-slate-400">
                    Sort Order: {{ $platform->sort_order }}
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 bg-white rounded-xl border border-slate-200 text-slate-400">
                <i class="fa-solid fa-layer-group text-3xl mb-2 text-slate-300"></i>
                <p>No platforms created yet. Click "Add Platform" above to start.</p>
            </div>
        @endforelse
    </div>

    <!-- Create Platform Modal -->
    <div x-show="platformModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="platformModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Add Social Platform</h3>
                <button @click="platformModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('super-admin.smm.platforms.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Platform Name</label>
                    <input type="text" name="name" required placeholder="Instagram" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Platform Slug</label>
                    <input type="text" name="slug" required placeholder="instagram" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">FontAwesome Icon Class</label>
                    <input type="text" name="icon" value="fa-brands fa-instagram" placeholder="fa-brands fa-instagram" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="platformModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Create Platform</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Category Modal -->
    <div x-show="categoryModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-950/50 backdrop-blur-sm p-4">
        <div @click.away="categoryModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                <h3 class="font-bold text-slate-900 text-base">Add Service Category</h3>
                <button @click="categoryModal = false" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" action="{{ route('super-admin.smm.categories.store') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Select Platform</label>
                    <select name="smm_platform_id" x-model="selectedPlatformId" required class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">-- Choose Platform --</option>
                        @foreach($platforms as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Category Name</label>
                    <input type="text" name="name" required placeholder="Instagram Followers [Guaranteed]" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Category Slug</label>
                    <input type="text" name="slug" required placeholder="instagram-followers-guaranteed" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0" class="w-full text-xs p-2.5 bg-slate-50 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" @click="categoryModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-lg text-xs">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs shadow-md">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
