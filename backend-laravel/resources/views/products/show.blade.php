@extends('layouts.app')

@section('content')
@php
    use App\Support\VariationFormatter;
    $galleryImages = VariationFormatter::buildGalleryImages($product->image, $product);
    $styleVariants = VariationFormatter::buildStyleVariants($product);
    $variantCards = VariationFormatter::buildVariantImageCards($product, $galleryImages);
    $productVariations = $styleVariants; // Backward compatibility
    $isAdminUser = Auth::check() && in_array(Auth::user()->role, ['admin', 'superadmin']);
    $isProductOwner = Auth::check() && Auth::user()->role === 'seller' && Auth::id() === $product->sellerId;
    $productStatus = strtolower($product->status ?? 'pending');
    $adminCatalogUrl = Auth::check() && Auth::user()->role === 'superadmin' ? route('superadmin.products') : route('admin.products');
    $statusBadgeClass = match($productStatus) {
        'approved' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
        'rejected' => 'bg-rose-500/20 text-rose-300 border border-rose-500/30',
        default    => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
    };
    $cardStatusBadgeClass = match($productStatus) {
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
        default    => 'bg-amber-100 text-amber-800',
    };
@endphp
{{-- Alpine productDetail component MUST be registered here (before the x-data div below).
     Alpine.js loads with `defer`, which means `alpine:init` fires during DOM parse before
     @stack('scripts') at the bottom of body.blade.php. Registering here ensures the
     component is available when Alpine processes the x-data attribute. --}}
<div id="product-page-data"
    data-logged-in="{{ auth()->check() ? 'true' : 'false' }}"
    data-login-url="{{ route('login') }}"
    data-product-id="{{ $product->id }}"
    data-is-wishlisted="{{ ($isWishlisted ?? false) ? 'true' : 'false' }}"
    data-default-image-url="{{ $product->getImageUrl() }}"
    data-csrf-token="{{ csrf_token() }}"
    style="display:none;" aria-hidden="true">
</div>
<script>
    var _pd = document.getElementById('product-page-data') ? document.getElementById('product-page-data').dataset : {};
    window.isLoggedIn = _pd.loggedIn === 'true';
    window.loginUrl   = _pd.loginUrl;

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[action="/cart/add"], form[action="/checkout"]').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                if (!window.isLoggedIn) {
                    e.preventDefault();
                    var pId = form.querySelector('[name="productId"]')?.value || (_pd ? _pd.productId : '');
                    var sz  = form.querySelector('[name="size"]')?.value || '';
                    var qty = parseInt(form.querySelector('[name="quantity"]')?.value || '1', 10);
                    var varLbl = form.querySelector('[name="variation"]')?.value || 'Original';
                    var act = form.getAttribute('action') === '/checkout' ? 'buy_now' : 'add_to_cart';

                    var intent = {
                        action: act,
                        productId: pId,
                        quantity: qty,
                        size: sz,
                        variation: varLbl,
                        redirectUrl: act === 'buy_now' ? '/checkout?mode=buy_now' : window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                }
            });
        });
    });

    function productDetail(defaultStock, sizeStocks, galleryImages, styleVariants, saleEndsAt, variantCards) {
        var dataEl = document.getElementById('product-page-data');
        var dataset = dataEl ? dataEl.dataset : {};
        var isWishlistedInitial = dataset.isWishlisted === 'true';
        var productId = dataset.productId || '';
        var defaultProductImageUrl = dataset.defaultImageUrl || '';
        var csrfToken = dataset.csrfToken || '';

        var galleryList = Array.isArray(galleryImages) ? galleryImages : [];
        var styleList = Array.isArray(styleVariants) ? styleVariants : [];
        var cardsList = Array.isArray(variantCards) ? variantCards : [];

        return {
            selectedSize: '',
            quantity: 1,
            defaultStock: defaultStock || 1,
            stock: defaultStock || 1,
            sizeStocks: sizeStocks || {},
            activeImage: 0,
            galleryImages: galleryList,
            styleVariants: styleList,
            variantCards: cardsList,
            selectedCardId: cardsList.length > 0 ? cardsList[0].card_id : null,
            selectedStyle: styleList.length > 0 ? styleList[0].id : 0,
            selectedVariation: 0,
            // Compatibility accessor for any legacy references
            get variations() {
                return this.galleryImages;
            },
            isCardActive: function(card) {
                if (this.selectedCardId !== null && this.selectedCardId !== undefined) {
                    return this.selectedCardId === card.card_id;
                }
                return this.selectedStyle === card.variant_id;
            },
            selectVariantCard: function(card) {
                this.selectedCardId = card.card_id;
                this.selectedStyle = card.variant_id;
                this.selectedVariation = card.variant_id;
                if (card.gallery_index !== undefined && card.gallery_index !== -1 && card.gallery_index !== null) {
                    this.activeImage = card.gallery_index;
                } else {
                    var self = this;
                    var idx = this.galleryImages.findIndex(function(img) {
                        return (img.url && card.image_url && img.url === card.image_url) ||
                               (img.path && card.image_path && img.path === card.image_path);
                    });
                    if (idx !== -1) {
                        this.activeImage = idx;
                    }
                }
            },
            selectImage: function(index) {
                this.activeImage = index;
                var img = this.galleryImages && this.galleryImages[index];
                if (img && img.variant_id !== undefined && img.variant_id !== null) {
                    var targetId = img.variant_id;
                    var found = this.styleVariants.find(function(v) { return v.id === targetId; });
                    if (found) {
                        this.selectedStyle = targetId;
                        this.selectedVariation = targetId;
                        var self = this;
                        var matchCard = this.variantCards.find(function(c) {
                            return c.variant_id === targetId && c.gallery_index === index;
                        }) || this.variantCards.find(function(c) {
                            return c.variant_id === targetId;
                        });
                        if (matchCard) {
                            this.selectedCardId = matchCard.card_id;
                        }
                    }
                }
            },
            selectStyleVariant: function(variant) {
                this.selectedStyle = variant.id;
                this.selectedVariation = variant.id;
                var self = this;
                var matchCard = this.variantCards.find(function(c) {
                    return c.variant_id === variant.id;
                });
                if (matchCard) {
                    this.selectedCardId = matchCard.card_id;
                }
                var imgIdx = this.galleryImages.findIndex(function(img) {
                    return img.variant_id === variant.id;
                });
                if (imgIdx === -1 && (variant.image_path || variant.image || variant.image_url)) {
                    var targetPath = variant.image_path;
                    var targetUrl = variant.image || variant.image_url;
                    imgIdx = this.galleryImages.findIndex(function(img) {
                        return (targetPath && img.path === targetPath) || 
                               (targetUrl && (img.url === targetUrl || img.path === targetUrl));
                    });
                }
                if (imgIdx !== -1) {
                    this.activeImage = imgIdx;
                }
            },
            showSizeGuide: false,
            adminRejectModal: false,
            adminRejectReason: '',
            openAdminReject() {
                this.adminRejectReason = '';
                this.adminRejectModal = true;
            },
            closeAdminReject() {
                this.adminRejectModal = false;
                this.adminRejectReason = '';
            },

            // ─── Buy Now Bottom Sheet (Mobile Shopee-Style) ───
            showBuyNowSheet: false,
            buyNowMode: 'buy_now', // 'buy_now' or 'add_to_cart'
            openBuyNowSheet(mode) {
                this.buyNowMode = mode || 'buy_now';
                this.showBuyNowSheet = true;
                document.body.style.overflow = 'hidden';
                var self = this;
                var match = this.variantCards.find(function(c) {
                    return c.variant_id === self.selectedStyle && c.gallery_index === self.activeImage;
                }) || this.variantCards.find(function(c) {
                    return c.variant_id === self.selectedStyle;
                });
                if (match) {
                    this.selectedCardId = match.card_id;
                }
            },
            closeBuyNowSheet() {
                this.showBuyNowSheet = false;
                document.body.style.overflow = '';
            },
            executeBuyNow() {
                if (!this.selectedSize) {
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').trigger('Please select a size first.', 'info');
                    }
                    return;
                }
                if (this.buyNowMode === 'add_to_cart') {
                    // Submit the hidden cart form
                    var cartForm = document.getElementById('mainCartForm');
                    if (cartForm) {
                        this.submitAddToCart({ target: cartForm, preventDefault: function(){} });
                    }
                    this.closeBuyNowSheet();
                    return;
                }
                if (!window.isLoggedIn) {
                    var intent = {
                        action: 'buy_now',
                        productId: productId,
                        quantity: this.quantity,
                        size: this.effectiveSize(),
                        variation: this.selectedVariationLabel(),
                        redirectUrl: '/checkout?mode=buy_now'
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                    return;
                }
                window.location.href = '/checkout?productId=' + productId + '&size=' + encodeURIComponent(this.effectiveSize()) + '&quantity=' + this.quantity + '&variation=' + encodeURIComponent(this.selectedVariationLabel()) + '&direct=1';
                this.closeBuyNowSheet();
            },

            // ─── Mobile Sticky Tabs & Scroll-Spy State ───
            activeMobileTab: 'overview',
            scrollToSection(id) {
                this.activeMobileTab = id;
                const el = document.getElementById(id);
                if (el) {
                    const yOffset = -92; // Height offset for sticky search + tabs
                    const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
                    window.scrollTo({ top: y, behavior: 'smooth' });
                }
            },
            initScrollSpy() {
                const sectionIds = ['overview', 'reviews', 'details', 'recommendations'];
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.activeMobileTab = entry.target.id;
                        }
                    });
                }, {
                    rootMargin: '-80px 0px -65% 0px',
                    threshold: 0.05
                });
                sectionIds.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) observer.observe(el);
                });
            },

            // ─── Countdown Timer for Lumbarong Seller Sales ───
            saleEndsAt: saleEndsAt || '',
            countdownHours: '00',
            countdownMinutes: '00',
            countdownSeconds: '00',
            countdownActive: false,
            init() {
                if (this.saleEndsAt) {
                    this.initCountdown();
                }
                this.$nextTick(() => {
                    this.initScrollSpy();
                });
            },
            initCountdown() {
                const update = () => {
                    const diff = new Date(this.saleEndsAt).getTime() - Date.now();
                    if (diff <= 0) {
                        this.countdownActive = false;
                        this.countdownHours = '00';
                        this.countdownMinutes = '00';
                        this.countdownSeconds = '00';
                        return;
                    }
                    this.countdownActive = true;
                    const totalHours = Math.floor(diff / (1000 * 60 * 60));
                    const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                    const s = Math.floor((diff % (1000 * 60)) / 1000);
                    this.countdownHours = String(totalHours).padStart(2, '0');
                    this.countdownMinutes = String(m).padStart(2, '0');
                    this.countdownSeconds = String(s).padStart(2, '0');
                };
                update();
                setInterval(update, 1000);
            },
            
            // ─── Shopee-Style Hover Zoom Inspection State ───
            showZoomModal: false,
            zoomOriginX: 50,
            zoomOriginY: 50,
            isZoomed: false,

            openZoomModal(idx) {
                if (idx !== undefined) {
                    this.selectImage(idx);
                }
                this.isZoomed = false;
                this.zoomOriginX = 50;
                this.zoomOriginY = 50;
                this.showZoomModal = true;
                document.body.style.overflow = 'hidden';
            },
            closeZoomModal() {
                this.showZoomModal = false;
                this.isZoomed = false;
                document.body.style.overflow = '';
            },
            handleModalMouseMove(e) {
                const rect = e.currentTarget.getBoundingClientRect();
                const x = ((e.clientX - rect.left) / rect.width) * 100;
                const y = ((e.clientY - rect.top) / rect.height) * 100;
                this.zoomOriginX = Math.min(Math.max(x, 0), 100).toFixed(2);
                this.zoomOriginY = Math.min(Math.max(y, 0), 100).toFixed(2);
                this.isZoomed = true;
            },
            handleModalMouseLeave() {
                this.isZoomed = false;
            },
            handleModalTouch(e) {
                if (e.touches && e.touches.length > 0) {
                    const rect = e.currentTarget.getBoundingClientRect();
                    const touch = e.touches[0];
                    const x = ((touch.clientX - rect.left) / rect.width) * 100;
                    const y = ((touch.clientY - rect.top) / rect.height) * 100;
                    this.zoomOriginX = Math.min(Math.max(x, 0), 100).toFixed(2);
                    this.zoomOriginY = Math.min(Math.max(y, 0), 100).toFixed(2);
                    this.isZoomed = true;
                }
            },
            selectedColorName: 'Off-White',
            isWishlisted: isWishlistedInitial,
            colorSwatches: [
                { name: 'Off-White', hex: '#F9F8F6' },
                { name: 'Ivory', hex: '#EBE4D5' },
                { name: 'Navy Blue', hex: '#1E293B' },
                { name: 'Natural Linen', hex: '#D6C8B4' },
                { name: 'Black', hex: '#18181B' },
                { name: 'Classic Cream', hex: '#F5EAD9' }
            ],
            effectiveSize: function() {
                return this.selectedSize || '';
            },
            toggleWishlist: async function() {
                var hasSizes = Object.keys(this.sizeStocks || {}).length > 0;
                if (hasSizes && !this.selectedSize) {
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').trigger('Please select your preferred size first before saving to your wishlist.', 'info');
                    } else {
                        alert('Please select your preferred size first before saving to your wishlist.');
                    }
                    return;
                }

                if (!window.isLoggedIn) {
                    const intent = {
                        action: 'wishlist',
                        productId: productId,
                        size: this.selectedSize || null,
                        redirectUrl: window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                    return;
                }
                try {
                    var res = await fetch('/wishlist/toggle', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ 
                            product_id: productId,
                            size: this.selectedSize || null
                        })
                    });
                    var data = await res.json();
                    this.isWishlisted = data.status === 'added';
                    if (window.Alpine && Alpine.store('toast')) {
                        Alpine.store('toast').trigger(data.message, data.status === 'added' ? 'success' : 'info');
                    }
                } catch(e) {
                    this.isWishlisted = !this.isWishlisted;
                }
            },
            imageUrl: function(url) {
                if (!url) return defaultProductImageUrl;
                if (url.startsWith('http') || url.startsWith('/')) return url;
                if (url.startsWith('products/')) return '/storage/' + url;
                if (url.startsWith('uploads/')) return '/' + url;
                return '/uploads/products/' + url;
            },
            selectedVariationLabel: function() {
                if (this.styleVariants && this.styleVariants.length > 0) {
                    var self = this;
                    var found = this.styleVariants.find(function(v) { return v.id === self.selectedStyle; });
                    return found ? (found.name || 'Original') : (this.styleVariants[0]?.name || 'Original');
                }
                return 'Original';
            },
            updateStock: function(size) {
                this.selectedSize = size;
                if (this.sizeStocks && this.sizeStocks[size] !== undefined) {
                    this.stock = parseInt(this.sizeStocks[size]) || 0;
                } else {
                    this.stock = this.defaultStock;
                }
                if (this.quantity > this.stock) {
                    this.quantity = Math.max(1, this.stock);
                }
            },
            submitAddToCart: async function(e) {
                if (!window.isLoggedIn) {
                    const intent = {
                        action: 'add_to_cart',
                        productId: '{{ $product->id }}',
                        quantity: this.quantity,
                        size: this.effectiveSize(),
                        variation: this.selectedVariationLabel(),
                        redirectUrl: window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                    return;
                }
                try {
                    var formData = new FormData(e.target);
                    var response = await fetch('/cart/add', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: formData
                    });
                    if (response.ok) {
                        var data = await response.json();
                        if (data.success) {
                            window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                            Alpine.store('toast').trigger('Product successfully added to cart!', 'success');
                        }
                    } else {
                        var errData = await response.json();
                        Alpine.store('toast').trigger(errData.message || 'Failed to add item to cart.', 'error');
                    }
                } catch(err) {
                    Alpine.store('toast').trigger('Something went wrong. Please try again.', 'error');
                }
            },
            chatWithSeller: function(sellerId, sellerName) {
                if (!window.isLoggedIn) {
                    var intent = {
                        action: 'chat',
                        sellerId: sellerId,
                        sellerName: sellerName,
                        redirectUrl: window.location.href
                    };
                    try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                    window.location.href = window.loginUrl;
                    return;
                }
                window.dispatchEvent(new CustomEvent('open-chat', { 
                    detail: { sellerId: sellerId, sellerName: sellerName } 
                }));
            }
        };
    }
