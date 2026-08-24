@extends('layouts.app')

@section('content')
<div style="min-height:calc(100vh - 80px);background-color:#FAF8F5;padding:24px 16px 48px 16px;">
    <div style="max-width:1120px;margin:0 auto;">

        {{-- Top Header with Heraldic Laurel Wreath --}}
        <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:24px;box-shadow:0 10px 30px rgba(0,0,0,0.04);padding:24px 28px;margin-bottom:28px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                <div style="display:flex;align-items:center;gap:14px;">
                    <!-- Heraldic Laurel Wreath + Star Emblem -->
                    <div style="width:48px;height:48px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg width="46" height="46" viewBox="0 0 48 48" fill="none">
                            <circle cx="24" cy="23" r="10.5" stroke="#C49520" stroke-width="1" stroke-dasharray="2 1.5"/>
                            <circle cx="24" cy="23" r="8.5" stroke="#C49520" stroke-width="0.8"/>
                            <path d="M24 17.5l1.6 3.4 3.7.5-2.7 2.6.6 3.7-3.2-1.7-3.2 1.7.6-3.7-2.7-2.6 3.7-.5L24 17.5z" fill="#C49520"/>
                            <path d="M15 32.5c-4-3.5-6-8.5-6-14 0-3.5 1-6.5 2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M10 12c1.8 1.2 3.5 2.8 4 4.5M8 17.5c2 .6 3.8 1.8 4.8 3.5M8 23.5c2 0 3.8.6 5 2M9.5 29.5c2-.8 3.8-.8 5.2 0M12.5 34c1.8-1.2 3.6-1.5 5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                            <path d="M33 32.5c4-3.5 6-8.5 6-14 0-3.5-1-6.5-2.5-9" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M38 12c-1.8 1.2-3.5 2.8-4 4.5M40 17.5c-2 .6-3.8 1.8-4.8 3.5M40 23.5c-2 0-3.8.6-5 2M38.5 29.5c-2-.8-3.8-.8-5.2 0M35.5 34c-1.8-1.2-3.6-1.5-5-.8" stroke="#C49520" stroke-width="1.2" stroke-linecap="round"/>
                            <path d="M19 36c3 1.2 7 1.2 10 0" stroke="#C49520" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                    </div>
                    <div>
                        <h1 style="font-family:ui-serif,Georgia,Cambria,serif;font-size:22px;font-weight:700;color:#1E1915;letter-spacing:-0.01em;line-height:1.2;margin:0;">
                            My Saved Masterpieces
                        </h1>
                        <p style="font-size:12.5px;color:#78716C;margin-top:3px;margin-bottom:0;">
                            Handcrafted Lumban Barong Tagalog pieces curated in your personal collection
                        </p>
                    </div>
                </div>

                <div style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background-color:#FAF5EA;border:1px solid #E6D8BA;border-radius:20px;font-size:12px;font-weight:700;color:#8C6212;">
                    <span>✦</span>
                    <span>{{ $products->count() }} {{ Str::plural('piece', $products->count()) }} saved</span>
                </div>
            </div>

            {{-- Star Divider --}}
            <div style="position:relative;margin:16px 0 0 0;display:flex;align-items:center;justify-content:center;">
                <div style="width:100%;border-top:1px solid #EAE1D0;"></div>
                <span style="position:absolute;background-color:#FDFBF7;padding:0 12px;color:#C49520;font-size:11px;">✦</span>
            </div>
        </div>

        @if($wishlists->isNotEmpty())
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                @foreach($wishlists as $wishlist)
                    @php $product = $wishlist->product; @endphp
                    @if(!$product) @continue @endif
                    <div class="bg-white rounded-2xl border border-[#ECE3D2] p-4 shadow-2xs flex flex-col justify-between group transition-all duration-300 hover:shadow-md hover:border-[#DFC97A] relative" id="wishlist-card-{{ $product->id }}">
                        
                        <!-- Remove Button -->
                        <button type="button" 
                                onclick="removeWishlist('{{ $product->id }}')" 
                                style="position:absolute;top:18px;right:18px;z-index:15;width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,0.92);border:1px solid #E2D9C8;display:flex;align-items:center;justify-content:center;color:#8C827A;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,0.06);transition:all 0.2s;"
                                onmouseover="this.style.color='#DC2626';this.style.borderColor='#FECACA';this.style.background='#FEF2F2';"
                                onmouseout="this.style.color='#8C827A';this.style.borderColor='#E2D9C8';this.style.background='rgba(255,255,255,0.92)';"
                                title="Remove from wishlist">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>

                        <div>
                            <!-- Product Image -->
                            <a href="/products/{{ $product->id }}" class="block aspect-4/5 bg-[#FAF8F5] rounded-xl overflow-hidden mb-3 relative">
                                <img src="{{ $product->getImageUrl() }}" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out">
                                
                                @if($product->is_on_sale && $product->discount_percentage > 0)
                                    <div style="position:absolute;top:6px;left:6px;display:flex;flex-direction:column;gap:4px;z-index:10;pointer-events:none;">
                                        <div style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px 3px 5px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:20px;box-shadow:0 0 8px rgba(180,130,15,0.45),inset 0 1px 0 rgba(230,185,60,0.12);white-space:nowrap;">
                                            <img src="/images/logo-icon.png" alt="LumBarong" style="width:13px;height:13px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                                            <span style="color:#DFC97A;font-family:ui-sans-serif,system-ui,sans-serif;font-size:7px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;">Lumban Special</span>
                                        </div>
                                        <div style="display:inline-flex;align-items:baseline;padding:3px 8px;background:linear-gradient(90deg,#7A5505 0%,#C8890A 25%,#E8AD12 50%,#C8890A 75%,#7A5505 100%);border:1px solid #5C3E04;border-radius:20px;box-shadow:0 2px 10px rgba(200,137,10,0.5),inset 0 1px 0 rgba(255,220,80,0.25);white-space:nowrap;width:fit-content;">
                                            <span style="color:#FFF8E0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:13px;font-weight:900;line-height:1;letter-spacing:-0.02em;">-{{ number_format($product->discount_percentage, 0) }}%</span>
                                            <span style="color:#FFE8A0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-left:2px;">OFF</span>
                                        </div>
                                    </div>
                                @elseif($product->target_group)
                                    @php
                                        $tgVal = strtolower(trim($product->target_group));
                                    @endphp
                                    <div style="position:absolute;top:6px;left:6px;display:inline-flex;align-items:center;gap:5px;padding:3px 7px 3px 5px;background:linear-gradient(135deg,#131E2E 0%,#0B111A 100%);border:1px solid #A87B10;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.45),inset 0 1px 0 rgba(230,185,60,0.2);white-space:nowrap;z-index:10;pointer-events:none;">
                                        @if($tgVal === 'men')
                                            <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                                <path fill="#DFC97A" d="M12 2l-2.5 5 2.5 1.5 2.5-1.5L12 2zm-4.5 5.5L3 9v13h7v-9l-2.5-2.5zm9 0l-2.5 2.5v9h7V9l-4.5-1.5zM11 9.5v8l1 3.5 1-3.5v-8l-1 1-1-1z"/>
                                            </svg>
                                        @elseif($tgVal === 'women')
                                            <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                                <path fill="#DFC97A" d="M12 2a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4zm-2.5 5.5L7 11.5l2 1.5-2 9h10l-2-9 2-1.5-2.5-4h-5zM11 9h2l1 3.5-2 1.5-2-1.5L11 9z"/>
                                            </svg>
                                        @elseif($tgVal === 'kids')
                                            <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                                <path fill="#DFC97A" d="M12 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm-4 7.5c-1.4 0-2.5 1.1-2.5 2.5v3.5c0 .8.7 1.5 1.5 1.5H8V21h8v-4h1c.8 0 1.5-.7 1.5-1.5V12c0-1.4-1.1-2.5-2.5-2.5h-8z"/>
                                            </svg>
                                        @else
                                            <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                                <path fill="#DFC97A" d="M12 2l2.4 7.2h7.6l-6.1 4.5 2.3 7.3L12 16.5 5.8 21l2.3-7.3L2 9.2h7.6z"/>
                                            </svg>
                                        @endif
                                        <div style="width:1px;height:9px;background:rgba(223,201,122,0.35);flex-shrink:0;"></div>
                                        <span style="color:#DFC97A;font-family:ui-serif,Georgia,Cambria,'Times New Roman',serif;font-size:7.5px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;line-height:1;">{{ $product->target_group }}</span>
                                    </div>
                                @endif
                                @if($wishlist->size)
                                    <div class="absolute bottom-2.5 left-2.5 bg-black/80 backdrop-blur-xs text-white text-[9px] font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-lg border border-white/20">
                                        Size: {{ $wishlist->size }}
                                    </div>
                                @endif
                            </a>

                            <!-- Category & Artisan -->
                            <div class="flex items-center justify-between text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                                <span>{{ $product->category->name ?? 'Barong Tagalog' }}</span>
                                <span class="text-[#996515] font-extrabold truncate max-w-28">by {{ $product->artisan ?? $product->seller->shopName ?? 'Artisan' }}</span>
                            </div>

                            <!-- Title -->
                            <a href="/products/{{ $product->id }}" class="font-extrabold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2 mb-2 block uppercase tracking-tight">
                                {{ $product->name }}
                            </a>

                            <!-- Price -->
                            <div class="flex items-baseline gap-2 mb-3">
                                <span class="text-base font-extrabold text-gray-900">₱{{ number_format($product->salePrice) }}</span>
                                @if($product->is_on_sale && $product->discount_percentage > 0)
                                    <span class="text-xs font-bold text-gray-400 line-through">₱{{ number_format($product->price) }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Bottom Actions -->
                        <div class="pt-3 border-t border-[#ECE3D2] flex items-center gap-2">
                            <a href="/products/{{ $product->id }}" class="flex-1 h-9 rounded-xl bg-[#1E1915] hover:bg-[#C0422A] text-white font-bold text-xs flex items-center justify-center gap-1.5 transition-all shadow-xs uppercase tracking-wider">
                                <svg class="w-3.5 h-3.5 text-[#DFC97A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <span>View Details</span>
                            </a>
                        </div>

                    </div>
                @endforeach
            </div>
        @else
            <!-- Empty State -->
            <div style="background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;padding:48px 24px;text-align:center;box-shadow:0 10px 30px rgba(0,0,0,0.04);max-width:520px;margin:32px auto;">
                <div style="width:64px;height:64px;border-radius:50%;background-color:#FAF5EA;border:1px solid #E6D8BA;display:flex;align-items:center;justify-content:center;margin:0 auto 16px auto;color:#C49520;">
                    <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </div>
                <h3 style="font-family:ui-serif,Georgia,serif;font-size:20px;font-weight:700;color:#1E1915;margin-bottom:6px;">Your Wishlist is Empty</h3>
                <p style="font-size:12.5px;color:#78716C;margin-bottom:24px;line-height:1.5;">Save handcrafted Barong Tagalog & Filipiniana pieces you love to easily view or purchase them later.</p>
                <a href="/" style="display:inline-flex;align-items:center;justify-content:center;padding:10px 24px;border-radius:14px;background-color:#1E1915;color:#FFFFFF;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:0.12em;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:all 0.2s;"
                   onmouseover="this.style.backgroundColor='#C0422A';"
                   onmouseout="this.style.backgroundColor='#1E1915';">
                    Explore Collection
                </a>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    async function removeWishlist(productId) {
        try {
            const res = await fetch('/wishlist/toggle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ product_id: productId })
            });
            const data = await res.json();
            if (data.success) {
                const card = document.getElementById('wishlist-card-' + productId);
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(() => card.remove(), 200);
                }
                if (window.Alpine && Alpine.store('toast')) {
                    Alpine.store('toast').trigger('Item removed from wishlist', 'info');
                }
            }
        } catch(e) {}
    }
</script>
@endpush
@endsection
