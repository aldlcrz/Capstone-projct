@extends('layouts.admin')
@section('title', 'Maintenance Mode')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-(--charcoal) font-serif">Maintenance Mode</h1>
        <p class="text-sm text-(--muted) mt-1">Put the site into maintenance mode to perform updates or repairs.</p>
    </div>

    {{-- Status Banner --}}
    @if($isMaintenanceMode)
    <div class="flex items-center gap-4 p-5 bg-amber-50 border border-amber-200 rounded-2xl">
        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <p class="font-bold text-amber-800">Maintenance Mode is ACTIVE</p>
            <p class="text-xs text-amber-600 mt-0.5">The site is currently unavailable to regular users.</p>
        </div>
        <span class="ml-auto px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-full uppercase tracking-wider animate-pulse">LIVE</span>
    </div>
    @else
    <div class="flex items-center gap-4 p-5 bg-green-50 border border-green-200 rounded-2xl">
        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <p class="font-bold text-green-800">Site is Operational</p>
            <p class="text-xs text-green-600 mt-0.5">All users can access the platform normally.</p>
        </div>
        <span class="ml-auto px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full uppercase tracking-wider">ONLINE</span>
    </div>
    @endif

    {{-- Toggle Form --}}
    <div class="bg-white dark:bg-[#1A1A2B] rounded-2xl border border-(--border) p-6 space-y-5" x-data="{ showModal: false }">
        <h2 class="text-xs font-bold uppercase tracking-widest text-(--muted) pb-3 border-b border-(--border)">Toggle Maintenance</h2>

        <form id="maintenanceForm" action="{{ route('admin.maintenance.toggle') }}" method="POST" class="space-y-5" @submit.prevent="showModal = true">
            @csrf
            <input type="hidden" name="enable" value="{{ $isMaintenanceMode ? '0' : '1' }}">

            <div>
                <label class="block text-xs font-bold text-(--charcoal) mb-1.5 uppercase tracking-wider">Maintenance Message</label>
                <textarea name="message" rows="3"
                    class="w-full px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream) resize-none"
                    placeholder="Message shown to users during maintenance...">{{ $maintenanceMessage }}</textarea>
                <p class="text-xs text-(--muted) mt-1">This message will be displayed on the maintenance page.</p>
            </div>

            <button type="submit"
                class="px-8 py-3 text-sm font-bold rounded-xl transition-all uppercase tracking-widest {{ $isMaintenanceMode ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-amber-500 hover:bg-amber-600 text-white' }}">
                {{ $isMaintenanceMode ? '✓ Bring Site Back Online' : '⚠ Enable Maintenance Mode' }}
            </button>
        </form>

        <!-- Alpine.js Modal Backdrop & Window -->
        <div x-show="showModal" 
             class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" 
             style="display: none;" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak>
            <div @click.outside="showModal = false" 
                 class="bg-white dark:bg-[#1A1A2B] w-full max-w-md rounded-3xl border border-gray-100 dark:border-[#2E2E42] shadow-2xl p-6 relative overflow-hidden text-left"
                 x-transition:enter="transition ease-out duration-300 transform"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200 transform"
                 x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                 x-transition:leave-end="opacity-0 scale-95 translate-y-4">
                
                <!-- Icon & Header -->
                <div class="flex items-start gap-4 mb-6">
                    @if($isMaintenanceMode)
                        <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-500/10 flex items-center justify-center text-green-600 dark:text-green-400 shrink-0 shadow-sm border border-green-100 dark:border-green-500/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    @else
                        <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-500/10 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 shadow-sm border border-amber-100 dark:border-amber-500/20 animate-pulse">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    @endif
                    <div class="space-y-1">
                        <h3 class="text-base font-bold text-(--charcoal) font-serif">
                            {{ $isMaintenanceMode ? 'Bring Website Back Online?' : 'Activate Maintenance Mode?' }}
                        </h3>
                        <p class="text-[11px] text-(--muted) leading-normal">Please confirm your request to switch the website operational status.</p>
                    </div>
                </div>

                <!-- Warning/Information Details -->
                <div class="bg-gray-50 dark:bg-black/10 rounded-2xl p-4 border border-gray-100 dark:border-white/5 space-y-3 mb-6">
                    @if($isMaintenanceMode)
                        <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed font-medium">
                            Enabling the website will immediately restore public access to the entire platform. 
                            Regular customers will be able to browse and purchase, and sellers can access their portals.
                        </p>
                    @else
                        <p class="text-xs text-amber-800 dark:text-amber-500 font-bold uppercase tracking-wider text-[10px]">What happens when active?</p>
                        <ul class="text-xs text-gray-500 dark:text-gray-400 space-y-2 list-disc list-inside leading-relaxed font-medium">
                            <li><span class="font-bold text-(--charcoal)">Customers / Guests</span> will be blocked from accessing any shop pages and see the notice.</li>
                            <li><span class="font-bold text-(--charcoal)">Sellers</span> will be blocked from their portals, product listings, and order tracking.</li>
                            <li><span class="font-bold text-(--charcoal)">API Services</span> will return 503 errors and be disabled.</li>
                            <li><span class="font-bold text-(--charcoal)">Only Admins</span> can bypass and manage site configurations.</li>
                        </ul>
                    @endif
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-white/5">
                    <button type="button" @click="showModal = false" 
                        class="px-5 py-2.5 rounded-xl border border-(--border) text-xs font-bold uppercase tracking-wider text-gray-500 hover:bg-gray-50 dark:hover:bg-white/5 transition-all">
                        Cancel
                    </button>
                    <button type="button" @click="document.getElementById('maintenanceForm').submit()"
                        class="px-5 py-2.5 rounded-xl text-xs font-bold uppercase tracking-wider text-white transition-all shadow-md {{ $isMaintenanceMode ? 'bg-green-500 hover:bg-green-600 shadow-green-500/10' : 'bg-amber-500 hover:bg-amber-600 shadow-amber-500/10' }}">
                        {{ $isMaintenanceMode ? 'Yes, Bring Online' : 'Yes, Start Maintenance' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach([
            ['icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Who can still access?', 'body' => 'Administrators can bypass maintenance mode by accessing the secret URL or logging in via /login.'],
            ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'How long will it take?', 'body' => 'Plan your maintenance window. Notify sellers and customers via email before enabling.'],
            ['icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z', 'title' => 'What is safe to do?', 'body' => 'Database migrations, cache clearing, dependency updates, and configuration changes.'],
        ] as $card)
        <div class="bg-white rounded-2xl border border-(--border) p-5">
            <svg class="w-6 h-6 text-(--rust) mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $card['icon'] }}"/>
            </svg>
            <h3 class="text-sm font-bold text-(--charcoal) mb-1">{{ $card['title'] }}</h3>
            <p class="text-xs text-(--muted) leading-relaxed">{{ $card['body'] }}</p>
        </div>
        @endforeach
    </div>
</div>
@endsection