</script>
<div class="max-w-6xl mx-auto py-4 lg:py-6 pb-24 lg:pb-6" x-data="productDetail({{ (int)($product->stock ?? 1) }}, @js($product->size_stocks ?? (object)[]), @js($galleryImages), @js($styleVariants), '{{ $product->sale_ends_at ? $product->sale_ends_at->toISOString() : '' }}', @js($variantCards))">
    @if($isAdminUser)
    <!-- Admin Context Header Bar -->
    <div class="mb-5 px-4 py-3 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md"
         style="background-color: #1E1915; border: 1px solid #382F28; color: #FFFFFF;">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                 style="background-color: rgba(192, 66, 10, 0.25); border: 1px solid rgba(192, 66, 10, 0.5); color: #F59E0B;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
            </div>
            <div class="flex items-center gap-2 flex-wrap text-xs">
                <span class="font-bold uppercase tracking-wider text-amber-400">Admin Preview Mode</span>
                <span class="text-stone-500">•</span>
                <span class="font-semibold text-stone-300">Status:</span>
                @if($productStatus === 'approved')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                          style="background-color: #064E3B; color: #A7F3D0; border: 1px solid #059669;">
                        Approved
                    </span>
                @elseif($productStatus === 'rejected')
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                          style="background-color: #881337; color: #FECDD3; border: 1px solid #E11D48;">
                        Rejected
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                          style="background-color: #78350F; color: #FDE68A; border: 1px solid #D97706;">
                        Pending Review
                    </span>
                @endif
                @if($product->stock <= 0)
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                          style="background-color: #450A0A; color: #FCA5A5; border: 1px solid #991B1B;">
                        Out of Stock
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ $adminCatalogUrl }}" 
               class="px-3.5 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all flex items-center gap-1.5 shadow-sm hover:opacity-90 cursor-pointer"
               style="background-color: rgba(255, 255, 255, 0.12); color: #FFFFFF; border: 1px solid rgba(255, 255, 255, 0.25);">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Back to Catalog</span>
            </a>
        </div>
    </div>
    @endif

    <!-- Mobile App Header (Sticky Search + Directory Tabs) -->
    <div class="lg:hidden sticky top-0 z-40 bg-white border-b border-gray-100 shadow-2xs" style="position: sticky; top: 0; z-index: 40;">
        {{-- Row 1: Back Navigation + Pink Bordered Search Box --}}
        <div class="px-3 py-2 flex items-center gap-2.5">
            <button type="button" onclick="window.history.length > 1 ? window.history.back() : window.location.href = '/'" class="p-1 text-gray-800 hover:text-black cursor-pointer shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>

            <div class="flex-1 relative">
                <form action="/" method="GET" class="m-0">
                    <div class="flex items-center bg-white border border-stone-300 focus-within:border-[#A67C2E] focus-within:ring-2 focus-within:ring-[#A67C2E]/15 rounded-full pl-2.5 pr-2 py-1 shadow-2xs transition-all">
                        <svg class="w-3.5 h-3.5 text-stone-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="search" value="barong tagalog" placeholder="Search Barongs..." class="w-full bg-transparent text-gray-900 placeholder-gray-400 text-xs pl-2 pr-1 outline-none font-medium">
                    </div>
                </form>
            </div>
        </div>

        {{-- Row 2: Mobile Tab Bar (Overview | Reviews | Product Details | Recommendation) --}}
        <div class="border-t border-gray-100 flex items-center justify-around text-xs tracking-tight px-1 bg-white">
            <button type="button" @click="scrollToSection('overview')" 
                    class="py-2.5 px-2 transition-all cursor-pointer border-b-2 font-semibold"
                    :class="activeMobileTab === 'overview' ? 'text-[#A67C2E] font-bold border-[#A67C2E]' : 'text-gray-500 border-transparent hover:text-gray-900'">
                Overview
            </button>
            <button type="button" @click="scrollToSection('reviews')" 
                    class="py-2.5 px-2 transition-all cursor-pointer border-b-2 font-semibold"
                    :class="activeMobileTab === 'reviews' ? 'text-[#A67C2E] font-bold border-[#A67C2E]' : 'text-gray-500 border-transparent hover:text-gray-900'">
                Reviews
            </button>
            <button type="button" @click="scrollToSection('details')" 
                    class="py-2.5 px-2 transition-all cursor-pointer border-b-2 font-semibold"
                    :class="activeMobileTab === 'details' ? 'text-[#A67C2E] font-bold border-[#A67C2E]' : 'text-gray-500 border-transparent hover:text-gray-900'">
                Product Details
            </button>
            <button type="button" @click="scrollToSection('recommendations')" 
                    class="py-2.5 px-2 transition-all cursor-pointer border-b-2 font-semibold"
                    :class="activeMobileTab === 'recommendations' ? 'text-[#A67C2E] font-bold border-[#A67C2E]' : 'text-gray-500 border-transparent hover:text-gray-900'">
                Recommendation
            </button>
        </div>
    </div>

    <!-- Breadcrumb Navigation (Desktop Only) -->
    <nav class="hidden lg:flex items-center gap-2 text-xs font-semibold text-gray-500 mb-5">
        <a href="/" class="hover:text-black transition-colors">Home</a>
        <span>&gt;</span>
        <a href="/?category={{ urlencode($product->category->name ?? 'Barong Tagalog') }}" class="hover:text-black transition-colors">{{ $product->category->name ?? 'Barong Tagalog' }}</a>
        <span>&gt;</span>
        <span class="text-gray-900 font-bold truncate max-w-62.5 sm:max-w-none">{{ $product->name }}</span>
    </nav>

    <!-- Product Detail Main Container Card -->
    <div class="bg-white rounded-none lg:rounded-3xl border-0 lg:border border-gray-100 shadow-none lg:shadow-sm overflow-hidden p-0 sm:p-4 lg:p-10">
        <div id="overview" class="grid grid-cols-1 lg:grid-cols-12 gap-0 lg:gap-12 items-start scroll-mt-24">
            
            <!-- Left Side: Product Images Gallery (Vertical Thumbnails + Main Image) -->
            <div class="lg:col-span-5 flex flex-col-reverse sm:flex-row gap-3 sm:gap-4 items-start">
                
                <!-- Vertical Gallery Thumbnails (Desktop/Tablet Only) -->
                <div class="hidden sm:flex sm:flex-col gap-3 overflow-x-auto sm:overflow-y-auto max-h-115 no-scrollbar shrink-0 w-full sm:w-20">
                    <template x-for="(img, index) in galleryImages" :key="index">
                        <button 
                            @click="selectImage(index)"
                            class="relative w-14 h-18 sm:w-16 sm:h-20 rounded-xl overflow-hidden shrink-0 border-2 transition-all shadow-2xs"
                            :class="activeImage === index ? 'border-amber-600 ring-2 ring-amber-500/20 opacity-100 scale-98' : 'border-gray-200 opacity-60 hover:opacity-100'"
                        >
                            <img :src="imageUrl(img.url)" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>

                <!-- Main Image Display Box with Direct Click-to-Zoom Inspection -->
                <div 
                    class="flex-1 min-w-0 w-full relative aspect-4/5 bg-gray-50 rounded-none sm:rounded-2xl overflow-hidden border-0 sm:border border-gray-100 shadow-none sm:shadow-xs group select-none cursor-zoom-in"
                    @click="openZoomModal(activeImage)"
                    title="Click to inspect and zoom"
                >
                    <!-- Main Image Display -->
                    <template x-for="(img, index) in galleryImages" :key="index">
                        <img 
                            x-show="activeImage === index"
                            :src="imageUrl(img.url)"
                            onerror="this.src='/uploads/products/default.jpg'"
                            class="w-full h-full object-contain group-hover:scale-105 transition-transform duration-300 ease-out p-1"
                            alt="{{ $product->name }}"
                        >
                    </template>

                    {{-- Desktop Only Badges (Top-Left) --}}
                    @if($product->is_on_sale && $product->discount_percentage > 0)
                        <div class="hidden lg:flex" style="position:absolute;top:8px;left:8px;display:flex;flex-direction:column;gap:5px;z-index:10;pointer-events:none;">
                            <div style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px 5px 8px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:20px;box-shadow:0 0 8px rgba(180,130,15,0.45),inset 0 1px 0 rgba(230,185,60,0.12);white-space:nowrap;">
                                <img src="/images/logo-icon.png" alt="LumBarong" style="width:16px;height:16px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                                <span style="color:#DFC97A;font-family:ui-sans-serif,system-ui,sans-serif;font-size:8.5px;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;">Lumbarong Specials &amp; Promo</span>
                            </div>
                            <div style="display:inline-flex;align-items:baseline;padding:5px 12px;background:linear-gradient(90deg,#7A5505 0%,#C8890A 25%,#E8AD12 50%,#C8890A 75%,#7A5505 100%);border:1px solid #5C3E04;border-radius:20px;box-shadow:0 2px 10px rgba(200,137,10,0.5),inset 0 1px 0 rgba(255,220,80,0.25);white-space:nowrap;width:fit-content;">
                                <span style="color:#FFF8E0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:16px;font-weight:900;line-height:1;letter-spacing:-0.02em;">-{{ number_format($product->discount_percentage, 0) }}%</span>
                                <span style="color:#FFE8A0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;margin-left:3px;">OFF</span>
                            </div>
                        </div>
                    @endif



                    {{-- Mobile Only: Image Counter Pill (Bottom-Right of Image) --}}
                    <div class="lg:hidden absolute bottom-2 right-2 z-10 px-2 py-0.5 rounded-full bg-black/60 text-white text-[10px] font-bold pointer-events-none" x-show="galleryImages && galleryImages.length > 0">
                        <span x-text="(activeImage + 1) + '/' + galleryImages.length"></span>
                    </div>

                    <!-- Desktop Zoom Helper Hint Badge (Bottom Right) -->
                    <div class="hidden lg:flex absolute bottom-3.5 right-3.5 z-10 px-3 py-1.5 rounded-full bg-white/90 backdrop-blur-md border border-gray-200 shadow-sm items-center gap-1.5 text-gray-700 pointer-events-none opacity-85 group-hover:opacity-100 transition-opacity">
                        <svg class="w-3.5 h-3.5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                        </svg>
                        <span class="text-[10px] font-bold tracking-tight">Click to Zoom</span>
                    </div>

                    <!-- Desktop Image Counter Badge (Top Right) -->
                    <div class="hidden lg:block absolute top-2.5 right-2.5 z-10 px-2 py-1 rounded-full bg-black/50 text-white text-[10px] font-bold pointer-events-none" x-show="galleryImages && galleryImages.length > 1">
                        <span x-text="(activeImage + 1) + '/' + galleryImages.length"></span>
                    </div>
                </div>
            </div>

            {{-- ═══ Mobile-Only: Horizontal Mini-Thumbnails Ribbon (Directly Beneath Main Image) ═══ --}}
            <div class="lg:hidden bg-white p-2.5 border-b border-gray-100 flex items-center gap-2 overflow-x-auto no-scrollbar">
                <template x-for="(img, idx) in galleryImages" :key="idx">
                    <button 
                        type="button" 
                        @click="selectImage(idx)" 
                        class="relative w-12 h-14 rounded-lg overflow-hidden shrink-0 border-2 transition-all cursor-pointer bg-gray-50"
                        :class="activeImage === idx ? 'border-[#C89B55] ring-2 ring-[#C89B55]/30' : 'border-gray-200 opacity-70 hover:opacity-100'"
                    >
                        <img :src="imageUrl(img.url)" class="w-full h-full object-cover">
                    </button>
                </template>
            </div>

            {{-- ═══ Mobile-Only: Pricing & Promo Section ═══ --}}
            @php
                $calcDiff = ($product->price ?? 0) - ($product->salePrice ?? 0);
            @endphp
            <div class="lg:hidden bg-white px-3.5 pt-3 pb-2.5 border-b border-gray-100">
                {{-- Promo Discount Line with Realtime Countdown (Only shown when product is on sale) --}}
                @if($product->is_on_sale && $product->discount_percentage > 0)
                    <div class="flex items-center justify-between text-xs font-bold text-[#A67C2E] mb-1">
                        @if($calcDiff > 0)
                            <span>₱{{ number_format($calcDiff, 2) }} off with Promo</span>
                        @else
                            <span>Lumbarong Specials &amp; Promo</span>
                        @endif
                        <template x-if="countdownActive">
                            <div class="flex items-center gap-1 font-mono text-[11px] font-black">
                                <span class="text-gray-300">|</span>
                                <span x-text="countdownHours + ':' + countdownMinutes + ':' + countdownSeconds">00:23:27</span>
                            </div>
                        </template>
                    </div>
                @endif

                {{-- Primary Bold Price (Dark Antique Gold) --}}
                <div class="flex items-baseline gap-2">
                    <span class="text-2xl sm:text-3xl font-black text-[#A67C2E] tracking-tight">₱{{ number_format($product->salePrice, 2) }}</span>
                    @if(($product->price ?? 0) > ($product->salePrice ?? 0))
                        <span class="text-xs text-gray-400 line-through">₱{{ number_format($product->price, 2) }}</span>
                    @endif
                </div>
            </div>

            {{-- ═══ Mobile-Only: Product Title, Tags, Metrics & Social Action Row ═══ --}}
            <div class="lg:hidden bg-white px-3.5 py-3 border-b border-gray-100">
                <div class="flex items-start justify-between gap-2">
                    <h1 class="text-sm sm:text-base font-bold text-gray-900 leading-snug flex-1">
                        {{ $product->name }}
                    </h1>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>

                {{-- Ratings & Sold Count Row --}}
                <div class="flex items-center justify-between pt-3 mt-2.5 border-t border-gray-50 text-xs">
                    <div class="flex items-center gap-1.5 text-gray-700">
                        @if(($product->reviewCount ?? 0) > 0)
                            <div class="flex items-center gap-1 font-bold text-amber-500">
                                <span>★</span>
                                <span>{{ number_format($product->avgRating, 1) }}</span>
                            </div>
                            <span class="text-gray-400">({{ $product->reviewCount }})</span>
                        @else
                            <div class="flex items-center gap-1 text-gray-400 font-medium">
                                <span>★</span>
                                <span>0.0 (0)</span>
                            </div>
                        @endif
                        <span class="text-gray-300">|</span>
                        <span class="text-gray-500">{{ (int)($soldCount ?? 0) }} sold</span>
                    </div>

                </div>
            </div>

            {{-- ═══ Mobile-Only: Delivery & Variation Rows ═══ --}}



            {{-- 3. Variations Preview Row (Click opens Buy Now sheet) --}}
            <div class="lg:hidden bg-white px-3.5 py-3 border-b border-gray-100 cursor-pointer active:bg-gray-50 transition-colors" @click="openBuyNowSheet()">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 overflow-hidden flex-1 mr-2">
                        <svg class="w-4 h-4 text-gray-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm8 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V4zm-8 8a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1v-4zm8 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" clip-rule="evenodd"/></svg>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <template x-for="(img, idx) in galleryImages.slice(0, 5)" :key="idx">
                                <img :src="imageUrl(img.url)" class="w-7 h-8 object-cover rounded border border-gray-200">
                            </template>
                        </div>
                        <span class="text-[11px] font-medium text-gray-800 truncate" 
                              x-text="(selectedVariationLabel() ? selectedVariationLabel() : 'Variations') + (selectedSize ? ' · Size: ' + selectedSize : ' · Select size')">
                        </span>
                    </div>
                    <span class="text-gray-400 text-sm shrink-0">›</span>
                </div>
            </div>

            <!-- Right Side: Details & Selectors (Desktop Only - 100% Untouched) -->
            <div class="hidden lg:flex lg:col-span-7 flex-col justify-between space-y-6">
                <div>
                    <!-- Product Title -->
                    <h1 class="font-sans text-2xl sm:text-3xl font-extrabold text-gray-900 mb-1 leading-tight tracking-tight">
                        {{ $product->name }}
                    </h1>

                    <!-- Seller Info & Rating Row -->
                    <div class="flex items-center gap-3 text-xs mb-4">
                        <div class="flex items-center gap-1.5">
                            <span class="text-gray-500 font-medium">by</span>
                            <a href="/shops/{{ $product->sellerId }}" class="font-extrabold text-amber-800 hover:underline flex items-center gap-1.5">
                                <img src="{{ $product->seller->profile_photo_url ?? '/uploads/products/default.jpg' }}" onerror="this.src='/uploads/products/default.jpg'" class="w-5 h-5 rounded-full object-cover border border-gray-200" alt="Artisan">
                                <span>{{ $product->artisan ?? $product->seller->shopName ?? 'BarongniJuan' }}</span>
                            </a>
                        </div>
                        <span class="text-gray-300">•</span>
                        <div class="flex items-center gap-1 font-bold text-gray-700">
                            @if(($product->reviewCount ?? 0) > 0)
                                <span class="text-amber-600 font-extrabold">{{ number_format($product->avgRating, 1) }}</span>
                                <svg class="w-3.5 h-3.5 fill-amber-400 text-amber-400" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="text-gray-400 font-normal">({{ $product->reviewCount }} {{ \Illuminate\Support\Str::plural('review', $product->reviewCount) }})</span>
                            @else
                                <span class="text-xs text-gray-400 font-medium">New • No ratings yet</span>
                            @endif
                        </div>
                        <span class="text-gray-300">•</span>
                        <span class="text-xs text-gray-600 font-semibold">{{ (int)($soldCount ?? 0) }} sold</span>
                    </div>

                    <!-- Price Row -->
                    <div class="flex items-baseline gap-3 mb-4">
                        <span class="text-2xl sm:text-3xl font-extrabold text-gray-900">₱{{ number_format($product->salePrice) }}</span>
                        @if($product->is_on_sale && $product->discount_percentage > 0)
                            <span class="text-base font-bold text-gray-400 line-through">₱{{ number_format($product->price) }}</span>
                            <span class="text-xs font-extrabold text-orange-600 uppercase">{{ number_format($product->discount_percentage, 0) }}% OFF</span>
                        @endif
                    </div>

                    <!-- Category Specification -->
                    <div class="space-y-1 text-xs text-gray-600 mb-6">
                        <div><span class="font-bold text-gray-800">Category:</span> {{ $product->category->name ?? 'Wedding Barong' }}</div>
                    </div>

                    <!-- Style / Variation Selection (When product has multiple variants) -->
                    <div x-show="styleVariants && styleVariants.length > 1" class="mb-6" x-cloak>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">
                                Style / Variation: <span class="text-[#C0420A] font-black" x-text="selectedVariationLabel()"></span>
                            </span>
                            <span class="text-[10px] text-gray-400 font-semibold" x-text="styleVariants.length + ' styles'"></span>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            <template x-for="(variant, index) in styleVariants" :key="variant.id">
                                <button 
                                    @click="selectStyleVariant(variant)"
                                    type="button"
                                    class="px-3.5 py-2 rounded-xl flex items-center gap-2 text-xs font-bold border transition-all cursor-pointer shadow-2xs group"
                                    :class="selectedStyle === variant.id 
                                        ? 'border-[#C0420A] bg-[#C0420A]/5 text-[#C0420A] ring-2 ring-[#C0420A]/20 font-black' 
                                        : 'border-gray-200 text-gray-700 bg-white hover:border-gray-400 font-semibold'"
                                >
                                    <template x-if="variant.image_url || variant.image">
                                        <img :src="imageUrl(variant.image_url || variant.image)" onerror="this.src='/uploads/products/default.jpg'" class="w-6 h-6 rounded-lg object-cover">
                                    </template>
                                    <span x-text="variant.name || ('Style ' + (index + 1))"></span>
                                    <span x-show="selectedStyle === variant.id" class="w-3.5 h-3.5 rounded-full bg-[#C0420A] text-white flex items-center justify-center text-[8px] font-bold">✓</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Available Sizes Row -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-gray-900">Available Sizes:</span>
                            <button type="button" @click="showSizeGuide = true" onclick="openSizeGuideModal()" class="text-[11px] font-bold text-amber-800 hover:underline cursor-pointer">Size Guide</button>
                        </div>
                        <div class="flex flex-wrap gap-2.5">
                            @php
                                $rawSizes = is_string($product->sizes) ? json_decode($product->sizes, true) : $product->sizes;
                                if (empty($rawSizes)) {
                                    $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
                                } else {
                                    $sizes = array_values(array_filter($rawSizes, function($sz) {
                                        $name = is_array($sz) ? ($sz['size'] ?? $sz['name'] ?? '') : $sz;
                                        return strtolower(trim((string)$name)) !== 'custom';
                                    }));
                                    if (empty($sizes)) {
                                        $sizes = ['S', 'M', 'L', 'XL', 'XXL'];
                                    }
                                }
                            @endphp
                            @foreach($sizes as $size)
                                @php 
                                    $sizeName = is_array($size) ? ($size['size'] ?? $size['name'] ?? 'N/A') : $size;
                                    $hasSizeStock = true;
                                    if (is_array($product->size_stocks) && isset($product->size_stocks[$sizeName]) && (int)$product->size_stocks[$sizeName] === 0) {
                                        $hasSizeStock = false;
                                    }
                                @endphp
                                <button 
                                    @click="updateStock('{{ $sizeName }}')"
                                    type="button"
                                    class="min-w-11 h-11 px-3 rounded-xl flex items-center justify-center text-xs font-bold border transition-all shadow-2xs"
                                    :class="selectedSize === '{{ $sizeName }}' ? 'border-amber-600 bg-amber-50/80 text-amber-900 ring-2 ring-amber-500/20' : 'border-gray-200 text-gray-700 bg-white hover:border-gray-400'"
                                >
                                    <span class="{{ !$hasSizeStock ? 'text-gray-300 line-through font-normal' : '' }}">{{ $sizeName }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    @if($isAdminUser || $isProductOwner)
                        <div class="flex items-center gap-2.5 mb-6 text-xs text-gray-700">
                            <span class="font-bold">Total Stock Inventory:</span>
                            <span class="px-3 py-1 rounded-xl bg-stone-100 font-extrabold text-stone-900 border border-stone-200">
                                {{ $product->stock > 0 ? $product->stock . ' pieces available' : 'Out of Stock' }}
                            </span>
                        </div>
                    @else
                    <!-- Quantity Stepper -->
                    <div class="flex items-center gap-4 mb-6">
                        <span class="text-xs font-bold text-gray-900">Quantity:</span>
                        <div class="flex items-center border border-gray-200 rounded-xl bg-gray-50 overflow-hidden shadow-2xs h-10">
                            <button 
                                @click="if(quantity > 1) quantity--" 
                                type="button" 
                                class="w-9 h-full flex items-center justify-center text-gray-600 hover:text-black font-bold text-base hover:bg-gray-100 transition-colors"
                            >
                                −
                            </button>
                            <input 
                                type="number" 
                                x-model.number="quantity" 
                                min="1" 
                                :max="stock"
                                class="w-10 text-center bg-transparent border-0 outline-none text-xs font-bold text-gray-900"
                            >
                            <button 
                                @click="if(quantity < stock) quantity++" 
                                type="button" 
                                class="w-9 h-full flex items-center justify-center text-gray-600 hover:text-black font-bold text-base hover:bg-gray-100 transition-colors"
                            >
                                +
                            </button>
                        </div>
                        <span class="text-xs font-semibold" :class="stock > 0 ? 'text-gray-400' : 'text-red-500 font-bold'">
                            (<span x-text="stock > 0 ? stock + ' pieces available' : 'Out of Stock'"></span>)
                        </span>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    @if($isAdminUser)
                        <!-- Admin Moderation Actions Panel (Replaces Add to Cart / Buy Now) -->
                        <div class="p-5 rounded-2xl shadow-sm space-y-4"
                             style="background-color: #FAF7F2; border: 1.5px solid #E8DECB;">
                            <div class="flex items-center justify-between gap-2 pb-3" style="border-bottom: 1px solid #E5DFD5;">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-lg flex items-center justify-center"
                                         style="background-color: rgba(192, 66, 10, 0.12); color: #C0420A;">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    </div>
                                    <span class="text-xs font-extrabold uppercase tracking-wider" style="color: #1E1915;">
                                        Admin Moderation Controls
                                    </span>
                                </div>
                                @if($productStatus === 'approved')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                                          style="background-color: #DEF7EC; color: #03543F; border: 1px solid #BCF0DA;">
                                        Approved
                                    </span>
                                @elseif($productStatus === 'rejected')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                                          style="background-color: #FDE8E8; color: #9B1C1C; border: 1px solid #F8B4B4;">
                                        Rejected
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                                          style="background-color: #FEF08A; color: #713F12; border: 1px solid #FDE047;">
                                        Pending Review
                                    </span>
                                @endif
                            </div>

                            @if($productStatus === 'rejected' && $product->rejectionReason)
                                <div class="p-3 rounded-xl text-xs space-y-1"
                                     style="background-color: #FEF2F2; border: 1px solid #FECACA; color: #991B1B;">
                                    <span class="font-bold block uppercase text-[10px] tracking-wider">Current Rejection Reason:</span>
                                    <p class="font-normal">{{ $product->rejectionReason }}</p>
                                </div>
                            @endif

                            <p class="text-xs leading-relaxed" style="color: #4B5563;">
                                Customer purchasing, cart additions, and checkout are disabled while viewing this listing as an administrator.
                            </p>

                            <div class="flex flex-col sm:flex-row gap-2.5 pt-1">
                                @if(Auth::user()->role === 'admin')
                                    @if($productStatus === 'pending' || $productStatus === 'rejected')
                                        <form action="{{ route('admin.products.approve', $product->id) }}" method="POST" class="flex-1">
                                            @csrf
                                            <button type="submit" onclick="return confirm('Approve this product and make it live in the catalog?')"
                                                    class="w-full h-11 px-4 rounded-xl font-bold text-xs uppercase tracking-wider shadow-sm flex items-center justify-center gap-2 cursor-pointer transition-all hover:opacity-95 active:scale-[0.99]"
                                                    style="background-color: #059669; color: #FFFFFF; border: none;">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                <span>Approve Product</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if($productStatus === 'pending' || $productStatus === 'approved')
                                        <button type="button" @click="openAdminReject()"
                                                class="flex-1 h-11 px-4 rounded-xl font-bold text-xs uppercase tracking-wider shadow-sm flex items-center justify-center gap-2 cursor-pointer transition-all hover:opacity-95 active:scale-[0.99]"
                                                style="background-color: #DC2626; color: #FFFFFF; border: none;">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            <span>{{ $productStatus === 'approved' ? 'Revoke Approval' : 'Reject Product' }}</span>
                                        </button>
                                    @endif
                                @endif

                                <a href="{{ $adminCatalogUrl }}" 
                                   class="flex-1 h-11 px-4 rounded-xl font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-1.5 text-center hover:opacity-90 active:scale-[0.99] cursor-pointer"
                                   style="background-color: #1E1915; color: #FFFFFF; border: none;">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                                    <span>Manage in Catalog</span>
                                </a>
                            </div>
                        </div>
                    @elseif($isProductOwner)
                        <!-- Artisan Owner Notice (Replaces Add to Cart / Buy Now) -->
                        <div class="space-y-3.5 p-5 rounded-2xl bg-amber-50 border border-amber-200">
                            <div class="flex items-center justify-between gap-2 pb-3 border-b border-amber-200/80">
                                <span class="text-xs font-bold uppercase tracking-wider text-amber-900 flex items-center gap-1.5">
                                    🏪 Artisan Owner Preview
                                </span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800">
                                    Your Creation
                                </span>
                            </div>
                            <p class="text-xs text-amber-800 leading-relaxed">
                                This is how your product appears to customers in the marketplace. You cannot purchase your own item.
                            </p>
                            <div class="flex gap-2.5 pt-1">
                                <a href="/seller/products/{{ $product->id }}/edit" class="flex-1 h-11 rounded-xl bg-[#C0422A] hover:bg-black text-white font-bold text-xs uppercase tracking-wider transition-colors shadow-sm flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    <span>Edit Product</span>
                                </a>
                                <a href="/seller/products" class="flex-1 h-11 rounded-xl bg-white border border-amber-200 hover:bg-amber-100/50 text-amber-900 font-bold text-xs uppercase tracking-wider transition-colors flex items-center justify-center gap-1.5 text-center">
                                    <span>My Catalog</span>
                                </a>
                            </div>
                        </div>
                    @else
                    {{-- Hidden form for cart submission --}}
                    <form id="mainCartForm" action="/cart/add" method="POST" @submit.prevent="submitAddToCart($event)" style="display:none;">
                        @csrf
                        <input type="hidden" name="productId" value="{{ $product->id }}">
                        <input type="hidden" name="size" :value="effectiveSize()">
                        <input type="hidden" name="quantity" :value="quantity">
                        <input type="hidden" name="variation" :value="selectedVariationLabel()">
                    </form>

                    <div class="space-y-3">
                        {{-- WHEN IN STOCK (stock > 0) --}}
                        <div x-show="stock > 0" class="space-y-3">
                            {{-- Desktop: Keep inline buttons --}}
                            <div class="hidden lg:flex items-center gap-3">
                                <button 
                                    type="button"
                                    @click="
                                        if (!selectedSize) {
                                            if (window.Alpine && Alpine.store('toast')) Alpine.store('toast').trigger('Please select a size first.', 'info');
                                            return;
                                        }
                                        var cartForm = document.getElementById('mainCartForm');
                                        if (cartForm) submitAddToCart({ target: cartForm, preventDefault: function(){} });
                                    "
                                    :disabled="!selectedSize"
                                    class="flex-1 h-12 rounded-xl bg-black hover:bg-gray-900 text-white font-bold text-xs flex items-center justify-center gap-2 transition-colors shadow-md disabled:opacity-50 cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <span x-text="!selectedSize ? 'Select Size' : 'Add to Cart'">Add to Cart</span>
                                </button>

                                <button 
                                    type="button" 
                                    @click="
                                        if (selectedSize && stock > 0) {
                                            if (!window.isLoggedIn) {
                                                const intent = {
                                                    action: 'buy_now',
                                                    productId: '{{ $product->id }}',
                                                    quantity: quantity,
                                                    size: effectiveSize(),
                                                    variation: selectedVariationLabel(),
                                                    redirectUrl: '/checkout?mode=buy_now'
                                                };
                                                try { localStorage.setItem('lumbarong_pending_intent', JSON.stringify(intent)); } catch(err) {}
                                                window.location.href = window.loginUrl;
                                                return;
                                            }
                                            window.location.href = '/checkout?productId={{ $product->id }}&size=' + encodeURIComponent(effectiveSize()) + '&quantity=' + quantity + '&variation=' + encodeURIComponent(selectedVariationLabel()) + '&direct=1';
                                        }
                                    "
                                    :disabled="!selectedSize"
                                    class="flex-1 h-12 rounded-xl bg-[#C89B55] hover:bg-[#B88B45] text-white font-extrabold text-xs tracking-wide shadow-md transition-colors disabled:opacity-50 cursor-pointer flex items-center justify-center gap-1.5"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span x-text="!selectedSize ? 'Select Size' : 'Buy Now'">Buy Now</span>
                                </button>
                            </div>

                            {{-- Mobile: Buttons that trigger bottom sheet --}}
                            <div class="lg:hidden flex items-center gap-3">
                                <button 
                                    type="button"
                                    @click="openBuyNowSheet('add_to_cart')"
                                    class="flex-1 h-12 rounded-xl bg-black hover:bg-gray-900 text-white font-bold text-xs flex items-center justify-center gap-2 transition-colors shadow-md cursor-pointer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                    <span>Add to Cart</span>
                                </button>

                                <button 
                                    type="button" 
                                    @click="openBuyNowSheet('buy_now')"
                                    class="flex-1 h-12 rounded-xl bg-[#C89B55] hover:bg-[#B88B45] text-white font-extrabold text-xs tracking-wide shadow-md transition-colors cursor-pointer flex items-center justify-center gap-1.5"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>Buy Now</span>
                                </button>
                            </div>
                        </div>

                        {{-- WHEN OUT OF STOCK (stock <= 0) - Wishlist Button --}}
                        <div x-show="stock <= 0" class="space-y-3" x-cloak style="display: none;">
                            <div class="p-3 bg-red-50/80 border border-red-200 rounded-xl flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2 text-red-700 font-bold">
                                    <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    <span>This item is currently out of stock.</span>
                                </div>
                                <span class="text-[10px] uppercase tracking-wider font-extrabold text-red-800 bg-red-100 px-2 py-0.5 rounded">Sold Out</span>
                            </div>

                            <button 
                                type="button" 
                                @click="toggleWishlist()" 
                                class="w-full h-12 rounded-xl border font-bold text-xs flex items-center justify-center gap-2 transition-all shadow-sm cursor-pointer"
                                :class="isWishlisted ? 'text-red-600 border-red-200 bg-red-50 hover:bg-red-100/70' : 'text-gray-900 border-gray-300 bg-white hover:bg-gray-50'"
                            >
                                <svg class="w-4 h-4" :class="isWishlisted ? 'fill-red-500 text-red-500' : 'fill-none stroke-current'" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                <span x-text="isWishlisted ? '❤️ Saved in Your Wishlist' : '♡ Add to Wishlist (Save for later)'"></span>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Bottom Delivery Feature Bar (Authoritative Logistics Calculation) -->
        @php
            $locationParts = array_filter([$product->seller->shopCity ?? null, $product->seller->shopProvince ?? null]);
            $shipsFrom = !empty($locationParts) ? implode(', ', $locationParts) : ($product->seller->shopAddress ?? $product->artisan_region ?? 'Lumban, Laguna');
            $hasAddress = Auth::check() && !empty($customerAddress);
            $feeDisplay = $estimatedShipping ? '₱' . number_format($estimatedShipping['shipping_fee'], 2) : ($hasAddress ? 'Unavailable' : 'Calculated at checkout');
            $daysDisplay = $estimatedShipping ? ($estimatedShipping['delivery_estimate_display'] ?? ($estimatedShipping['estimated_days_min'] . '–' . $estimatedShipping['estimated_days_max'] . ' days')) : '2–4 business days';
            $courierDisplay = $estimatedShipping ? ($estimatedShipping['provider_name'] ?? 'Standard Courier') : 'Standard Courier';
        @endphp
        <div class="hidden lg:grid grid-cols-1 sm:grid-cols-3 gap-4 pt-8 mt-8 border-t border-gray-100 text-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    🚚
                </div>
                <div>
                    <div class="font-medium text-gray-500">Shipping Fee</div>
                    <div class="font-extrabold text-gray-900">{{ $feeDisplay }}</div>
                    @if($estimatedShipping)
                        <div class="text-[10px] text-gray-400 font-medium">via {{ $courierDisplay }}</div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    📦
                </div>
                <div>
                    <div class="font-medium text-gray-500">Estimated Delivery</div>
                    <div class="font-extrabold text-gray-900">{{ $daysDisplay }}</div>
                    @if($hasAddress)
                        <div class="text-[10px] text-gray-400 font-medium truncate max-w-40">To {{ $customerAddress->city }}, {{ $customerAddress->province }}</div>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gray-50 flex items-center justify-center text-gray-700 shrink-0 border border-gray-100">
                    📍
                </div>
                <div>
                    <div class="font-medium text-gray-500">Ships from</div>
                    <div class="font-extrabold text-gray-900">{{ $shipsFrom }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── Fullscreen Closer Inspection Modal (Shopee Reference Image 3) ─── --}}
    <div 
        x-show="showZoomModal" 
        x-cloak
        style="display: none; z-index: 99999;"
        class="fixed inset-0 bg-black flex flex-col justify-between select-none"
        @keydown.window.escape="closeZoomModal()"
    >
        <!-- Top Bar: Close Button (Left) & Counter (Right) -->
        <div class="flex items-center justify-between p-3 sm:p-4 z-20 shrink-0">
            <button 
                type="button" 
                @click="closeZoomModal()"
                class="text-white hover:text-gray-300 p-2 rounded-full transition-colors cursor-pointer"
                title="Close (Esc)"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="text-white font-bold text-sm tracking-widest px-2" x-show="galleryImages && galleryImages.length > 0">
                <span x-text="(activeImage + 1) + '/' + galleryImages.length"></span>
            </div>
        </div>

        <!-- Center: Large Image Viewer with Left / Right Arrows -->
        <div 
            class="relative flex-1 w-full flex items-center justify-center overflow-hidden cursor-crosshair px-2"
            @mousemove="handleModalMouseMove($event)"
            @mouseleave="handleModalMouseLeave()"
            @touchstart="handleModalTouch($event)"
            @touchmove.prevent="handleModalTouch($event)"
            @touchend="handleModalMouseLeave()"
        >
            <!-- Prev Image Arrow -->
            <button 
                type="button"
                x-show="galleryImages && galleryImages.length > 1 && activeImage > 0"
                @click.stop="selectImage(activeImage - 1)"
                class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center transition-all cursor-pointer backdrop-blur-xs"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <!-- Next Image Arrow -->
            <button 
                type="button"
                x-show="galleryImages && galleryImages.length > 1 && activeImage < galleryImages.length - 1"
                @click.stop="selectImage(activeImage + 1)"
                class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full bg-white/15 hover:bg-white/30 text-white flex items-center justify-center transition-all cursor-pointer backdrop-blur-xs"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                </svg>
            </button>

            <template x-for="(img, index) in galleryImages" :key="index">
                <img 
                    x-show="activeImage === index"
                    :src="imageUrl(img.url)"
                    onerror="this.src='/uploads/products/default.jpg'"
                    class="max-h-[78vh] max-w-[94vw] object-contain pointer-events-none transition-transform duration-75 ease-out"
                    :class="isZoomed ? 'scale-[2.4]' : 'scale-100'"
                    :style="isZoomed ? { transformOrigin: `${zoomOriginX}% ${zoomOriginY}%` } : {}"
                    alt="{{ $product->name }}"
                >
            </template>
        </div>

        <!-- Bottom Bar: Centered Variant Label on Solid Black -->
        <div class="p-4 bg-black text-center shrink-0 z-20">
            <span class="text-white text-sm sm:text-base font-medium tracking-wide" 
                  x-text="(galleryImages[activeImage]?.variant_name || '{{ $product->name }}') + (stock <= 5 && stock > 0 ? ' 【Only ' + stock + ' piece' + (stock > 1 ? 's' : '') + ' left!!!】' : '')">
            </span>
        </div>
    </div>

        <!-- Directory Sections Wrapper (Mobile flex-col with dynamic order, Desktop static) -->
        <div class="flex flex-col">

        <!-- Reviews Section -->
        @php
            $reviewsList = ($product->reviews ?? collect())->map(function($rev) {
                $customerName = $rev->customer->name ?? 'customer';
                $initial = strtoupper(substr($customerName, 0, 1));
                $photo = $rev->customer ? $rev->customer->profile_photo_url : null;
                $images = is_string($rev->images) ? json_decode($rev->images, true) : $rev->images;
                $images = is_array($images) ? array_values(array_filter($images)) : [];
                $images = array_map(function($img) {
                    if (!$img) return null;
                    if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) return $img;
                    if (str_starts_with($img, '/')) return asset(ltrim($img, '/'));
                    if (str_starts_with($img, 'uploads/')) return asset($img);
                    if (str_starts_with($img, 'storage/')) return asset($img);
                    if (str_starts_with($img, 'reviews/')) return asset('storage/' . $img);
                    return asset('uploads/reviews/' . $img);
                }, $images);
                $images = array_values(array_filter($images));

                $videoUrl = null;
                if ($rev->video) {
                    if (str_starts_with($rev->video, 'http://') || str_starts_with($rev->video, 'https://')) {
                        $videoUrl = $rev->video;
                    } elseif (str_starts_with($rev->video, '/')) {
                        $videoUrl = asset(ltrim($rev->video, '/'));
                    } elseif (str_starts_with($rev->video, 'uploads/') || str_starts_with($rev->video, 'storage/')) {
                        $videoUrl = asset($rev->video);
                    } else {
                        $videoUrl = asset('storage/' . $rev->video);
                    }
                }

                return [
                    'id' => $rev->id,
                    'rating' => (int)$rev->rating,
                    'comment' => $rev->comment,
                    'seller_reply' => $rev->seller_reply,
                    'seller_reply_date' => $rev->seller_reply_at ? ($rev->seller_reply_at instanceof \Carbon\Carbon ? $rev->seller_reply_at->format('M d, Y') : \Carbon\Carbon::parse($rev->seller_reply_at)->format('M d, Y')) : null,
                    'seller_name' => $product->seller->name ?? 'Artisan Store',
                    'date' => $rev->createdAt ? ($rev->createdAt instanceof \Carbon\Carbon ? $rev->createdAt->format('F d, Y') : \Carbon\Carbon::parse($rev->createdAt)->format('F d, Y')) : '',
                    'customerName' => $customerName,
                    'initial' => $initial,
                    'customerPhoto' => $photo,
                    'images' => $images,
                    'video' => $videoUrl,
                    'verified' => (bool)($rev->orderId || $rev->orderItemId),
                ];
            })->values();
        @endphp

        <div id="reviews" class="order-1 lg:order-2 mt-4 lg:mt-16 pt-3 lg:pt-10 border-t border-gray-100 scroll-mt-24"
             x-data="{
                 allReviews: {{ json_encode($reviewsList) }},
                 reviewsModal: false,
                 activeFilter: 'all',
                 currentPage: 1,
                 perPage: 5,
                 lightboxModal: false,
                 lightboxType: 'image',
                 lightboxUrl: '',
                 openLightbox(type, url) {
                     if (!url) return;
                     this.lightboxType = type;
                     this.lightboxUrl = url;
                     this.lightboxModal = true;
                 },
                 closeLightbox() {
                     this.lightboxModal = false;
                     if (this.$refs.lightboxVideo) {
                         try { this.$refs.lightboxVideo.pause(); } catch(e) {}
                     }
                     this.lightboxUrl = '';
                 },
                 get filteredReviews() {
                     if (this.activeFilter === 'all') return this.allReviews;
                     if (this.activeFilter === 'media') {
                         return this.allReviews.filter(r => (r.images && r.images.length > 0) || r.video);
                     }
                     const star = parseInt(this.activeFilter, 10);
                     return this.allReviews.filter(r => r.rating === star);
                 },
                 get totalPages() {
                     return Math.max(1, Math.ceil(this.filteredReviews.length / this.perPage));
                 },
                 get paginatedReviews() {
                     const start = (this.currentPage - 1) * this.perPage;
                     return this.filteredReviews.slice(start, start + this.perPage);
                 },
                 setFilter(f) {
                     this.activeFilter = f;
                     this.currentPage = 1;
                 },
                 countFilter(f) {
                     if (f === 'all') return this.allReviews.length;
                     if (f === 'media') return this.allReviews.filter(r => (r.images && r.images.length > 0) || r.video).length;
                     const star = parseInt(f, 10);
                     return this.allReviews.filter(r => r.rating === star).length;
                 }
             }"
             @keydown.escape.window="closeLightbox()">

            @php
                $totalRevCount = $product->reviews->count();
                $starBreakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                foreach($product->reviews as $r) {
                    $s = (int)$r->rating;
                    if(isset($starBreakdown[$s])) $starBreakdown[$s]++;
                }
                $mediaReviewsCount = $product->reviews->filter(fn($r)=>!empty($r->images_list)||!empty($r->video_url))->count();
            @endphp

            {{-- ═══ Mobile Reviews Directory View ═══ --}}
            <div class="lg:hidden bg-white mb-3">
                {{-- Reviews Header Row --}}
                <div class="flex items-center justify-between py-3 border-b border-gray-100 cursor-pointer" @click="reviewsModal = true">
                    <span class="text-sm font-bold text-gray-900">Reviews ({{ $totalRevCount }})</span>
                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                        @if($totalRevCount > 0)
                            <span class="font-bold text-gray-900">{{ number_format($product->avgRating, 1) }}</span>
                            <div class="flex items-center text-amber-400 text-xs">
                                @for($i = 1; $i <= 5; $i++)
                                    <span>{{ $i <= round($product->avgRating) ? '★' : '☆' }}</span>
                                @endfor
                            </div>
                        @else
                            <span class="text-gray-400">No ratings yet</span>
                        @endif
                        <span class="text-gray-400 text-sm">›</span>
                    </div>
                </div>

                {{-- Review Filter Chips (Only show if reviews exist) --}}
                @if($totalRevCount > 0)
                <div class="flex items-center gap-2 py-2.5 overflow-x-auto no-scrollbar border-b border-gray-100 text-[11px]">
                    @if($mediaReviewsCount > 0)
                    <button type="button" @click="reviewsModal = true; setFilter('media')" class="px-2.5 py-1 rounded-full bg-amber-50/80 text-amber-900 border border-amber-200/60 font-medium shrink-0 flex items-center gap-1 cursor-pointer">
                        <span>📷</span>
                        <span>With images/videos ({{ $mediaReviewsCount }})</span>
                    </button>
                    @endif
                    @if(($starBreakdown[5] ?? 0) > 0)
                    <button type="button" @click="reviewsModal = true; setFilter('5')" class="px-2.5 py-1 rounded-full bg-amber-50/80 text-amber-900 border border-amber-200/60 font-medium shrink-0 flex items-center gap-1 cursor-pointer">
                        <span>★ 5 Stars ({{ $starBreakdown[5] }})</span>
                    </button>
                    @endif
                </div>
                @endif

                {{-- Real-Time Review Items Preview --}}
                <div class="divide-y divide-gray-100">
                    @forelse($product->reviews->take(3) as $rev)
                        <div class="py-3 flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                @if(!empty($rev->comment))
                                    <p class="text-xs text-gray-800 line-clamp-3 leading-relaxed">
                                        {{ $rev->comment }}
                                    </p>
                                @else
                                    <p class="text-xs text-gray-400 italic">
                                        No written feedback provided.
                                    </p>
                                @endif
                                <div class="flex items-center gap-2 mt-2">
                                    <div class="flex items-center text-amber-400 text-[10px]">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span>{{ $i <= (int)$rev->rating ? '★' : '☆' }}</span>
                                        @endfor
                                    </div>
                                    <span class="text-[11px] text-gray-500 font-medium">{{ $rev->customer->name ?? 'Verified Buyer' }}</span>
                                </div>
                            </div>
                            @php
                                $imgList = $rev->images_list;
                                $topImg = !empty($imgList) ? $imgList[0] : null;
                            @endphp
                            @if($topImg)
                                <img src="{{ $topImg }}" class="w-14 h-16 object-cover rounded-lg border border-gray-100 shrink-0 cursor-pointer" @click="openLightbox('image', '{{ $topImg }}')">
                            @endif
                        </div>
                    @empty
                        <div class="py-6 text-center text-gray-400">
                            <p class="text-xs font-medium">No reviews yet for this product.</p>
                        </div>
                    @endforelse
                </div>

            </div>

            {{-- ═══ Desktop Reviews Section (Existing 100% Untouched on lg:) ═══ --}}
            <div class="hidden lg:block">

            {{-- Reviews Section Header & Summary Badge (Matching Mockup) --}}
            <div class="flex items-center gap-6 sm:gap-10 mb-8 flex-wrap">
                <div>
                    <div class="flex items-center gap-1.5 mb-1">
                        <span class="text-[#C0420A] font-bold text-xs">—</span>
                        <span class="text-[10px] font-black text-[#C0420A] uppercase tracking-widest">Reviews & Feedback</span>
                    </div>
                    <h2 class="font-serif text-2xl sm:text-3xl font-bold text-black">Customer Reviews</h2>
                </div>

                @if($product->reviews->isNotEmpty())
                    <div class="rounded-2xl px-6 py-3.5 flex items-center gap-4 shadow-xs" style="background-color: #FAF9F6; border: 1px solid #EFEAE2;">
                        <div class="text-center pr-4 border-r" style="border-color: #E5DEC3;">
                            <span class="text-3xl font-black text-[#C0420A] leading-none">{{ number_format($product->avgRating, 1) }}</span>
                            <span class="text-[9px] text-gray-400 font-bold block mt-0.5">out of 5</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-0.5 text-amber-400 text-sm">
                                @for($i = 1; $i <= 5; $i++)
                                    <span>{{ $i <= round($product->avgRating) ? '★' : '☆' }}</span>
                                @endfor
                            </div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-0.5 block">
                                {{ $totalRevCount }} {{ $totalRevCount === 1 ? 'REVIEW' : 'REVIEWS' }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            @if($product->reviews->isNotEmpty())
                {{-- Rating Distribution Breakdown (Matching Mockup) --}}
                <div class="rounded-3xl p-6 sm:p-7 mb-8 max-w-xl shadow-xs space-y-3" style="background-color: #FAF9F6; border: 1px solid #EFEAE2;">
                    <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-3">Rating Distribution</span>
                    @for($star = 5; $star >= 1; $star--)
                        @php
                            $starCount = $starBreakdown[$star] ?? 0;
                            $starPct = $totalRevCount > 0 ? round(($starCount / $totalRevCount) * 100) : 0;
                        @endphp
                        <div class="flex items-center gap-3 text-xs">
                            <span class="w-8 font-bold text-gray-600 text-right">{{ $star }} ★</span>
                            <div class="flex-1 h-3 rounded-full overflow-hidden bg-[#ECE7DE] min-h-2.5">
                                <div class="h-full rounded-full bg-amber-500 min-h-2.5" :style="'width: ' + {{ (int)$starPct }} + '%'"></div>
                            </div>
                            <span class="w-8 text-[11px] font-bold text-gray-400 text-right">{{ $starCount }}</span>
                        </div>
                    @endfor
                </div>

                {{-- Stacked Single-Column Customer Reviews List (Matching Mockup) --}}
                <div class="space-y-4">
                    @foreach($product->reviews->take(3) as $review)
                        <div class="rounded-2xl p-5 sm:p-6 space-y-3 shadow-xs" style="background-color: #FAF9F6; border: 1px solid #EFEAE2;">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-full flex items-center justify-center font-bold text-gray-700 text-sm overflow-hidden shrink-0" style="background-color: #EAE8E4;">
                                        @if($review->customer && $review->customer->profile_photo_url)
                                            <img src="{{ $review->customer->profile_photo_url }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                        @else
                                            {{ strtoupper(substr($review->customer->name ?? 'C', 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-sm font-bold text-black">{{ $review->customer->name ?? 'Customer' }}</span>
                                            <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                                                @for($i = 1; $i <= 5; $i++)
                                                    <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-0.5">
                                            {{ $review->createdAt ? $review->createdAt->format('F d, Y') : '' }}
                                        </div>
                                    </div>
                                </div>

                                @if($review->orderId || $review->orderItemId)
                                    <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest flex items-center gap-1" style="background-color: #E8F8F0; color: #1E9E65; border: 1px solid #D0F0E0;">
                                        ✓ Verified Purchase
                                    </span>
                                @endif
                            </div>

                            @if($review->comment)
                                <p class="text-xs sm:text-sm text-gray-700 italic leading-relaxed">
                                    "{{ $review->comment }}"
                                </p>
                            @endif

                            {{-- Review Media (Photos up to 3 & Video) --}}
                            @php
                                $revImages = $review->images_list;
                                $revVideo = $review->video_url;
                            @endphp
                            @if(!empty($revImages) || !empty($revVideo))
                                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                                    {{-- Photos --}}
                                    @foreach($revImages as $rImgUrl)
                                        @if($rImgUrl)
                                            <button type="button" 
                                                    @click="openLightbox('image', '{{ $rImgUrl }}')" 
                                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-white shrink-0 hover:scale-105 transition-all shadow-2xs border border-[#EAE6DF] relative group cursor-pointer">
                                                <img src="{{ $rImgUrl }}" class="w-full h-full object-cover" onerror="this.style.display='none'" alt="Customer Review Photo">
                                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs">
                                                    🔍
                                                </div>
                                            </button>
                                        @endif
                                    @endforeach

                                    {{-- Video --}}
                                    @if($revVideo)
                                        <button type="button" 
                                                @click="openLightbox('video', '{{ $revVideo }}')" 
                                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl overflow-hidden bg-black shrink-0 hover:scale-105 transition-all shadow-2xs border border-gray-800 relative group cursor-pointer flex items-center justify-center">
                                            <video src="{{ $revVideo }}" class="w-full h-full object-cover opacity-70" preload="metadata"></video>
                                            <div class="absolute inset-0 bg-black/25 flex items-center justify-center">
                                                <div class="w-7 h-7 rounded-full bg-white/90 text-black flex items-center justify-center text-[10px] font-black pl-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                                    ▶
                                                </div>
                                            </div>
                                            <span class="absolute bottom-1 right-1 bg-black/80 text-[8px] font-bold text-white px-1.5 py-0.2 rounded">VIDEO</span>
                                        </button>
                                    @endif
                                </div>
                            @endif

                            {{-- Seller Response (Shopee/Lazada Style) --}}
                            @if(!empty($review->seller_reply))
                                <div class="mt-3 p-3.5 sm:p-4 bg-[#FAF9F5] rounded-2xl border-l-4 border-[#C0420A] space-y-1.5 shadow-2xs">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-[#C0420A] flex items-center gap-1.5">
                                            <span>💬 Seller's Response</span>
                                            <span class="text-gray-400 font-medium">• {{ $product->seller->name ?? 'Artisan Store' }}</span>
                                        </span>
                                        @if($review->seller_reply_at)
                                            <span class="text-[9px] text-gray-400 font-medium">{{ \Carbon\Carbon::parse($review->seller_reply_at)->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-700 leading-relaxed font-normal">{{ $review->seller_reply }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- View All Reviews Button (Matching Mockup) --}}
                <div class="mt-6">
                    <button type="button" 
                            @click="reviewsModal = true"
                            class="w-full py-4 px-6 rounded-2xl text-xs font-black uppercase tracking-widest transition-all shadow-xs flex items-center justify-center gap-2 cursor-pointer group active:scale-[0.99] hover:bg-[#C0420A] hover:text-white"
                            style="background-color: #FAF9F6; border: 1px solid #E88058; color: #C0420A;">
                        <span>VIEW ALL REVIEWS ({{ $totalRevCount }})</span>
                        <span class="group-hover:translate-y-0.5 transition-transform text-sm font-bold">⌄</span>
                    </button>
                </div>

            @else
                <div class="text-center py-12 bg-gray-50/50 rounded-2xl border border-gray-100">
                    <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">No reviews yet for this heritage piece.</p>
                    <p class="text-[10px] text-gray-400 mt-1">Purchased items can be rated once they are received.</p>
                </div>
            @endif

            </div>

            {{-- Shopee/Lazada Style All Reviews Modal with Filter Tabs & Pagination --}}
            <div x-show="reviewsModal" class="fixed inset-0 z-9999 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-sm" x-cloak style="display: none;">
                <div @click.away="reviewsModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-200 border border-gray-100">
                    
                    {{-- Modal Header --}}
                    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b border-gray-100 flex items-center justify-between bg-white shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 font-black text-sm shadow-xs shrink-0">
                                ★
                            </div>
                            <div>
                                <h3 class="font-serif text-base sm:text-lg font-bold text-gray-900">Customer Reviews</h3>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">
                                    <span class="font-bold text-amber-500">★ {{ number_format($product->avgRating, 1) }}</span> out of 5 • <span class="font-semibold text-gray-700" x-text="allReviews.length + ' Total ' + (allReviews.length === 1 ? 'Review' : 'Reviews')"></span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="reviewsModal = false" class="w-8 h-8 rounded-full border border-gray-200 bg-gray-50 hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-900 transition-all cursor-pointer">
                            ✕
                        </button>
                    </div>

                    {{-- Shopee/Lazada Style Filter Tabs --}}
                    <div class="px-4 sm:px-6 py-3 bg-gray-50/70 border-b border-gray-100 flex items-center gap-1.5 sm:gap-2 overflow-x-auto no-scrollbar shrink-0">
                        <button type="button" 
                            @click="setFilter('all')"
                            :class="activeFilter === 'all' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>All</span>
                            <span class="opacity-80" x-text="'(' + countFilter('all') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('5')"
                            :class="activeFilter === '5' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>5 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('5') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('4')"
                            :class="activeFilter === '4' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>4 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('4') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('3')"
                            :class="activeFilter === '3' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>3 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('3') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('2')"
                            :class="activeFilter === '2' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>2 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('2') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('1')"
                            :class="activeFilter === '1' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3 py-1.5 sm:px-3.5 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>1 Star</span>
                            <span class="opacity-80" x-text="'(' + countFilter('1') + ')'"></span>
                        </button>
                        <button type="button" 
                            @click="setFilter('media')"
                            :class="activeFilter === 'media' ? 'bg-[#C0420A] text-white shadow-xs font-bold' : 'bg-white text-gray-700 border border-gray-200 hover:border-gray-300 hover:bg-gray-50 font-medium'"
                            class="px-3.5 py-1.5 sm:px-4 sm:py-1.5 rounded-full text-[11px] sm:text-xs transition-all whitespace-nowrap cursor-pointer flex items-center gap-1 shrink-0">
                            <span>📷 With Media</span>
                            <span class="opacity-80" x-text="'(' + countFilter('media') + ')'"></span>
                        </button>
                    </div>

                    {{-- Reviews Content (Scrollable) --}}
                    <div x-ref="reviewsContainer" class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-3.5 bg-gray-50/40">
                        <template x-if="filteredReviews.length === 0">
                            <div class="text-center py-12 text-gray-400 space-y-2">
                                <div class="text-3xl">💬</div>
                                <p class="text-xs font-bold uppercase tracking-widest">No reviews found under this filter.</p>
                            </div>
                        </template>

                        <template x-for="review in paginatedReviews" :key="review.id">
                            <div class="bg-white border border-gray-100 rounded-2xl p-4 sm:p-5 space-y-2.5 shadow-xs hover:border-gray-200 transition-all">
                                <div class="flex items-center justify-between flex-wrap gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 border border-gray-200/60 flex items-center justify-center font-bold text-gray-700 text-sm overflow-hidden shrink-0 shadow-xs">
                                            <template x-if="review.customerPhoto">
                                                <img :src="review.customerPhoto" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="!review.customerPhoto">
                                                <span x-text="review.initial"></span>
                                            </template>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="text-xs sm:text-sm font-bold text-gray-900" x-text="review.customerName"></span>
                                                <div class="flex items-center gap-0.5 text-amber-400 text-xs">
                                                    <template x-for="s in 5" :key="s">
                                                        <span x-text="s <= review.rating ? '★' : '☆'"></span>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="text-[10px] text-gray-400 font-semibold uppercase tracking-wider mt-0.5" x-text="review.date"></div>
                                        </div>
                                    </div>

                                    <template x-if="review.verified">
                                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-100/80 rounded-full text-[9px] font-black uppercase tracking-wider flex items-center gap-1">
                                            ✓ Verified Purchase
                                        </span>
                                    </template>
                                </div>

                                <template x-if="review.comment">
                                    <p class="text-xs sm:text-sm text-gray-800 leading-relaxed italic" x-text="'“' + review.comment + '”'"></p>
                                </template>

                                {{-- Review Media (Images up to 3 & Video) in Modal --}}
                                <div class="flex flex-wrap items-center gap-2 pt-1">
                                    {{-- Photos --}}
                                    <template x-if="review.images && review.images.length > 0">
                                        <template x-for="(img, idx) in review.images" :key="idx">
                                            <button type="button" 
                                                    @click="openLightbox('image', img)" 
                                                    class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 shrink-0 hover:scale-105 transition-all shadow-xs block cursor-pointer relative group">
                                                <img :src="img" class="w-full h-full object-cover" alt="Review Photo">
                                                <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs">
                                                    🔍
                                                </div>
                                            </button>
                                        </template>
                                    </template>

                                    {{-- Video --}}
                                    <template x-if="review.video">
                                        <button type="button" 
                                                @click="openLightbox('video', review.video)" 
                                                class="w-16 h-16 sm:w-20 sm:h-20 rounded-xl overflow-hidden border border-gray-800 bg-black shrink-0 hover:scale-105 transition-all shadow-xs cursor-pointer relative group flex items-center justify-center">
                                            <video :src="review.video" class="w-full h-full object-cover opacity-70" preload="metadata"></video>
                                            <div class="absolute inset-0 bg-black/25 flex items-center justify-center">
                                                <div class="w-7 h-7 rounded-full bg-white/90 text-black flex items-center justify-center text-[10px] font-black pl-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                                    ▶
                                                </div>
                                            </div>
                                            <span class="absolute bottom-1 right-1 bg-black/80 text-[8px] font-bold text-white px-1.5 py-0.2 rounded">VIDEO</span>
                                        </button>
                                    </template>
                                </div>

                                {{-- Seller Response (Shopee/Lazada Style) --}}
                                <template x-if="review.seller_reply">
                                    <div class="mt-3 p-3.5 bg-[#FAF9F5] rounded-2xl border-l-4 border-[#C0420A] space-y-1 shadow-2xs">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[10px] font-black uppercase tracking-wider text-[#C0420A] flex items-center gap-1.5">
                                                <span>💬 Seller's Response</span>
                                                <span class="text-gray-400 font-normal" x-text="'• ' + (review.seller_name || 'Artisan Store')"></span>
                                            </span>
                                            <span class="text-[9px] text-gray-400 font-medium" x-text="review.seller_reply_date || ''"></span>
                                        </div>
                                        <p class="text-xs text-gray-700 leading-relaxed font-normal" x-text="review.seller_reply"></p>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    {{-- Modal Pagination Footer (Matching Screenshot) --}}
                    <div class="p-5 sm:p-6 bg-white border-t border-gray-100 flex flex-col items-center gap-3 shrink-0 relative">
                        <div class="text-xs text-gray-500 font-medium text-center">
                            Showing page <span class="font-bold text-gray-900" x-text="currentPage"></span> of <span class="font-bold text-gray-900" x-text="totalPages"></span> (<span x-text="filteredReviews.length"></span> reviews)
                        </div>

                        {{-- Pagination Buttons (‹ 1 2 3 ›) --}}
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                @click="if(currentPage > 1) { currentPage--; if($refs.reviewsContainer) $refs.reviewsContainer.scrollTop = 0; }"
                                :disabled="currentPage === 1"
                                :class="currentPage === 1 ? 'opacity-30 cursor-not-allowed text-gray-400 bg-gray-50' : 'hover:border-[#C0420A] hover:text-[#C0420A] text-gray-700 bg-white cursor-pointer'"
                                class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-sm font-bold transition-all shadow-xs">
                                ‹
                            </button>

                            <template x-for="p in totalPages" :key="p">
                                <button type="button" 
                                    @click="currentPage = p; if($refs.reviewsContainer) $refs.reviewsContainer.scrollTop = 0;"
                                    :class="currentPage === p ? 'border-[#C0420A] text-[#C0420A] bg-white font-black shadow-xs' : 'border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50 font-bold'"
                                    class="w-10 h-10 rounded-xl border text-sm transition-all cursor-pointer flex items-center justify-center"
                                    x-text="p">
                                </button>
                            </template>

                            <button type="button" 
                                @click="if(currentPage < totalPages) { currentPage++; if($refs.reviewsContainer) $refs.reviewsContainer.scrollTop = 0; }"
                                :disabled="currentPage === totalPages"
                                :class="currentPage === totalPages ? 'opacity-30 cursor-not-allowed text-gray-400 bg-gray-50' : 'hover:border-[#C0420A] hover:text-[#C0420A] text-gray-700 bg-white cursor-pointer'"
                                class="w-10 h-10 rounded-xl border border-gray-200 flex items-center justify-center text-sm font-bold transition-all shadow-xs">
                                ›
                            </button>
                        </div>

                        {{-- Close Button on Bottom Right --}}
                        <div class="w-full sm:w-auto sm:absolute sm:right-6 sm:bottom-5 flex justify-end mt-2 sm:mt-0">
                            <button type="button" @click="reviewsModal = false"
                                class="w-full sm:w-auto px-7 py-3 bg-[#111] text-white text-xs font-black uppercase tracking-widest rounded-xl hover:bg-[#C0420A] transition-all shadow-md active:scale-95 cursor-pointer">
                                CLOSE
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Integrated Media Lightbox Overlay (Images & Video Modal) --}}
            <div x-show="lightboxModal" 
                 class="fixed inset-0 z-99999 flex items-center justify-center p-3 sm:p-6 bg-black/85 backdrop-blur-md"
                 x-cloak 
                 style="display: none;">
                <div @click.away="closeLightbox()" class="relative max-w-4xl w-full flex flex-col items-center justify-center">
                    {{-- Obvious Close Button --}}
                    <button type="button" 
                            @click="closeLightbox()" 
                            class="absolute -top-12 right-0 sm:-right-2 w-10 h-10 rounded-full bg-white/20 hover:bg-white text-white hover:text-black flex items-center justify-center text-lg font-black backdrop-blur-md transition-all cursor-pointer shadow-lg z-20">
                        ✕
                    </button>

                    {{-- Lightbox Display Container --}}
                    <div class="w-full flex items-center justify-center rounded-2xl overflow-hidden shadow-2xl bg-black/60 p-1 sm:p-2 border border-white/10">
                        <template x-if="lightboxType === 'image'">
                            <img :src="lightboxUrl" class="max-w-full max-h-[82vh] object-contain rounded-xl select-none" alt="Review Media Preview">
                        </template>
                        <template x-if="lightboxType === 'video'">
                            <video x-ref="lightboxVideo" :src="lightboxUrl" controls autoplay playsinline class="max-w-full max-h-[78vh] rounded-xl bg-black shadow-2xl"></video>
                        </template>
                    </div>
                </div>
            </div>

        </div>

        <!-- Lower Section: Description & Info (Mobile order-2, Desktop lg:order-1) -->
        <div id="details" class="order-2 lg:order-1 mt-4 lg:mt-16 pt-3 lg:pt-10 border-t border-gray-100 scroll-mt-24">
            {{-- ═══ Mobile Store / Artisan Card (Redesigned Premium UI) ═══ --}}
            <div class="lg:hidden bg-white rounded-2xl p-4 border border-stone-200/80 shadow-xs mb-3">
                <div class="flex items-center justify-between pb-3 border-b border-stone-100">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="relative w-12 h-12 rounded-xl overflow-hidden border border-stone-200 shadow-2xs shrink-0 ring-2 ring-amber-100/60">
                            <img src="{{ $product->seller->profile_photo_url ?? asset('uploads/products/default.jpg') }}" class="w-full h-full object-cover" onerror="this.src='/uploads/products/default.jpg'">
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5">
                                <h4 class="text-sm font-extrabold text-stone-900 truncate">{{ $product->artisan ?? $product->seller->shopName ?? $product->seller->name ?? 'Artisan Store' }}</h4>
                                <svg class="w-4 h-4 text-[#B8860B] shrink-0" fill="currentColor" viewBox="0 0 20 20" title="Verified Artisan">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="flex items-center gap-1.5 text-[11px] mt-1 flex-wrap">
                                @if(!is_null($sellerAvgRating) && $sellerAvgRating > 0)
                                    <span class="inline-flex items-center gap-0.5 text-[#946A24] bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-full font-bold text-[10px]">
                                        ★ {{ number_format($sellerAvgRating, 1) }} Rating
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[#946A24] bg-amber-50 border border-amber-200/60 px-2 py-0.5 rounded-full font-bold text-[10px]">
                                        ✨ New Artisan
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1 text-emerald-700 bg-emerald-50 border border-emerald-200/60 px-2 py-0.5 rounded-full font-semibold text-[10px]">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Online
                                </span>
                            </div>
                        </div>
                    </div>
                    <a href="/shops/{{ $product->sellerId ?? ($product->seller->id ?? '') }}" class="px-3 py-1.5 bg-[#1E1915] hover:bg-[#A67C2E] text-white text-xs font-bold rounded-lg transition-all shadow-xs flex items-center gap-1 shrink-0 ml-2">
                        <span>Visit Store</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- 3-Column Clean Micro-Stats Grid --}}
                <div class="grid grid-cols-3 gap-2 pt-3 text-center">
                    <div class="bg-stone-50/80 rounded-xl p-2.5 border border-stone-100">
                        <div class="text-[10px] font-semibold text-stone-400 uppercase tracking-wider">Total Sold</div>
                        <div class="text-xs font-black text-stone-900 mt-0.5">
                            {{ (int)($sellerTotalSold ?? 0) > 0 ? number_format($sellerTotalSold) : '0' }}
                        </div>
                    </div>
                    <div class="bg-stone-50/80 rounded-xl p-2.5 border border-stone-100">
                        <div class="text-[10px] font-semibold text-stone-400 uppercase tracking-wider">Products</div>
                        <div class="text-xs font-black text-stone-900 mt-0.5">
                            {{ (int)($sellerProductCount ?? 0) }}
                        </div>
                    </div>
                    <div class="bg-stone-50/80 rounded-xl p-2.5 border border-stone-100">
                        <div class="text-[10px] font-semibold text-stone-400 uppercase tracking-wider">Origin</div>
                        <div class="text-xs font-black text-stone-900 mt-0.5 truncate">
                            {{ $product->seller->shopCity ?? $product->seller->city ?? 'Lumban' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile Product Details Card (Enhanced Luxury UI) --}}
            <div class="lg:hidden bg-white rounded-2xl p-4 border border-stone-200/80 shadow-xs mb-4">
                <div class="flex items-center gap-2 mb-3 pb-2.5 border-b border-stone-100">
                    <div class="w-4 h-0.5 bg-[#A67C2E] rounded-full"></div>
                    <h3 class="text-xs font-extrabold text-stone-900 uppercase tracking-widest">Product Details</h3>
                </div>
                
                {{-- Quick Specifications Grid --}}
                <div class="grid grid-cols-2 gap-2 mb-3 pb-3 border-b border-stone-100 text-[11px]">
                    <div class="flex items-center gap-1.5 text-stone-600">
                        <span class="text-stone-400 font-medium">Category:</span>
                        <span class="font-bold text-stone-900 truncate">{{ $product->category->name ?? 'Barong Tagalog' }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-stone-600">
                        <span class="text-stone-400 font-medium">Craft:</span>
                        <span class="font-bold text-stone-900">Hand Embroidered</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-stone-600">
                        <span class="text-stone-400 font-medium">Origin:</span>
                        <span class="font-bold text-stone-900">Lumban, Laguna</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-stone-600">
                        <span class="text-stone-400 font-medium">Condition:</span>
                        <span class="font-bold text-emerald-700">Brand New</span>
                    </div>
                </div>

                {{-- Full Description --}}
                <p class="text-xs text-stone-700 leading-relaxed whitespace-pre-line font-normal">
                    {{ $product->description }}
                </p>
            </div>

            {{-- Desktop Details Grid (Existing 100% Untouched on lg:) --}}
            <div class="hidden lg:grid lg:grid-cols-12 gap-8 lg:gap-16">
                <div class="lg:col-span-5">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Artisan's Story</h3>
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-gray-50 rounded-2xl flex items-center justify-center text-xl font-bold text-gray-300 border border-gray-100 shadow-sm shrink-0 relative overflow-hidden">
                            @if($product->seller && $product->seller->profile_photo_url)
                                <img src="{{ $product->seller->profile_photo_url }}" class="w-full h-full object-cover" onerror="this.src='/uploads/products/default.jpg'">
                            @else
                                <img src="{{ asset('uploads/products/default.jpg') }}" class="w-full h-full object-cover" alt="Artisan">
                            @endif
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Artisan</div>
                            <div class="text-sm font-bold text-black flex items-center gap-1.5">
                                {{ $product->artisan ?? 'Lumban Master Craft' }}
                            </div>
                            
                            <div class="mt-2 flex items-center gap-2 flex-wrap">
                                <a href="/shops/{{ $product->sellerId }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-stone-100 hover:bg-[#C0420A] text-[9px] font-black uppercase tracking-widest text-stone-700 hover:text-white rounded-lg border border-stone-200/60 transition-all shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    View Shop
                                </a>
                                <button 
                                    type="button" 
                                    @click="chatWithSeller('{{ $product->sellerId }}', '{{ e($product->seller->shopName ?? $product->seller->name ?? 'Artisan') }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-[#C0422A] text-[9px] font-black uppercase tracking-widest text-amber-900 hover:text-white rounded-lg border border-amber-200/60 transition-all shadow-sm cursor-pointer"
                                >
                                    💬 Chat with Seller
                                </button>
                                @if(!$isAdminUser)
                                <button 
                                    type="button" 
                                    @click="window.dispatchEvent(new CustomEvent('open-report', { detail: { reportedId: '{{ $product->sellerId }}', reportedName: '{{ e($product->seller->shopName ?? $product->seller->name ?? 'Artisan') }}', productId: '{{ $product->id }}', productName: '{{ e($product->name) }}', reportType: 'product' } }))"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-gray-50 hover:bg-red-50 text-[9px] font-black uppercase tracking-widest text-gray-500 hover:text-red-600 rounded-lg border border-gray-200/80 hover:border-red-200 transition-all shadow-2xs cursor-pointer"
                                    title="Report this listing for policy violations"
                                >
                                    🛡️ Report
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lg:col-span-7">
                    <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Product Details</h3>
                    <p class="text-gray-600 text-sm leading-relaxed whitespace-pre-line">
                        {{ $product->description }}
                    </p>
                </div>
            </div>
        </div>

        </div> <!-- Closing flex flex-col -->

    {{-- Recommended Products --}}
    @if($recommended->isNotEmpty())
    <div id="recommendations" class="mt-8 lg:mt-16 scroll-mt-24">
        {{-- ═══ Mobile Recommendations View (Interactive Tabs: Same store | Similar items | Recommended) ═══ --}}
        <div class="lg:hidden bg-white mb-6" x-data="{ recTab: 'similar' }">
            {{-- Tabs: Same store | Similar items | Recommended --}}
            <div class="flex items-center justify-around border-b border-stone-200 text-xs py-2 bg-white sticky top-12 z-10">
                <button type="button" @click="recTab = 'same_store'" 
                        class="cursor-pointer transition-all pb-1.5 px-2 font-medium"
                        :class="recTab === 'same_store' ? 'text-stone-900 font-bold border-b-2 border-[#A67C2E]' : 'text-stone-400 hover:text-stone-700 border-b-2 border-transparent'">
                    Same store
                </button>
                <button type="button" @click="recTab = 'similar'" 
                        class="cursor-pointer transition-all pb-1.5 px-2 font-medium"
                        :class="recTab === 'similar' ? 'text-stone-900 font-bold border-b-2 border-[#A67C2E]' : 'text-stone-400 hover:text-stone-700 border-b-2 border-transparent'">
                    Similar items
                </button>
                <button type="button" @click="recTab = 'recommended'" 
                        class="cursor-pointer transition-all pb-1.5 px-2 font-medium"
                        :class="recTab === 'recommended' ? 'text-stone-900 font-bold border-b-2 border-[#A67C2E]' : 'text-stone-400 hover:text-stone-700 border-b-2 border-transparent'">
                    Recommended
                </button>
            </div>

            {{-- Tab 1: Same Store Products Grid --}}
            <div x-show="recTab === 'same_store'" class="p-2.5">
                @if(isset($sameStoreProducts) && $sameStoreProducts->isNotEmpty())
                    <div class="grid grid-cols-3 gap-2">
                        @foreach($sameStoreProducts->take(6) as $rec)
                            <div class="flex flex-col bg-white rounded-lg border border-stone-100 overflow-hidden shadow-2xs relative">
                                <a href="/products/{{ $rec->id }}" class="block aspect-square overflow-hidden bg-gray-50">
                                    <img src="{{ $rec->getImageUrl() }}" alt="{{ $rec->name }}" class="w-full h-full object-cover">
                                </a>
                                <div class="p-1.5 flex flex-col justify-between flex-1">
                                    <div>
                                        <a href="/products/{{ $rec->id }}" class="text-[11px] font-medium text-gray-900 line-clamp-2 leading-tight hover:text-[#A67C2E]">
                                            {{ $rec->name }}
                                        </a>
                                        <div class="mt-1 flex items-baseline gap-1">
                                            <span class="text-xs font-black text-[#A67C2E]">₱{{ number_format($rec->is_on_sale ? $rec->salePrice : $rec->price, 2) }}</span>
                                        </div>
                                        <div class="mt-0.5">
                                            <span class="text-[9px] font-bold text-amber-800 bg-amber-50 px-1 py-0.2 rounded border border-amber-200/60 inline-block">
                                                Voucher applied
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-1.5 flex items-center justify-between">
                                        <div class="text-[9px] text-gray-500 flex items-center gap-1">
                                            @if($rec->avgRating && (float)$rec->avgRating > 0)
                                                <span class="text-amber-500 font-bold">★ {{ number_format($rec->avgRating, 1) }}</span>
                                            @else
                                                <span class="text-gray-400 font-medium">★ 0.0</span>
                                            @endif
                                            <span>{{ (int)($rec->sold_count ?? 0) }} sold</span>
                                        </div>
                                        <a href="/products/{{ $rec->id }}" class="w-5 h-5 rounded-full bg-[#1E1915] text-white flex items-center justify-center text-xs font-bold shadow-2xs hover:bg-[#A67C2E] transition-colors shrink-0 cursor-pointer">
                                            +
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 px-4 text-center">
                        <p class="text-xs text-stone-500 font-medium">No other items from this artisan yet.</p>
                        <a href="/shops/{{ $product->sellerId ?? ($product->seller->id ?? '') }}" class="mt-2.5 inline-flex items-center gap-1 px-3 py-1.5 bg-[#1E1915] text-white text-[11px] font-bold rounded-lg hover:bg-[#A67C2E] transition-colors">
                            <span>Visit Store Profile</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                @endif
            </div>

            {{-- Tab 2: Similar Items Grid --}}
            <div x-show="recTab === 'similar'" class="p-2.5">
                @php
                    $similarList = (isset($similarProducts) && $similarProducts->isNotEmpty()) ? $similarProducts : $recommended;
                @endphp
                <div class="grid grid-cols-3 gap-2">
                    @foreach($similarList->take(6) as $rec)
                        <div class="flex flex-col bg-white rounded-lg border border-stone-100 overflow-hidden shadow-2xs relative">
                            <a href="/products/{{ $rec->id }}" class="block aspect-square overflow-hidden bg-gray-50">
                                <img src="{{ $rec->getImageUrl() }}" alt="{{ $rec->name }}" class="w-full h-full object-cover">
                            </a>
                            <div class="p-1.5 flex flex-col justify-between flex-1">
                                <div>
                                    <a href="/products/{{ $rec->id }}" class="text-[11px] font-medium text-gray-900 line-clamp-2 leading-tight hover:text-[#A67C2E]">
                                        {{ $rec->name }}
                                    </a>
                                    <div class="mt-1 flex items-baseline gap-1">
                                        <span class="text-xs font-black text-[#A67C2E]">₱{{ number_format($rec->is_on_sale ? $rec->salePrice : $rec->price, 2) }}</span>
                                    </div>
                                    <div class="mt-0.5">
                                        <span class="text-[9px] font-bold text-amber-800 bg-amber-50 px-1 py-0.2 rounded border border-amber-200/60 inline-block">
                                            Voucher applied
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between">
                                    <div class="text-[9px] text-gray-500 flex items-center gap-1">
                                        @if($rec->avgRating && (float)$rec->avgRating > 0)
                                            <span class="text-amber-500 font-bold">★ {{ number_format($rec->avgRating, 1) }}</span>
                                        @else
                                            <span class="text-gray-400 font-medium">★ 0.0</span>
                                        @endif
                                        <span>{{ (int)($rec->sold_count ?? 0) }} sold</span>
                                    </div>
                                    <a href="/products/{{ $rec->id }}" class="w-5 h-5 rounded-full bg-[#1E1915] text-white flex items-center justify-center text-xs font-bold shadow-2xs hover:bg-[#A67C2E] transition-colors shrink-0 cursor-pointer">
                                        +
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tab 3: Recommended Products Grid --}}
            <div x-show="recTab === 'recommended'" class="p-2.5">
                <div class="grid grid-cols-3 gap-2">
                    @foreach($recommended->take(6) as $rec)
                        <div class="flex flex-col bg-white rounded-lg border border-stone-100 overflow-hidden shadow-2xs relative">
                            <a href="/products/{{ $rec->id }}" class="block aspect-square overflow-hidden bg-gray-50">
                                <img src="{{ $rec->getImageUrl() }}" alt="{{ $rec->name }}" class="w-full h-full object-cover">
                            </a>
                            <div class="p-1.5 flex flex-col justify-between flex-1">
                                <div>
                                    <a href="/products/{{ $rec->id }}" class="text-[11px] font-medium text-gray-900 line-clamp-2 leading-tight hover:text-[#A67C2E]">
                                        {{ $rec->name }}
                                    </a>
                                    <div class="mt-1 flex items-baseline gap-1">
                                        <span class="text-xs font-black text-[#A67C2E]">₱{{ number_format($rec->is_on_sale ? $rec->salePrice : $rec->price, 2) }}</span>
                                    </div>
                                    <div class="mt-0.5">
                                        <span class="text-[9px] font-bold text-amber-800 bg-amber-50 px-1 py-0.2 rounded border border-amber-200/60 inline-block">
                                            Voucher applied
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-1.5 flex items-center justify-between">
                                    <div class="text-[9px] text-gray-500 flex items-center gap-1">
                                        @if($rec->avgRating && (float)$rec->avgRating > 0)
                                            <span class="text-amber-500 font-bold">★ {{ number_format($rec->avgRating, 1) }}</span>
                                        @else
                                            <span class="text-gray-400 font-medium">★ 0.0</span>
                                        @endif
                                        <span>{{ (int)($rec->sold_count ?? 0) }} sold</span>
                                    </div>
                                    <a href="/products/{{ $rec->id }}" class="w-5 h-5 rounded-full bg-[#1E1915] text-white flex items-center justify-center text-xs font-bold shadow-2xs hover:bg-[#A67C2E] transition-colors shrink-0 cursor-pointer">
                                        +
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ═══ Desktop Recommendations View (Existing 100% Untouched on lg:) ═══ --}}
        <div class="hidden lg:block">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Keep Exploring</span>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-black">Recommended Products</h2>
                </div>
                <a href="/#catalogue-section" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#C0422A] transition-colors">
                    View all →
                </a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 lg:grid-cols-6 gap-3.5 sm:gap-4">
                @foreach($recommended as $rec)
                <a href="/products/{{ $rec->id }}" class="group block">
                    <div class="aspect-4/5 bg-gray-100 rounded-2xl overflow-hidden mb-3 relative shadow-sm">
                        <img src="{{ $rec->getImageUrl() }}"
                             alt="{{ $rec->name }}"
                             class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500 ease-out">

                        @if($rec->is_on_sale && $rec->discount_percentage > 0)
                            <div style="position:absolute;top:6px;left:6px;display:flex;flex-direction:column;gap:4px;z-index:10;pointer-events:none;">
                                <div style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px 3px 5px;background:linear-gradient(135deg,#0F0C08 0%,#1C1609 100%);border:1px solid #A87B10;border-radius:20px;box-shadow:0 0 8px rgba(180,130,15,0.45),inset 0 1px 0 rgba(230,185,60,0.12);white-space:nowrap;">
                                    <img src="/images/logo-icon.png" alt="LumBarong" style="width:13px;height:13px;border-radius:50%;flex-shrink:0;object-fit:cover;">
                                    <span style="color:#DFC97A;font-family:ui-sans-serif,system-ui,sans-serif;font-size:7px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;">Lumbarong Specials &amp; Promo</span>
                                </div>
                                <div style="display:inline-flex;align-items:baseline;padding:3px 8px;background:linear-gradient(90deg,#7A5505 0%,#C8890A 25%,#E8AD12 50%,#C8890A 75%,#7A5505 100%);border:1px solid #5C3E04;border-radius:20px;box-shadow:0 2px 10px rgba(200,137,10,0.5),inset 0 1px 0 rgba(255,220,80,0.25);white-space:nowrap;width:fit-content;">
                                    <span style="color:#FFF8E0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:13px;font-weight:900;line-height:1;letter-spacing:-0.02em;">-{{ number_format($rec->discount_percentage, 0) }}%</span>
                                    <span style="color:#FFE8A0;font-family:ui-sans-serif,system-ui,sans-serif;font-size:8px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-left:2px;">OFF</span>
                                </div>
                            </div>
                        @elseif($rec->target_group)
                            @php
                                $recTgVal = strtolower(trim($rec->target_group));
                            @endphp
                            <div style="position:absolute;top:6px;left:6px;display:inline-flex;align-items:center;gap:5px;padding:3px 7px 3px 5px;background:linear-gradient(135deg,#131E2E 0%,#0B111A 100%);border:1px solid #A87B10;border-radius:4px;box-shadow:0 2px 8px rgba(0,0,0,0.45),inset 0 1px 0 rgba(230,185,60,0.2);white-space:nowrap;z-index:10;pointer-events:none;">
                                @if($recTgVal === 'men')
                                    <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                        <path fill="#DFC97A" d="M12 2l-2.5 5 2.5 1.5 2.5-1.5L12 2zm-4.5 5.5L3 9v13h7v-9l-2.5-2.5zm9 0l-2.5 2.5v9h7V9l-4.5-1.5zM11 9.5v8l1 3.5 1-3.5v-8l-1 1-1-1z"/>
                                    </svg>
                                @elseif($recTgVal === 'women')
                                    <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                        <path fill="#DFC97A" d="M12 2a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4zm-2.5 5.5L7 11.5l2 1.5-2 9h10l-2-9 2-1.5-2.5-4h-5zM11 9h2l1 3.5-2 1.5-2-1.5L11 9z"/>
                                    </svg>
                                @elseif($recTgVal === 'kids')
                                    <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                        <path fill="#DFC97A" d="M12 2a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm-4 7.5c-1.4 0-2.5 1.1-2.5 2.5v3.5c0 .8.7 1.5 1.5 1.5H8V21h8v-4h1c.8 0 1.5-.7 1.5-1.5V12c0-1.4-1.1-2.5-2.5-2.5h-8z"/>
                                    </svg>
                                @else
                                    <svg style="width:11px;height:11px;flex-shrink:0;" viewBox="0 0 24 24">
                                        <path fill="#DFC97A" d="M12 2l2.4 7.2h7.6l-6.1 4.5 2.3 7.3L12 16.5 5.8 21l2.3-7.3L2 9.2h7.6z"/>
                                    </svg>
                                @endif
                                <div style="width:1px;height:9px;background:rgba(223,201,122,0.35);flex-shrink:0;"></div>
                                <span style="color:#DFC97A;font-family:ui-serif,Georgia,Cambria,'Times New Roman',serif;font-size:7.5px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;line-height:1;">{{ $rec->target_group }}</span>
                            </div>
                        @endif
                    </div>

                    <h3 class="font-extrabold text-sm text-gray-900 group-hover:text-[#C0422A] transition-colors leading-tight line-clamp-2 uppercase tracking-tight">{{ $rec->name }}</h3>

                    <div class="flex items-center gap-1.5 text-[10px] mt-1">
                        @if($rec->avgRating)
                            <div class="flex items-center gap-1 font-bold text-yellow-500">
                                <span>★</span>
                                <span>{{ number_format($rec->avgRating, 1) }}</span>
                                <span class="text-gray-400 font-normal">({{ $rec->reviewCount }})</span>
                            </div>
                            <span class="text-gray-300">•</span>
                        @endif
                        <span class="text-gray-500 font-semibold text-[10px]">
                            {{ (int)($rec->sold_count ?? 0) }} sold
                        </span>
                    </div>

                    <div class="flex items-center gap-2 mt-1">
                        @if($rec->is_on_sale && $rec->discount_percentage > 0)
                            <p class="text-base font-extrabold text-[#E02424]">₱{{ number_format($rec->salePrice) }}</p>
                            <p class="text-xs font-bold text-gray-400 line-through">₱{{ number_format($rec->price) }}</p>
                        @else
                            <p class="text-sm font-black text-gray-800">₱{{ number_format($rec->price) }}</p>
                        @endif
                    </div>

                    @if($rec->artisan)
                        <p class="text-[10px] text-gray-400 mt-0.5 font-medium">by {{ $rec->artisan }}</p>
                    @endif
                </a>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <!-- ========== SIZE GUIDE MODAL ========== -->
    <div id="size-guide-modal"
         x-show="showSizeGuide"
         x-cloak
         @keydown.escape.window="showSizeGuide = false; closeSizeGuideModal();"
         class="fixed inset-0 flex items-center justify-center p-4"
         style="display: none; z-index: 999999 !important;">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showSizeGuide = false" onclick="closeSizeGuideModal()"></div>

        <!-- Modal Panel -->
        <div class="relative z-10 bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-gray-100">

            <!-- Header -->
            <div class="sticky top-0 bg-white px-8 pt-8 pb-6 border-b border-gray-100 flex items-start justify-between z-20">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-5 h-[1.5px] bg-[#C0422A]"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-[#C0422A]">Barong Tagalog</span>
                    </div>
                    <h2 class="font-serif text-2xl font-bold text-black">Size Guide</h2>
                    <p class="text-xs text-gray-400 mt-0.5">All measurements are in centimetres (cm)</p>
                </div>
                <button type="button" @click="showSizeGuide = false" onclick="closeSizeGuideModal()"
                        class="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Content -->
            <div class="px-8 py-6 space-y-6" x-data="{ sizeTab: 'men' }">

                <!-- Seller's Custom Size Guide Image (if uploaded) -->
                @if($product->getSizeGuideUrl())
                    <div class="bg-[#FDF9F4] border border-[#F5EAD9] rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-[#C0422A]">📐 Seller's Custom Size Chart</span>
                            <span class="text-[9px] bg-[#C0422A]/10 text-[#C0422A] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider">Artisan Size Guide</span>
                        </div>
                        <div class="rounded-xl overflow-hidden bg-white border border-gray-200 p-2 shadow-xs">
                            <img src="{{ $product->getSizeGuideUrl() }}" class="w-full max-h-96 object-contain mx-auto rounded-lg" alt="{{ $product->name }} Size Guide">
                        </div>
                    </div>
                @endif

                <!-- Seller Custom Measurements Table (if specified by artisan) -->
                @if(!empty($product->size_guide_measurements) && is_array($product->size_guide_measurements))
                    <div class="bg-amber-50/60 border border-amber-200/80 rounded-2xl p-5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C0422A]">👕 Seller Specific Product Measurements</span>
                            <span class="text-[9px] bg-amber-200/60 text-amber-900 px-2 py-0.5 rounded-full font-bold uppercase">Exact Garment Specs</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b-2 border-amber-300">
                                        <th class="py-2.5 pr-4 text-left font-black uppercase text-[10px] text-black">Size</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Chest</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Shoulder</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Length</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Sleeves</th>
                                        <th class="py-2.5 px-3 text-center font-black uppercase text-[10px] text-gray-700">Width</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-amber-100">
                                    @foreach($product->size_guide_measurements as $mRow)
                                        <tr>
                                            <td class="py-2.5 pr-4 font-black text-black">{{ $mRow['size'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['chest'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['shoulder'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['length'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['sleeves'] ?? '—' }}</td>
                                            <td class="py-2.5 px-3 text-center font-bold text-gray-800">{{ $mRow['width'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Size Guide Reference with Men / Women / Kids Tabs -->
                @php
                    $sellerSizeGuides = $product->seller->size_guides ?? [];
                    $resolveSgUrl = function($targetGroup, $defaultPath) use ($sellerSizeGuides) {
                        if (!empty($sellerSizeGuides[$targetGroup])) {
                            $path = $sellerSizeGuides[$targetGroup];
                            return str_starts_with($path, 'http') ? $path : asset(ltrim($path, '/'));
                        }
                        return asset($defaultPath);
                    };
                    $menGuideUrl   = $resolveSgUrl('Men', 'uploads/size-guides/size_guide_men.png');
                    $womenGuideUrl = $resolveSgUrl('Women', 'uploads/size-guides/size_guide_women.png');
                    $kidsGuideUrl  = $resolveSgUrl('Kids', 'uploads/size-guides/size_guide_kids.png');

                    $isCustomMen   = !empty($sellerSizeGuides['Men']);
                    $isCustomWomen = !empty($sellerSizeGuides['Women']);
                    $isCustomKids  = !empty($sellerSizeGuides['Kids']);
                @endphp

                <div class="space-y-4">
                    <div class="flex items-center gap-2 py-1">
                        <div class="w-5 h-[1.5px] bg-gray-300"></div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
                            Barong & Filipiniana Size Guide Reference
                        </span>
                        <div class="flex-1 h-px bg-gray-100"></div>
                    </div>

                    <!-- Tab Buttons: Men / Women / Kids -->
                    <div class="flex gap-2 bg-gray-100 p-1 rounded-2xl w-full">
                        <button type="button" id="size-tab-btn-men" @click="sizeTab = 'men'" onclick="switchSizeGuideTab('men')"
                            class="flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-white shadow-md text-black border border-gray-200 transition-all">
                            👔 Men
                        </button>
                        <button type="button" id="size-tab-btn-women" @click="sizeTab = 'women'" onclick="switchSizeGuideTab('women')"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest text-gray-400 hover:text-black transition-all">
                            👗 Women
                        </button>
                        <button type="button" id="size-tab-btn-kids" @click="sizeTab = 'kids'" onclick="switchSizeGuideTab('kids')"
                            class="flex-1 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest text-gray-400 hover:text-black transition-all">
                            🧒 Kids
                        </button>
                    </div>

                    <!-- MEN's SIZE DIAGRAM PICTURE -->
                    <div id="size-tab-content-men" x-show="sizeTab === 'men'" class="space-y-4 text-center" style="display: block;">
                        <div class="rounded-3xl border border-[#E5DDD5] overflow-hidden bg-[#FDF9F4] p-4 shadow-sm">
                            <a href="{{ $menGuideUrl }}" target="_blank" title="Click to view full size image">
                                <img src="{{ $menGuideUrl }}" alt="Men's Size Guide Chart" class="w-full max-h-[70vh] object-contain rounded-2xl mx-auto shadow-xs hover:scale-[1.02] transition-transform">
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 font-semibold">
                            👔 {{ $isCustomMen ? "Artisan's Shop Men's Size Guide Chart" : "Men's Barong Tagalog Standard Size Guide Chart" }} • Click image to open high-resolution view
                        </p>
                    </div>

                    <!-- WOMEN's SIZE DIAGRAM PICTURE -->
                    <div id="size-tab-content-women" x-show="sizeTab === 'women'" class="space-y-4 text-center" style="display: none;">
                        <div class="rounded-3xl border border-[#F5EAD9] overflow-hidden bg-[#FDF9F4] p-4 shadow-sm">
                            <a href="{{ $womenGuideUrl }}" target="_blank" title="Click to view full size image">
                                <img src="{{ $womenGuideUrl }}" alt="Women's Size Guide Chart" class="w-full max-h-[70vh] object-contain rounded-2xl mx-auto shadow-xs hover:scale-[1.02] transition-transform">
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 font-semibold">
                            👗 {{ $isCustomWomen ? "Artisan's Shop Women's Size Guide Chart" : "Women's Baro't Saya / Filipiniana Standard Size Guide Chart" }} • Click image to open high-resolution view
                        </p>
                    </div>

                    <!-- KIDS' SIZE DIAGRAM PICTURE -->
                    <div id="size-tab-content-kids" x-show="sizeTab === 'kids'" class="space-y-4 text-center" style="display: none;">
                        <div class="rounded-3xl border border-gray-200 overflow-hidden bg-[#F4F8F3] p-4 shadow-sm">
                            <a href="{{ $kidsGuideUrl }}" target="_blank" title="Click to view full size image">
                                <img src="{{ $kidsGuideUrl }}" alt="Kids' Size Guide Chart" class="w-full max-h-[70vh] object-contain rounded-2xl mx-auto shadow-xs hover:scale-[1.02] transition-transform">
                            </a>
                        </div>
                        <p class="text-xs text-gray-500 font-semibold">
                            🧒 {{ $isCustomKids ? "Artisan's Shop Kids' Size Guide Chart" : "Kids' Barong Tagalog Standard Size Guide Chart" }} • Click image to open high-resolution view
                        </p>
                    </div>
                </div>

                <!-- How to Measure tip -->
                <div class="bg-[#FDF9F4] border border-[#F5EAD9] rounded-2xl p-4 flex gap-4">
                    <div class="w-8 h-8 rounded-full bg-[#C0422A]/10 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-widest text-[#C0422A] mb-1">How to Measure</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Use a soft measuring tape. Keep the tape close to your body but not tight. Measure over your undergarment for the most accurate results.</p>
                    </div>
                </div>

                <!-- Measurement Guides Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Chest / Bust</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure around the fullest part of your chest, keeping the tape horizontal under the armpits.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Shoulders</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the edge of one shoulder across the back to the edge of the other shoulder.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Length</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the highest point of the shoulder, straight down to the desired hemline.</p>
                    </div>
                    <div class="bg-gray-50 rounded-2xl p-4">
                        <div class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-1.5">Sleeve</div>
                        <p class="text-xs text-gray-600 leading-relaxed">Measure from the shoulder seam to the end of the cuff with your arm slightly bent.</p>
                    </div>
                </div>

                <!-- Note -->
                <div class="text-center pb-2">
                    <p class="text-[10px] text-gray-400 font-medium">Sizes may vary slightly between artisans. When in doubt, size up. Contact the artisan for custom fitting.</p>
                </div>

            </div>
        </div>
    </div> <!-- Closing #size-guide-modal -->
    @if($isAdminUser)
    {{-- Admin Rejection Modal --}}
    <div x-show="adminRejectModal"
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         @keydown.escape.window="closeAdminReject()">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-gray-100 text-left space-y-4"
             @click.away="closeAdminReject()">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2 text-rose-600 font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span>Reject Product / Revoke Approval</span>
                </div>
                <button type="button" @click="closeAdminReject()" class="text-gray-400 hover:text-black text-sm font-bold cursor-pointer">✕</button>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                Provide a clear reason for rejecting <strong>"{{ $product->name }}"</strong>. This explanation will be delivered directly to the artisan.
            </p>

            <form action="{{ route('admin.products.reject', $product->id) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 uppercase tracking-wider mb-1.5">Rejection Reason *</label>
                    <textarea name="reason" rows="3" required placeholder="e.g. Photo resolution is too low, missing accurate size measurements, policy violation..."
                              class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-900 focus:bg-white focus:border-rose-500 outline-none transition-colors resize-none"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="closeAdminReject()" 
                            class="px-4 py-2 text-xs font-bold rounded-xl transition-all cursor-pointer hover:opacity-90 active:scale-98"
                            style="background-color: #F3F4F6; color: #374151; border: 1px solid #E5E7EB;">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-xs font-bold rounded-xl transition-all shadow-sm cursor-pointer hover:opacity-95 active:scale-98"
                            style="background-color: #DC2626; color: #FFFFFF; border: none;">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════════════════ --}}
    {{-- ─── Shopee-Style Slide-Up Buy Now / Add to Cart Bottom Sheet (Mobile & Tablet) ─── --}}
    {{-- ═══════════════════════════════════════════════════════════════════════════════ --}}
    <div 
        x-show="showBuyNowSheet" 
        x-cloak 
        style="display: none; z-index: 100050 !important;"
        class="fixed inset-0 flex flex-col justify-end bg-black/60 backdrop-blur-xs transition-opacity"
        @keydown.window.escape="closeBuyNowSheet()"
    >
        <!-- Backdrop Click to Close -->
        <div class="fixed inset-0" @click="closeBuyNowSheet()"></div>

        <!-- Slide-Up Panel Content -->
        <div 
            class="relative w-full max-w-lg mx-auto bg-white rounded-t-3xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] z-10 animate-in slide-in-from-bottom duration-300"
            @click.stop
        >
            <!-- Drag Handle Bar -->
            <div class="w-12 h-1.5 bg-gray-300 rounded-full mx-auto mt-3 shrink-0"></div>

            <!-- Header: Selected Variant Image + Pricing + Stock + Close Button -->
            <div class="p-4 sm:p-5 flex items-start gap-3.5 border-b border-gray-100 shrink-0 relative">
                <!-- Thumbnail -->
                <div class="w-20 h-24 sm:w-24 sm:h-28 rounded-xl overflow-hidden bg-gray-100 border border-gray-200 shrink-0 relative shadow-2xs">
                    <img :src="imageUrl(galleryImages[activeImage]?.url)" onerror="this.src='/uploads/products/default.jpg'" class="w-full h-full object-cover">
                </div>

                <!-- Price & Selection Info -->
                <div class="flex-1 min-w-0 pr-6">
                    <div class="flex items-baseline gap-2 flex-wrap"></div>
                    <!-- Stock warning -->
                    <template x-if="stock > 0">
                    <div class="text-[11px] font-bold text-[#A67C2E] leading-none mb-0.5">
                        @if($calcDiff > 0)
                            <span>₱{{ number_format($calcDiff, 2) }} off with Promo</span>
                        @else
                            <span>Special Promo Price</span>
                        @endif
                    </div>
                    </template>
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-xl sm:text-2xl font-black text-[#A67C2E]">₱{{ number_format($product->salePrice, 2) }}</span>
                    </div>
                    <div class="text-[11px] text-gray-600 font-medium truncate mt-1 flex items-center gap-1.5 flex-wrap">
                        <span class="text-gray-900 font-semibold" x-text="selectedVariationLabel() || 'Default'"></span>
                        <span class="text-gray-300">·</span>
                        <span :class="selectedSize ? 'text-gray-900 font-semibold' : 'text-[#A67C2E] font-bold'" 
                              x-text="selectedSize ? 'Size: ' + selectedSize : 'Please select a size'"></span>
                    </div>
                </div>

                <!-- Close Button -->
                <button type="button" @click="closeBuyNowSheet()" class="absolute top-3 right-3 text-gray-400 hover:text-black p-1 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>



            <!-- Scrollable Content: Available Designs Cards, Sizes, Quantity -->
            <div class="p-3 sm:p-4 overflow-y-auto space-y-4 flex-1">
                <!-- Available Designs (Visual Cards matching Reference Image 1) -->
                <template x-if="variantCards && variantCards.length > 0">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-black text-gray-900">Available Designs</span>
                                <span class="text-xs font-bold text-gray-400" x-text="'(' + styleVariants.length + ')'"></span>
                            </div>
                        </div>
                        
                        <!-- Horizontal Scrollable Row of Visual Design Cards -->
                        <div class="relative">
                            <div class="flex gap-2.5 overflow-x-auto pb-2 pt-0.5 px-0.5 scrollbar-none snap-x" style="-webkit-overflow-scrolling: touch;">
                                <template x-for="card in variantCards" :key="card.card_id">
                                    <button 
                                        type="button" 
                                        @click="selectVariantCard(card)"
                                        class="shrink-0 w-24 sm:w-28 rounded-xl border flex flex-col text-left transition-all cursor-pointer overflow-hidden bg-white shadow-2xs snap-start group"
                                        :class="isCardActive(card) 
                                            ? 'border-[#A67C2E] ring-2 ring-[#A67C2E] bg-amber-50/20 shadow-xs' 
                                            : 'border-gray-200 hover:border-gray-300'"
                                    >
                                        <!-- Portrait Image (Matching Reference Image 1) -->
                                        <div class="w-full h-24 sm:h-28 bg-gray-50 relative overflow-hidden shrink-0 border-b border-gray-100">
                                            <img :src="imageUrl(card.image_url || card.image_path)" 
                                                 onerror="this.src='/uploads/products/default.jpg'"
                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-200">
                                            
                                            <!-- Checkmark Badge on Active Card -->
                                            <template x-if="isCardActive(card)">
                                                <div class="absolute top-1.5 right-1.5 w-4 h-4 rounded-full bg-[#A67C2E] text-white flex items-center justify-center text-[9px] font-black shadow-xs">
                                                    ✓
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Card Info: Variation Name + Urgency/Stock Badge -->
                                        <div class="p-1.5 sm:p-2 flex flex-col items-center justify-center text-center flex-1 bg-white">
                                            <span class="text-[11px] font-bold text-gray-900 truncate w-full leading-tight" 
                                                  :class="isCardActive(card) ? 'text-[#A67C2E]' : 'text-gray-900'"
                                                  x-text="card.variant_name">
                                            </span>
                                            <span class="text-[9.5px] font-medium text-gray-400 truncate w-full mt-0.5 leading-none"
                                                  x-text="stock <= 3 && stock > 0 ? '【Only ' + stock + ' left!】' : '【Available】'">
                                            </span>
                                        </div>
                                    </button>
                                </template>
                            </div>

                            <!-- Scroll Indicator Bar (Matching Reference Image 1) -->
                            <template x-if="variantCards && variantCards.length > 2">
                                <div class="w-10 h-1 bg-gray-200 rounded-full mx-auto mt-1 opacity-60"></div>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="(!variantCards || variantCards.length === 0) && styleVariants && styleVariants.length > 0">
                    <div>
                        <div class="mb-2">
                            <span class="text-xs font-black text-gray-900">Available Designs</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <template x-for="v in styleVariants" :key="v.id">
                                <button 
                                    type="button" 
                                    @click="selectStyleVariant(v)"
                                    class="p-1.5 rounded-lg border flex items-center gap-2 text-left transition-all cursor-pointer bg-white shadow-2xs"
                                    :class="selectedStyle === v.id ? 'border-[#A67C2E] bg-amber-50/50 text-[#A67C2E] ring-1 ring-[#A67C2E]' : 'border-gray-200 text-gray-800 hover:border-gray-300'"
                                >
                                    <img :src="imageUrl(v.image_path || v.image_url || v.image)" class="w-8 h-8 rounded object-cover border border-gray-200 shrink-0">
                                    <span class="text-[11px] font-bold truncate flex-1" x-text="v.name"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Size Selection -->
                @php
                    $availableSizes = is_array($product->sizes) ? $product->sizes : json_decode($product->sizes ?? '[]', true);
                    $availableSizes = is_array($availableSizes) ? $availableSizes : [];
                @endphp
                @if(!empty($availableSizes))
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-black text-gray-900">Size</span>
                        @if($product->size_guide_image || !empty($product->size_guide_measurements))
                            <button type="button" onclick="openSizeGuideModal()" class="text-[11px] font-bold text-[#A67C2E] hover:underline flex items-center gap-1 cursor-pointer">
                                📏 Size Chart
                            </button>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($availableSizes as $sz)
                        @php
                            $szStock = (int)($product->size_stocks[$sz] ?? $product->stock ?? 0);
                        @endphp
                        <button 
                            type="button"
                            @click="updateStock('{{ $sz }}')"
                            class="px-3.5 py-1.5 rounded-lg text-xs font-bold border transition-all cursor-pointer {{ $szStock <= 0 ? 'opacity-40 line-through cursor-not-allowed bg-gray-50 text-gray-400' : '' }}"
                            :class="selectedSize === '{{ $sz }}' ? 'border-[#A67C2E] bg-amber-50/50 text-[#A67C2E] ring-1 ring-[#A67C2E]' : 'border-gray-200 bg-white text-gray-800 hover:border-gray-300'"
                            {{ $szStock <= 0 ? 'disabled' : '' }}
                        >
                            {{ $sz }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Quantity Stepper -->
                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                    <div>
                        <div class="text-xs font-bold text-gray-800">Quantity</div>
                        <div class="text-[10px] text-gray-400" x-text="'Stock: ' + stock + ' available'"></div>
                    </div>
                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                        <button type="button" @click="if(quantity > 1) quantity--" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold transition-colors cursor-pointer">-</button>
                        <span class="w-9 text-center text-xs font-black text-gray-900" x-text="quantity"></span>
                        <button type="button" @click="if(quantity < stock) quantity++" class="w-8 h-8 flex items-center justify-center bg-gray-50 hover:bg-gray-100 text-gray-700 font-bold transition-colors cursor-pointer">+</button>
                    </div>
                </div>
            </div>

            <!-- Sticky Bottom Full-Width Action Button -->
            <div class="p-3 sm:p-4 border-t border-gray-100 bg-white shrink-0 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
                <button 
                    type="button"
                    @click="executeBuyNow()"
                    class="w-full py-3.5 px-4 rounded-xl text-white font-bold text-xs uppercase tracking-wider shadow-md hover:brightness-105 transition-all cursor-pointer text-center flex flex-col items-center justify-center leading-tight"
                    :style="buyNowMode === 'add_to_cart' ? 'background-color: #1E1915; box-shadow: 0 2px 10px rgba(0,0,0,0.25);' : 'background: linear-gradient(135deg, #C89B55 0%, #A67C2E 100%); box-shadow: 0 2px 10px rgba(166, 124, 46, 0.35);'"
                >
                    <span class="text-xs font-black" x-text="buyNowMode === 'add_to_cart' ? 'Add to Cart' : 'Buy Now'"></span>
                    <span class="text-[10px] font-semibold opacity-95" x-show="buyNowMode !== 'add_to_cart'">₱0 Shipping Fee</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══ Mobile-Only Sticky Bottom Action Bar (Fixed to Mobile Screen Bottom) ═══ --}}
    <div 
        id="mobile-bottom-action-bar"
        x-show="!showBuyNowSheet"
        x-cloak
        class="lg:hidden"
    >
        {{-- Store Icon Link --}}
        <a href="{{ ($product->sellerId || ($product->seller->id ?? null)) ? '/shops/' . ($product->sellerId ?? $product->seller->id) : '/' }}" 
           class="flex flex-col items-center justify-center text-gray-700 hover:text-black shrink-0"
           style="text-decoration: none; min-width: 44px;">
            <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l2-4h14l2 4M3 7h18M10 11h4v4h-4z"/>
            </svg>
            <span style="font-size: 10px; color: #374151; font-weight: 600; margin-top: 2px;">Store</span>
        </a>

        {{-- Chat Icon Button with Online Green Dot --}}
        <button 
            type="button" 
            @click="chatWithSeller('{{ $product->sellerId ?? ($product->seller->id ?? 0) }}', '{{ addslashes($product->seller->shopName ?? $product->seller->name ?? 'Artisan') }}')"
            class="flex flex-col items-center justify-center text-gray-700 hover:text-black shrink-0 cursor-pointer"
            style="background: transparent; border: none; padding: 0; min-width: 44px;"
        >
            <div style="position: relative; display: flex; align-items: center; justify-content: center;">
                <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <span style="position: absolute; bottom: -2px; right: -2px; width: 8px; height: 8px; border-radius: 50%; background-color: #00C853; border: 1.5px solid #FFFFFF;"></span>
            </div>
            <span style="font-size: 10px; color: #374151; font-weight: 600; margin-top: 2px;">Chat</span>
        </button>

        {{-- Dual CTA Buttons: Add to Cart (Charcoal / Onyx) & Buy Now (Dark Antique Gold) --}}
        <div style="flex: 1; display: flex; align-items: stretch; gap: 8px; margin-left: 4px;">
            {{-- Add to Cart Button (Luxury Onyx) --}}
            <button 
                type="button" 
                @click="openBuyNowSheet('add_to_cart')" 
                style="flex: 1; height: 42px; border-radius: 8px; background-color: #1E1915; color: #FFFFFF; font-weight: 800; font-size: 12px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer; transition: all 0.15s ease;"
                onmouseover="this.style.backgroundColor='#000000'"
                onmouseout="this.style.backgroundColor='#1E1915'"
            >
                <span>Add to Cart</span>
            </button>

            {{-- Buy Now Button (Dark Antique Gold Gradient) --}}
            <button 
                type="button" 
                @click="openBuyNowSheet('buy_now')" 
                style="flex: 1.15; height: 42px; border-radius: 8px; background: linear-gradient(135deg, #C89B55 0%, #A67C2E 100%); color: #FFFFFF; font-weight: 800; display: flex; flex-direction: column; align-items: center; justify-content: center; line-height: 1.15; border: none; cursor: pointer; transition: all 0.15s ease; box-shadow: 0 2px 10px rgba(166, 124, 46, 0.4);"
                onmouseover="this.style.opacity='0.92'"
                onmouseout="this.style.opacity='1'"
            >
                <span style="font-size: 12px; font-weight: 900; letter-spacing: -0.01em;">Buy Now</span>
                <span style="font-size: 9.5px; font-weight: 600; opacity: 0.95;">₱0 Shipping Fee</span>
            </button>
        </div>
    </div>
</div>

<script>
    function openSizeGuideModal() {
        var modal = document.getElementById('size-guide-modal');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeSizeGuideModal() {
        var modal = document.getElementById('size-guide-modal');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            document.body.style.overflow = '';
        }
    }

    function switchSizeGuideTab(tabName) {
        var tabs = ['men', 'women', 'kids'];
        tabs.forEach(function(t) {
            var content = document.getElementById('size-tab-content-' + t);
            var btn = document.getElementById('size-tab-btn-' + t);
            if (content) {
                content.style.display = (t === tabName) ? 'block' : 'none';
            }
            if (btn) {
                if (t === tabName) {
                    btn.className = 'flex-1 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest bg-white shadow-md text-black border border-gray-200 transition-all';
                } else {
                    btn.className = 'flex-1 py-2.5 rounded-xl text-xs font-semibold uppercase tracking-widest text-gray-400 hover:text-black transition-all';
                }
            }
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSizeGuideModal();
        }
    });
</script>

<style>
/* Mobile sticky bottom action bar: strictly fixed to screen bottom on mobile (<1024px) */
@media (min-width: 1024px) {
    #mobile-bottom-action-bar {
        display: none !important;
    }
}
@media (max-width: 1023px) {
    #mobile-bottom-action-bar {
        display: flex;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        background: #FFFFFF;
        border-top: 1px solid #E5E7EB;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.10);
        padding: 8px 12px;
        align-items: center;
        gap: 8px;
        width: 100%;
        box-sizing: border-box;
    }
    #mobile-bottom-action-bar[style*="display: none"],
    #mobile-bottom-action-bar[hidden] {
        display: none !important;
    }
}

/* Hide the floating circular chat widget button on mobile in product view since Chat is integrated in the sticky bottom action bar */
@media (max-width: 1023px) {
    .lumbarong-chat-wrapper > button {
        display: none !important;
    }
}
</style>

@endsection
