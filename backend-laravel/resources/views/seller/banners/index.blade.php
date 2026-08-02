@extends('layouts.seller')

@section('content')
<div class="max-w-4xl mx-auto space-y-8" x-data="{ showAddModal: false }">
    <!-- Header -->
    <div>
        <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Marketing Tools</div>
        <h1 class="font-serif text-3xl font-bold text-black uppercase">
            Hero <span class="text-[#C0422A] italic lowercase">banners</span>
        </h1>
    </div>

    @if(!$isPremium)
        <!-- Upsell view for Non-Premium Sellers -->
        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-xl space-y-8 relative overflow-hidden">
            <div class="absolute -right-32 -bottom-32 w-80 h-80 bg-[#C0422A]/5 rounded-full blur-3xl"></div>
            
            <div class="max-w-xl space-y-4">
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-yellow-500/10 border border-yellow-500/20 text-yellow-600 rounded-full text-[10px] font-bold uppercase tracking-wider">
                    👑 Premium Perk
                </div>
                <h2 class="font-serif text-3xl font-bold tracking-tight text-black">Promote Your Brand on the Homepage</h2>
                <p class="text-sm text-gray-500 leading-relaxed">
                    Hero Banners are the most visible real estate on our platform. As a Premium Seller, you can submit customized banners featuring your best products or artisan story to be shown on the home page carousel.
                </p>
            </div>

            <!-- Features list -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-100">
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        1
                    </div>
                    <h4 class="text-xs font-bold text-black uppercase tracking-wider">Maximum Visibility</h4>
                    <p class="text-[10px] text-gray-400">Your custom designs placed directly at the top of the homepage where all customers land.</p>
                </div>
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center font-bold">
                        2
                    </div>
                    <h4 class="text-xs font-bold text-black uppercase tracking-wider">Custom CTA Buttons</h4>
                    <p class="text-[10px] text-gray-400">Directly link customer clicks to your custom shop page or a specific featured product.</p>
                </div>
                <div class="space-y-2">
                    <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center font-bold">
                        3
                    </div>
                    <h4 class="text-xs font-bold text-black uppercase tracking-wider">Boost Workshop Sales</h4>
                    <p class="text-[10px] text-gray-400">Sellers using homepage banners see a substantial lift in daily shop views and orders.</p>
                </div>
            </div>

            <div class="pt-4">
                <a href="{{ route('seller.subscription.index') }}" class="inline-block px-8 py-3.5 bg-black hover:bg-[#C0422A] text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all">
                    Upgrade to Premium
                </a>
            </div>
        </div>
    @else
        <!-- Premium Dashboard View -->
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <div>
                <p class="text-xs text-gray-400">Submit banner requests to be displayed on the platform homepage.</p>
            </div>
            <button @click="showAddModal = true" class="flex items-center gap-2 px-5 py-2.5 bg-black text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Request Banner
            </button>
        </div>

        @if($banners->isEmpty())
            <div class="bg-white rounded-3xl border border-gray-100 p-16 text-center shadow-sm">
                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h3 class="text-xs font-bold text-black uppercase tracking-widest mb-1">No requested banners</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto">You haven't requested any custom banners yet. Request one to promote your shop's heritage creations!</p>
            </div>
        @else
            <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest w-40">Preview</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Banner Info</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Buttons</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center w-28">Status</th>
                            <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right w-24">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($banners as $banner)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="w-32 aspect-2/1 rounded-xl overflow-hidden bg-gray-50 border border-gray-100 shadow-sm">
                                        <img src="{{ $banner->getImageUrl() }}" class="w-full h-full object-cover">
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="text-sm font-bold text-black">{{ $banner->title ?: 'Untitled Promotion' }}</div>
                                        <div class="text-[10px] text-gray-500 max-w-xs line-clamp-2">{{ $banner->subtitle }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        @if($banner->button_text_1)
                                            <div class="text-[9px] font-bold text-gray-700">Btn 1: {{ $banner->button_text_1 }} &rarr; <span class="font-mono text-gray-400">{{ $banner->button_url_1 }}</span></div>
                                        @endif
                                        @if($banner->button_text_2)
                                            <div class="text-[9px] font-bold text-gray-700">Btn 2: {{ $banner->button_text_2 }} &rarr; <span class="font-mono text-gray-400">{{ $banner->button_url_2 }}</span></div>
                                        @endif
                                        @if(!$banner->button_text_1 && !$banner->button_text_2)
                                            <span class="text-[10px] text-gray-400 italic">No buttons</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="space-y-1">
                                        @if($banner->status === 'approved')
                                            <span class="inline-block px-2.5 py-1 bg-green-50 text-green-700 rounded-full text-[9px] font-black uppercase tracking-wider border border-green-200/50">Approved</span>
                                            @if($banner->is_active)
                                                <div class="text-[8px] font-bold text-green-500">Live on Home</div>
                                            @else
                                                <div class="text-[8px] font-bold text-gray-400">Hidden</div>
                                            @endif
                                        @elseif($banner->status === 'rejected')
                                            <span class="inline-block px-2.5 py-1 bg-red-50 text-red-700 rounded-full text-[9px] font-black uppercase tracking-wider border border-red-200/50" title="Reason: {{ $banner->rejection_reason }}">Rejected</span>
                                            @if($banner->rejection_reason)
                                                <div class="text-[9px] text-red-500 font-medium max-w-[120px] mx-auto line-clamp-2">{{ $banner->rejection_reason }}</div>
                                            @endif
                                        @else
                                            <span class="inline-block px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full text-[9px] font-black uppercase tracking-wider border border-amber-200/50">Pending Review</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <form action="{{ route('seller.banners.destroy', $banner->id) }}" method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this banner?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="p-2 text-gray-400 hover:text-red-600 transition-colors rounded-lg hover:bg-red-50"
                                            title="Delete banner">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pt-4">
                {{ $banners->links() }}
            </div>
        @endif
    @endif

    <!-- Add Banner Request Modal -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-2xl p-8 shadow-2xl overflow-y-auto max-h-[90vh]" @click.away="showAddModal = false">
            <h2 class="font-serif text-2xl font-bold mb-6">Request Homepage <span class="text-[#C0422A] italic">Hero Banner</span></h2>
            <form action="{{ route('seller.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
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
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-50 mt-6">
                    <button type="button" @click="showAddModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
