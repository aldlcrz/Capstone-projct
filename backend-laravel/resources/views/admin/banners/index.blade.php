@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{
    tab: '{{ $pendingCount > 0 ? "requests" : "all" }}',
    showAddModal: false,
    showEditModal: false,
    showRejectModal: false,
    rejectRoute: '',
    rejectBannerTitle: '',
    editingBanner: {
        id: '', title: '', subtitle: '',
        button_text_1: '', button_url_1: '',
        button_text_2: '', button_url_2: '',
        order_index: 0, is_active: true, image_url: ''
    }
}">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Content Management</div>
            <h1 class="font-serif text-3xl font-bold text-black">Hero <span class="text-gray-300 font-light italic">Banners</span></h1>
        </div>
        <button @click="showAddModal = true"
            class="flex items-center gap-2 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Banner
        </button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-2 border-b border-gray-100 pb-1">
        <button @click="tab = 'requests'"
            :class="tab === 'requests' ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 font-bold hover:text-gray-600'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all flex items-center gap-2">
            Seller Requests
            @if($pendingCount > 0)
                <span class="px-2 py-0.5 bg-amber-500 text-white text-[8px] font-black rounded-full">{{ $pendingCount }}</span>
            @endif
        </button>
        <button @click="tab = 'all'"
            :class="tab === 'all' ? 'border-b-2 border-black text-black font-black' : 'text-gray-400 font-bold hover:text-gray-600'"
            class="px-4 py-2 text-[10px] uppercase tracking-widest transition-all">
            All Banners
        </button>
    </div>

    {{-- ── SELLER REQUESTS TAB ── --}}
    <div x-show="tab === 'requests'" class="space-y-4">
        @if($sellerBanners->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-12 text-center shadow-sm">
                <div class="w-14 h-14 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-xs font-bold text-gray-300 uppercase tracking-widest">No seller banner requests yet</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden divide-y divide-gray-50">
                @foreach($sellerBanners as $banner)
                    <div class="p-6 flex flex-col md:flex-row md:items-center gap-6 hover:bg-gray-50/50 transition-all">
                        {{-- Preview --}}
                        <div class="w-32 shrink-0">
                            <div class="w-32 aspect-video rounded-xl overflow-hidden bg-gray-50 border border-gray-100 shadow-sm">
                                <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover">
                            </div>
                        </div>

                        {{-- Banner Details --}}
                        <div class="flex-1 space-y-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-bold text-black">{{ $banner->title ?: 'Untitled Banner' }}</span>
                                @if($banner->status === 'pending')
                                    <span class="px-2 py-0.5 bg-amber-50 border border-amber-200 text-amber-700 text-[8px] font-black rounded-full uppercase tracking-wider">Pending</span>
                                @elseif($banner->status === 'approved')
                                    <span class="px-2 py-0.5 bg-green-50 border border-green-200 text-green-700 text-[8px] font-black rounded-full uppercase tracking-wider">Approved</span>
                                @else
                                    <span class="px-2 py-0.5 bg-red-50 border border-red-200 text-red-700 text-[8px] font-black rounded-full uppercase tracking-wider">Rejected</span>
                                @endif
                            </div>
                            <p class="text-[10px] text-gray-500 max-w-md leading-relaxed">{{ Str::limit($banner->subtitle, 100) }}</p>
                            @if($banner->user)
                                <div class="flex items-center gap-1.5 text-[9px] text-gray-400 font-bold uppercase tracking-wider pt-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ $banner->user->shopName ?: $banner->user->name }}
                                    <span class="text-gray-200">·</span>
                                    {{ $banner->created_at->diffForHumans() }}
                                </div>
                            @endif
                            @if($banner->button_text_1)
                                <div class="text-[9px] text-gray-400 pt-0.5">Btn 1: <span class="font-bold text-gray-600">{{ $banner->button_text_1 }}</span> → {{ $banner->button_url_1 }}</div>
                            @endif
                            @if($banner->button_text_2)
                                <div class="text-[9px] text-gray-400">Btn 2: <span class="font-bold text-gray-600">{{ $banner->button_text_2 }}</span> → {{ $banner->button_url_2 }}</div>
                            @endif
                            @if($banner->rejection_reason)
                                <div class="text-[9px] text-red-500 font-bold pt-1">Rejection Reason: {{ $banner->rejection_reason }}</div>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 shrink-0">
                            @if($banner->status === 'pending')
                                <form action="{{ route('admin.banners.approve', $banner->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="px-4 py-2.5 bg-black hover:bg-green-600 text-white rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                                        Approve
                                    </button>
                                </form>
                                <button type="button"
                                    @click="rejectRoute = '{{ route('admin.banners.reject', $banner->id) }}'; rejectBannerTitle = '{{ addslashes($banner->title ?: 'Untitled') }}'; showRejectModal = true"
                                    class="px-4 py-2.5 bg-white border border-red-200 text-red-600 hover:bg-red-50 rounded-xl text-[9px] font-black uppercase tracking-widest transition-all">
                                    Reject
                                </button>
                            @elseif($banner->status === 'approved')
                                <form action="{{ route('admin.banners.toggle', $banner->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest transition-all {{ $banner->is_active ? 'bg-green-50 text-green-600 border border-green-200 hover:bg-green-100' : 'bg-gray-100 text-gray-400 border border-gray-200 hover:bg-gray-200' }}">
                                        {{ $banner->is_active ? 'Live' : 'Hidden' }}
                                    </button>
                                </form>
                            @endif
                            <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST"
                                onsubmit="return confirm('Delete this banner request?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-2 text-gray-300 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── ALL BANNERS TAB ── --}}
    <div x-show="tab === 'all'" class="space-y-4" style="display:none;" x-cloak>
        @if($banners->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-16 text-center shadow-sm">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-black uppercase tracking-widest mb-1">No Banners</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">Create dynamic sliding banners to display on your homepage.</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest w-36">Preview</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Banner Info</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Source</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-20">Order</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-28">Visibility</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($banners as $banner)
                            <tr class="hover:bg-gray-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="w-32 aspect-video rounded-xl overflow-hidden bg-gray-50 border border-gray-100 shadow-sm">
                                        <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover" alt="Banner">
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-sm font-bold text-black">{{ $banner->title ?: 'Untitled Banner' }}</div>
                                        <div class="text-[10px] text-gray-500 max-w-xs line-clamp-2">{{ $banner->subtitle }}</div>
                                        @if($banner->button_text_1 && $banner->button_url_1)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 text-gray-700 rounded text-[9px] font-bold">
                                                <span class="w-1.5 h-1.5 rounded-full bg-[#C0422A]"></span>
                                                {{ $banner->button_text_1 }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($banner->userId && $banner->user)
                                        <div class="space-y-0.5">
                                            <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-700 text-[8px] font-black rounded-full border border-blue-100 uppercase tracking-wider">Seller Request</span>
                                            <div class="text-[9px] text-gray-500 font-bold">{{ $banner->user->shopName ?: $banner->user->name }}</div>
                                            @if($banner->status === 'pending')
                                                <span class="text-[8px] text-amber-600 font-black uppercase">Pending Review</span>
                                            @elseif($banner->status === 'approved')
                                                <span class="text-[8px] text-green-600 font-black uppercase">Approved</span>
                                            @else
                                                <span class="text-[8px] text-red-500 font-black uppercase">Rejected</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 text-[8px] font-black rounded-full border border-gray-200 uppercase tracking-wider">Admin</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2.5 py-1 bg-gray-100 text-gray-700 rounded-lg text-xs font-bold font-mono">
                                        {{ $banner->order_index }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <form action="{{ route('admin.banners.toggle', $banner->id) }}" method="POST">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest transition-all {{ $banner->is_active ? 'bg-green-50 text-green-600 border border-green-200/50 hover:bg-green-100' : 'bg-gray-100 text-gray-400 border border-gray-200/50 hover:bg-gray-200' }}">
                                            {{ $banner->is_active ? 'Visible' : 'Hidden' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="editingBanner = JSON.parse($el.dataset.banner); showEditModal = true"
                                            data-banner="{{ json_encode([
                                                'id' => $banner->id,
                                                'title' => $banner->title,
                                                'subtitle' => $banner->subtitle,
                                                'button_text_1' => $banner->button_text_1,
                                                'button_url_1' => $banner->button_url_1,
                                                'button_text_2' => $banner->button_text_2,
                                                'button_url_2' => $banner->button_url_2,
                                                'order_index' => $banner->order_index,
                                                'is_active' => $banner->is_active,
                                                'image_url' => $banner->getImageUrl()
                                            ]) }}"
                                            class="p-2 text-gray-400 hover:text-black transition-colors" title="Edit Banner">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                            </svg>
                                        </button>
                                        <form action="{{ route('admin.banners.destroy', $banner->id) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this banner?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Delete Banner">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── ADD MODAL ── --}}
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]"
            @click.away="showAddModal = false">
            <h2 class="font-serif text-2xl font-bold mb-6">Add New <span class="text-[#C0422A] italic">Hero Banner</span></h2>
            <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Banner Image <span class="text-red-500">*</span></label>
                        <input type="file" name="image" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                        <p class="text-[9px] text-gray-400 mt-1">Recommended size: 1200x400px or larger. Maximum size: 5MB.</p>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Title / Main Text</label>
                        <input type="text" name="title" placeholder="e.g. Traditional Piña Silk Barong" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">
                            Order Index
                            <span class="ml-1 font-normal normal-case text-gray-300">(display position)</span>
                        </label>
                        <input type="number" name="order_index" value="0" min="0" placeholder="e.g. 1"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                        <p class="text-[9px] text-gray-400 mt-1">Lower number = shown earlier in the carousel (0 = first).</p>
                    </div>

                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Subtitle / Supporting Text</label>
                        <input type="text" name="subtitle" placeholder="e.g. 100% hand-woven by local artisans." class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Primary Button Text (Optional)</label>
                        <input type="text" name="button_text_1" placeholder="e.g. Shop Now" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Primary Button URL (Optional)</label>
                        <input type="text" name="button_url_1" placeholder="e.g. /products/your-product-id" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Secondary Button Text (Optional)</label>
                        <input type="text" name="button_text_2" placeholder="e.g. Visit Shop" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Secondary Button URL (Optional)</label>
                        <input type="text" name="button_url_2" placeholder="e.g. /shops/your-seller-id" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>

                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2 cursor-pointer mt-2">
                            <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-[#C0422A] focus:ring-[#C0422A]/20">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Make visible immediately</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-50 mt-6">
                    <button type="button" @click="showAddModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">Create Banner</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── EDIT MODAL ── --}}
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]"
            @click.away="showEditModal = false">
            <h2 class="font-serif text-2xl font-bold mb-6">Edit <span class="text-[#C0422A] italic">Hero Banner</span></h2>
            <form :action="'/admin/banners/' + editingBanner.id" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Current Image</label>
                        <div class="w-48 aspect-video rounded-xl overflow-hidden bg-gray-50 border border-gray-100 shadow-sm mb-3">
                            <img :src="editingBanner.image_url" class="w-full h-full object-cover">
                        </div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Replace Image (Optional)</label>
                        <input type="file" name="image" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                        <p class="text-[9px] text-gray-400 mt-1 font-medium">Leave empty to keep current image. Max 5MB.</p>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Title</label>
                        <input type="text" name="title" x-model="editingBanner.title" placeholder="e.g. Handcrafted Barong" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Order Index</label>
                        <input type="number" name="order_index" x-model="editingBanner.order_index" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Subtitle / Description</label>
                        <textarea name="subtitle" x-model="editingBanner.subtitle" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all"></textarea>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Primary Button Text</label>
                        <input type="text" name="button_text_1" x-model="editingBanner.button_text_1" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Primary Button URL</label>
                        <input type="text" name="button_url_1" x-model="editingBanner.button_url_1" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Secondary Button Text</label>
                        <input type="text" name="button_text_2" x-model="editingBanner.button_text_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Secondary Button URL</label>
                        <input type="text" name="button_url_2" x-model="editingBanner.button_url_2" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    </div>
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-2 cursor-pointer mt-2">
                            <input type="checkbox" name="is_active" value="1" :checked="editingBanner.is_active" class="rounded border-gray-300 text-[#C0422A] focus:ring-[#C0422A]/20">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">Visible</span>
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 pt-4 border-t border-gray-50 mt-6">
                    <button type="button" @click="showEditModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">Update Banner</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── REJECT MODAL ── --}}
    <div x-show="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="showRejectModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
            <div>
                <h3 class="font-serif text-xl font-bold text-black mb-1">Reject Banner Request</h3>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Banner: <span x-text="rejectBannerTitle" class="text-black"></span></p>
            </div>
            <form :action="rejectRoute" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="text-[9px] font-bold text-gray-400 uppercase tracking-widest block mb-1.5">Reason for Rejection</label>
                    <textarea name="rejection_reason" required rows="4"
                        placeholder="Enter the reason for rejection to notify the seller..."
                        class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-red-500/10 bg-white resize-none"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" @click="showRejectModal = false"
                        class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white text-[10px] font-bold uppercase tracking-widest transition-all rounded-xl">
                        Confirm Reject
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection