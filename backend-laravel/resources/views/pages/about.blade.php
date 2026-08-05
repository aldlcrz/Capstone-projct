@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4">
    <!-- Header -->
    <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 px-3 py-1 bg-[#C0420A]/10 text-[#C0420A] text-[10px] font-black uppercase tracking-widest rounded-full mb-3">
            Heritage & Tradition
        </div>
        <h1 class="font-serif text-4xl lg:text-5xl font-bold text-gray-900 mb-4">About LumBarong</h1>
        <p class="text-gray-500 text-sm max-w-xl mx-auto leading-relaxed">
            Connecting the master embroiderers of Lumban, Laguna directly with heritage fashion lovers around the world.
        </p>
    </div>

    <!-- Main Content Card -->
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 lg:p-12 space-y-8">
        <div>
            <h2 class="text-xl font-bold text-gray-900 mb-3">Our Mission</h2>
            <p class="text-gray-600 text-sm leading-relaxed">
                LumBarong was founded with a singular purpose: to preserve, empower, and celebrate the intricate art of hand-embroidered Barong Tagalog from Lumban, Laguna—the official Barong Capital of the Philippines. We empower local artisans by giving them a direct platform to show their craft to customers everywhere.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-100">
            <div class="bg-stone-50 p-5 rounded-2xl border border-stone-100">
                <div class="text-[#C0420A] font-black text-lg mb-1">100% Authentic</div>
                <div class="text-xs text-gray-500">Handcrafted directly by verified master artisans in Lumban.</div>
            </div>
            <div class="bg-stone-50 p-5 rounded-2xl border border-stone-100">
                <div class="text-[#C0420A] font-black text-lg mb-1">Custom Sizing</div>
                <div class="text-xs text-gray-500">Tailored custom measurements for a flawless personal fit.</div>
            </div>
            <div class="bg-stone-50 p-5 rounded-2xl border border-stone-100">
                <div class="text-[#C0420A] font-black text-lg mb-1">Direct Artisan Support</div>
                <div class="text-xs text-gray-500">Your purchases directly sustain artisan livelihoods and traditions.</div>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
            <h2 class="text-xl font-bold text-gray-900 mb-3">The Lumban Craftsmanship</h2>
            <p class="text-gray-600 text-sm leading-relaxed mb-4">
                Every piece available on LumBarong tells a story. From delicate Piña fibers to elegant Organza and Jusi fabrics, each embroidery pattern is meticulously stitched by skilled hands using techniques passed down through generations.
            </p>
            <a href="/" class="inline-flex items-center gap-2 px-6 py-3 bg-[#3D2B1F] hover:bg-[#C0420A] text-white text-xs font-bold uppercase tracking-widest rounded-xl transition-colors">
                Explore Catalogue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
        </div>
    </div>
</div>
@endsection
