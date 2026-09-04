<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Complete Your Profile | LumBarong</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    
    <!-- Leaflet Map CSS & JS for Address Pinpointer -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    
    <style>
        body { font-family: 'Inter', sans-serif; background: #F7F3EE; }
        .font-serif { font-family: 'Playfair Display', serif; }
        [x-cloak] { display: none !important; }
        
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

        /* Leaflet custom pin styling */
        .lumbarong-pin-icon {
            background: transparent;
            border: none;
        }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen flex items-center justify-center p-3 sm:p-6 relative overflow-x-hidden" id="auth-body">
    <!-- Subtle warm background gradients -->
    <div class="absolute top-0 right-0 w-80 sm:w-140 h-80 sm:h-140 rounded-full -translate-y-1/2 translate-x-1/3 blur-3xl opacity-[0.06] pointer-events-none bg-[#C0422A]"></div>
    <div class="absolute bottom-0 left-0 w-72 sm:w-110 h-72 sm:h-110 rounded-full translate-y-1/2 -translate-x-1/3 blur-3xl opacity-[0.12] pointer-events-none bg-[#D4B896]"></div>

    <div class="w-full max-w-xl bg-white rounded-3xl sm:rounded-[2.5rem] border border-[#E5DDD5] p-4 sm:p-7 shadow-[0_20px_60px_rgba(60,40,20,0.08)] relative z-10 my-2 sm:my-6 transition-all duration-300" 
         x-data="onboardingSetup()" 
         x-init="init()">
        
        {{-- Top Status --}}
        <div class="flex items-center justify-end mb-2 sm:mb-3">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                Account Setup
            </div>
        </div>

        {{-- Header & Branding --}}
        <div class="text-center mb-5 sm:mb-7">
            <div class="flex justify-center mb-2.5">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full overflow-hidden shadow-md border-2 border-[#E8DFC8] bg-white p-0.5 shrink-0 flex items-center justify-center">
                    <img src="{{ asset('images/logo-icon.png') }}" alt="LumBarong Logo" class="w-full h-full object-contain rounded-full">
                </div>
            </div>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-[#C0422A]/10 text-[#C0422A] text-[10px] font-extrabold uppercase tracking-wider mb-1.5 border border-[#C0422A]/20">
                <span>Welcome to LumBarong</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-black italic tracking-tight text-gray-900 mb-1">
                Set Up Your Profile
            </h1>
            <p class="text-xs text-gray-600 font-medium max-w-md mx-auto leading-relaxed px-1">
                Add your details to personalize your authentic Lumban artisan experience and expedite delivery checkout. You can also skip and update these anytime.
            </p>
        </div>

        {{-- Error Summary Alert --}}
        @if ($errors->any())
            <div class="mb-4 p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-900 text-xs shadow-2xs animate-fade-in">
                <div class="flex items-center gap-2 font-bold mb-1">
                    <svg class="w-4 h-4 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Please review the highlighted details:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-red-700 font-medium">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Setup Form --}}
        <form action="{{ route('onboarding.save') }}" method="POST" @submit="isSubmitting = true" class="space-y-4 sm:space-y-5">
            @csrf

            {{-- Hidden Geolocation & Address Components for Backend Persistence --}}
            <input type="hidden" name="latitude" :value="addressForm.latitude || ''">
            <input type="hidden" name="longitude" :value="addressForm.longitude || ''">
            <input type="hidden" name="region" :value="addressForm.region || ''">
            <input type="hidden" name="province" :value="addressForm.province || ''">
            <input type="hidden" name="city" :value="addressForm.city || ''">
            <input type="hidden" name="barangay" :value="addressForm.barangay || ''">

            {{-- 1. BASIC INFORMATION SECTION --}}
            <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 space-y-3.5">
                <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-2">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[#1E1915]">Basic Information</h2>
                    <span class="text-[9.5px] font-extrabold text-[#C0422A] bg-[#C0422A]/10 border border-[#C0422A]/20 px-2 py-0.5 rounded-full uppercase tracking-wider">Required</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    {{-- Full Name --}}
                    <div>
                        <label for="name" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Full Name <span class="text-[#C0422A]">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', Auth::user()->name) }}" 
                               required
                               placeholder="e.g. Maria Santos" 
                               class="w-full h-11 px-3.5 bg-white border {{ $errors->has('name') ? 'border-red-400 bg-red-50/50' : 'border-[#D8CEBE]' }} rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-2xs">
                    </div>

                    {{-- Preferred Username --}}
                    <div>
                        <label for="username" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Preferred Username <span class="text-[#C0422A]">*</span>
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               value="{{ old('username', Auth::user()->username) }}" 
                               required
                               placeholder="e.g. mariasantos" 
                               class="w-full h-11 px-3.5 bg-white border {{ $errors->has('username') ? 'border-red-400 bg-red-50/50' : 'border-[#D8CEBE]' }} rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-2xs">
                    </div>

                    {{-- Phone Number --}}
                    <div class="sm:col-span-2">
                        <label for="mobileNumber" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Mobile Phone Number
                        </label>
                        <div class="flex items-center rounded-xl bg-white border {{ $errors->has('mobileNumber') ? 'border-red-400 bg-red-50/50' : 'border-[#D8CEBE]' }} overflow-hidden focus-within:border-[#C0422A] focus-within:ring-2 focus-within:ring-[#C0422A]/15 transition-all shadow-2xs h-11">
                            <div class="flex items-center justify-center px-3.5 bg-[#FAF7F2] border-r border-[#D8CEBE] text-xs font-bold text-gray-700 select-none shrink-0 h-full">
                                <span>+63</span>
                            </div>
                            <input type="text" 
                                   id="mobileNumber" 
                                   name="mobileNumber" 
                                   value="{{ old('mobileNumber', Auth::user()->mobileNumber) }}" 
                                   placeholder="9123456789" 
                                   class="w-full h-full px-3.5 bg-transparent text-xs font-semibold text-gray-900 outline-none placeholder:text-gray-400">
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. PRIMARY DELIVERY ADDRESS SECTION (CUSTOMER PROFILE INTERACTIVE UI) --}}
            <div class="bg-[#FAF8F5] border border-[#ECE3D2] rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 space-y-3.5">
                <div class="flex items-center justify-between border-b border-[#ECE3D2] pb-2">
                    <h2 class="text-xs font-black uppercase tracking-wider text-[#1E1915]">Primary Delivery Address</h2>
                    <span class="text-[9.5px] font-extrabold text-amber-800 bg-amber-100/80 border border-amber-300/50 px-2 py-0.5 rounded-full uppercase tracking-wider">Optional for now</span>
                </div>

                {{-- Interactive Map Pinpointer Container --}}
                <div class="space-y-2 pb-1">
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-[#8C6212] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            <span>Pin Delivery Location</span>
                        </label>
                        <button type="button"
                                @click="locateUserGps()"
                                :disabled="isLocatingGps"
                                class="bg-[#FAF5EA] border border-[#E6D8BA] text-[#8C6212] hover:bg-[#EAE2D2] px-2.5 py-1 rounded-xl text-[10px] sm:text-[11px] font-bold inline-flex items-center gap-1.5 cursor-pointer transition-all shadow-2xs disabled:opacity-50 shrink-0">
                            <template x-if="isLocatingGps">
                                <svg class="w-3.5 h-3.5 animate-spin text-[#8C6212]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </template>
                            <template x-if="!isLocatingGps">
                                <svg class="w-3.5 h-3.5 text-[#8C6212]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </template>
                            <span x-text="isLocatingGps ? 'Locating GPS...' : 'Use Current Location'"></span>
                        </button>
                    </div>

                    {{-- Map Search Input --}}
                    <div class="relative flex items-center w-full">
                        <div class="absolute left-3 flex items-center pointer-events-none text-[#8C827A] z-5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text" 
                               x-model="mapSearchQuery" 
                               @keydown.enter.prevent="searchMapLocation()" 
                               placeholder="Search street, barangay, or landmark..." 
                               class="w-full h-10 pl-9 pr-20 bg-white border border-[#D8CEBE] rounded-xl text-xs text-gray-900 outline-none focus:border-[#996515] transition-all shadow-2xs">
                        <button type="button" 
                                @click="searchMapLocation()" 
                                :disabled="pinSearching" 
                                class="absolute right-1.5 h-7 px-3 bg-[#1E1915] text-[#DFC97A] border-none rounded-lg text-[10px] font-extrabold uppercase tracking-wider hover:bg-black transition-all flex items-center justify-center disabled:opacity-50 cursor-pointer z-5">
                            <span x-text="pinSearching ? '...' : 'Search'"></span>
                        </button>
                    </div>

                    {{-- Leaflet Map Container --}}
                    <div class="h-40 sm:h-52 w-full rounded-xl overflow-hidden border border-[#ECE3D2] relative z-10 shadow-inner"
                         x-ref="addressMapContainer"></div>

                    {{-- Detected Location Bar --}}
                    <div class="p-2.5 bg-white border border-[#ECE3D2] rounded-xl flex items-center justify-between gap-2 text-[10.5px]">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0 animate-pulse"></span>
                            <span class="text-[#78716C] font-medium truncate" x-text="detectedLocationName || 'Drag pin or tap on map to lock coordinates'"></span>
                        </div>
                        <span class="shrink-0 px-2 py-0.5 bg-[#1E1915] text-[#DFC97A] text-[9px] font-black rounded-md uppercase tracking-wider shadow-2xs"
                              x-text="addressForm.latitude && addressForm.longitude ? 'Pin Locked' : 'Set Pin'"></span>
                    </div>
                </div>

                {{-- 4-Tier Philippine Location Dropdown Selector (PSGC) --}}
                <div class="relative">
                    <label class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                        Region, Province, City, Barangay
                    </label>
                    <div @click="toggleLocationDropdown()"
                         class="w-full h-11 px-3.5 bg-white border border-[#D8CEBE] rounded-xl flex items-center justify-between cursor-pointer transition-all shadow-2xs hover:border-[#C0422A]">
                        <span class="truncate text-xs" 
                              :class="getLocationSummary() ? 'text-gray-900 font-bold' : 'text-gray-400 font-medium'" 
                              x-text="getLocationSummary() || 'Select Region, Province, City, Barangay'"></span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 shrink-0" 
                             :class="locationDropdownOpen ? 'rotate-180' : ''" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </div>

                    {{-- Dropdown Tab Menu Panel --}}
                    <div x-show="locationDropdownOpen" 
                         @click.away="locationDropdownOpen = false"
                         class="absolute left-0 right-0 z-50 mt-1.5 bg-white border border-[#ECE3D2] rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-64 animate-fade-in" 
                         x-cloak>
                        
                        {{-- Tabs Navigation --}}
                        <div class="flex border-b border-gray-100 bg-[#FAF8F5] text-[10px] font-bold text-gray-500">
                            <button @click="activeTab = 'region'" type="button" 
                                    :class="activeTab === 'region' ? 'text-[#996515] bg-white border-b-2 border-[#996515] font-extrabold' : ''" 
                                    class="flex-1 py-2 text-center transition-colors">Region</button>
                            <button @click="if(selectedRegion && hasProvinces) activeTab = 'province'" type="button" 
                                    :disabled="!selectedRegion || !hasProvinces" 
                                    :class="activeTab === 'province' ? 'text-[#996515] bg-white border-b-2 border-[#996515] font-extrabold' : ''" 
                                    class="flex-1 py-2 text-center disabled:opacity-35 transition-colors">Province</button>
                            <button @click="if(selectedProvince || (selectedRegion && !hasProvinces)) activeTab = 'city'" type="button" 
                                    :disabled="!selectedProvince && (hasProvinces || !selectedRegion)" 
                                    :class="activeTab === 'city' ? 'text-[#996515] bg-white border-b-2 border-[#996515] font-extrabold' : ''" 
                                    class="flex-1 py-2 text-center disabled:opacity-35 transition-colors">City</button>
                            <button @click="if(selectedCity) activeTab = 'barangay'" type="button" 
                                    :disabled="!selectedCity" 
                                    :class="activeTab === 'barangay' ? 'text-[#996515] bg-white border-b-2 border-[#996515] font-extrabold' : ''" 
                                    class="flex-1 py-2 text-center disabled:opacity-35 transition-colors">Barangay</button>
                        </div>

                        {{-- Search Field for active tab --}}
                        <div class="p-2 border-b border-gray-100 bg-gray-50/70">
                            <input type="text" 
                                   x-model="locationSearch" 
                                   :placeholder="'Search ' + activeTab + '...'" 
                                   class="w-full h-8 px-2.5 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-[#996515]">
                        </div>

                        {{-- List of Geographic Options --}}
                        <div class="flex-1 overflow-y-auto max-h-40 divide-y divide-gray-50 text-xs">
                            <template x-if="loadingGeoData">
                                <div class="p-4 text-center text-xs text-gray-400 flex items-center justify-center gap-2">
                                    <svg class="animate-spin h-3.5 w-3.5 text-[#996515]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span>Loading geographic records...</span>
                                </div>
                            </template>

                            <template x-if="activeTab === 'region' && !loadingGeoData">
                                <div>
                                    <template x-for="reg in filteredGeoList(regionsList)" :key="reg.code">
                                        <button type="button" @click="selectRegion(reg)" class="w-full text-left px-3.5 py-2 hover:bg-amber-50/50 block truncate text-gray-800 transition-colors" x-text="reg.name"></button>
                                    </template>
                                </div>
                            </template>
                            <template x-if="activeTab === 'province' && !loadingGeoData">
                                <div>
                                    <template x-for="prov in filteredGeoList(provincesList)" :key="prov.code">
                                        <button type="button" @click="selectProvince(prov)" class="w-full text-left px-3.5 py-2 hover:bg-amber-50/50 block truncate text-gray-800 transition-colors" x-text="prov.name"></button>
                                    </template>
                                </div>
                            </template>
                            <template x-if="activeTab === 'city' && !loadingGeoData">
                                <div>
                                    <template x-for="ct in filteredGeoList(citiesList)" :key="ct.code">
                                        <button type="button" @click="selectCity(ct)" class="w-full text-left px-3.5 py-2 hover:bg-amber-50/50 block truncate text-gray-800 transition-colors" x-text="ct.name"></button>
                                    </template>
                                </div>
                            </template>
                            <template x-if="activeTab === 'barangay' && !loadingGeoData">
                                <div>
                                    <template x-for="bgy in filteredGeoList(barangaysList)" :key="bgy.code">
                                        <button type="button" @click="selectBarangay(bgy)" class="w-full text-left px-3.5 py-2 hover:bg-amber-50/50 block truncate text-gray-800 transition-colors" x-text="bgy.name"></button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Street Address & Postal Code Grid --}}
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    {{-- House No / Street --}}
                    <div class="sm:col-span-2">
                        <label for="houseNo" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Street Name, Building, House No.
                        </label>
                        <input type="text" 
                               id="houseNo" 
                               name="houseNo" 
                               x-model="addressForm.houseNo"
                               value="{{ old('houseNo') }}" 
                               placeholder="e.g. Unit 402, Sunset Bldg, Rizal St." 
                               class="w-full h-11 px-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-2xs">
                    </div>

                    {{-- Postal Code --}}
                    <div class="sm:col-span-1">
                        <label for="postalCode" class="block text-[11px] font-bold text-gray-800 uppercase tracking-wider mb-1">
                            Postal Code
                        </label>
                        <input type="text" 
                               id="postalCode" 
                               name="postalCode" 
                               x-model="addressForm.postalCode"
                               @input="addressForm.postalCode = addressForm.postalCode.replace(/[^0-9]/g, '')"
                               value="{{ old('postalCode') }}" 
                               maxlength="4"
                               placeholder="e.g. 4014" 
                               class="w-full h-11 px-3.5 bg-white border border-[#D8CEBE] rounded-xl text-xs font-semibold text-gray-900 outline-none focus:border-[#C0422A] focus:ring-2 focus:ring-[#C0422A]/15 transition-all shadow-2xs">
                    </div>
                </div>
            </div>

            {{-- Submit Action Button --}}
            <div class="pt-1.5">
                <button type="submit" 
                        :disabled="isSubmitting"
                        style="background: linear-gradient(to right, #C0422A, #B83D26, #A3341E);"
                        class="w-full h-12 sm:h-13 hover:opacity-95 text-white rounded-xl sm:rounded-2xl font-black uppercase tracking-wider text-xs sm:text-sm transition-all shadow-md shadow-[#C0422A]/20 hover:shadow-lg hover:shadow-[#C0422A]/30 active:scale-[0.99] flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60">
                    <span x-show="!isSubmitting" class="flex items-center gap-2">
                        <span>Save & Continue to LumBarong</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                    <span x-show="isSubmitting" x-cloak class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Saving Details...
                    </span>
                </button>
            </div>
        </form>

        {{-- Skip for Now Form (Secondary Action) --}}
        <form action="{{ route('onboarding.skip') }}" method="POST" class="mt-2.5 text-center">
            @csrf
            <button type="submit" 
                    class="text-xs font-bold text-gray-400 hover:text-gray-700 transition-colors py-1.5 px-3.5 rounded-xl hover:bg-gray-100 inline-flex items-center gap-1.5 cursor-pointer">
                <span>Skip for Now</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </form>

        {{-- Trust & Privacy Footer --}}
        <p class="text-center text-[10px] text-gray-400 mt-3 leading-relaxed">
            🔒 Your personal information is encrypted & never shared with third parties.
        </p>
    </div>

    {{-- Alpine State Script --}}
    <script>
        function onboardingSetup() {
            return {
                isSubmitting: false,
                map: null,
                marker: null,
                lat: 14.2952,
                lng: 121.4647,
                mapSearchQuery: '',
                pinSearching: false,
                isLocatingGps: false,
                detectedLocationName: '',
                
                // Location dropdown states
                locationDropdownOpen: false,
                activeTab: 'region',
                locationSearch: '',
                loadingGeoData: false,
                hasProvinces: true,
                regionsList: [],
                provincesList: [],
                citiesList: [],
                barangaysList: [],
                selectedRegion: null,
                selectedProvince: null,
                selectedCity: null,
                selectedBarangay: null,

                addressForm: {
                    houseNo: @json(old('houseNo', '')),
                    street: @json(old('street', '')),
                    barangay: @json(old('barangay', '')),
                    city: @json(old('city', '')),
                    province: @json(old('province', '')),
                    region: @json(old('region', '')),
                    postalCode: @json(old('postalCode', '')),
                    latitude: null,
                    longitude: null
                },

                init() {
                    this.initAddressMap(this.lat, this.lng);
                    window.addEventListener('resize', () => {
                        if (this.map) this.map.invalidateSize();
                    });
                },

                // Real-Time Map Location Picker
                initAddressMap(lat = 14.2952, lng = 121.4647) {
                    this.$nextTick(() => {
                        if (!this.$refs.addressMapContainer) return;

                        if (this.map) {
                            this.map.setView([lat, lng], 15);
                            if (this.marker) this.marker.setLatLng([lat, lng]);
                            setTimeout(() => { if (this.map) this.map.invalidateSize(); }, 300);
                            return;
                        }

                        if (typeof L === 'undefined') {
                            console.warn('Leaflet library is still loading...');
                            setTimeout(() => this.initAddressMap(lat, lng), 500);
                            return;
                        }

                        this.map = L.map(this.$refs.addressMapContainer, {
                            attributionControl: false
                        }).setView([lat, lng], 14);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19
                        }).addTo(this.map);

                        const customPinIcon = L.divIcon({
                            className: 'lumbarong-pin-icon',
                            html: `
                                <div style="position:relative;width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                                    <div style="width:28px;height:28px;background:#1E1915;border:2.5px solid #DFC97A;border-radius:50% 50% 50% 0;transform:rotate(-45deg);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 10px rgba(0,0,0,0.35);">
                                        <span style="transform:rotate(45deg);color:#DFC97A;font-size:11px;font-weight:900;">✦</span>
                                    </div>
                                    <div style="position:absolute;bottom:-6px;width:10px;height:4px;background:rgba(0,0,0,0.25);border-radius:50%;filter:blur(1px);"></div>
                                </div>
                            `,
                            iconSize: [32, 32],
                            iconAnchor: [16, 32],
                            popupAnchor: [0, -32]
                        });

                        this.marker = L.marker([lat, lng], {
                            draggable: true,
                            icon: customPinIcon
                        }).addTo(this.map);

                        this.map.on('click', (e) => {
                            this.updatePinLocation(e.latlng.lat, e.latlng.lng);
                        });

                        this.marker.on('dragend', (e) => {
                            const pos = e.target.getLatLng();
                            this.updatePinLocation(pos.lat, pos.lng);
                        });

                        this.reverseGeocode(lat, lng);

                        setTimeout(() => {
                            if (this.map) this.map.invalidateSize();
                        }, 400);
                    });
                },

                updatePinLocation(lat, lng, doReverseGeocode = true) {
                    this.addressForm.latitude = lat;
                    this.addressForm.longitude = lng;
                    if (this.marker) this.marker.setLatLng([lat, lng]);
                    if (this.map) this.map.panTo([lat, lng]);
                    if (doReverseGeocode) this.reverseGeocode(lat, lng);
                },

                async locateUserGps() {
                    if (!navigator.geolocation) {
                        alert('Geolocation is not supported by your browser.');
                        return;
                    }
                    this.isLocatingGps = true;
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.isLocatingGps = false;
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            this.updatePinLocation(lat, lng);
                            if (this.map) this.map.setView([lat, lng], 16);
                        },
                        (err) => {
                            this.isLocatingGps = false;
                            alert('Unable to retrieve GPS location. Please ensure location permissions are granted.');
                        },
                        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                    );
                },

                async searchMapLocation() {
                    if (!this.mapSearchQuery.trim()) return;
                    this.pinSearching = true;
                    try {
                        const query = encodeURIComponent(this.mapSearchQuery.trim() + ', Philippines');
                        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`);
                        const data = await res.json();
                        if (data && data.length > 0) {
                            const loc = data[0];
                            const lat = parseFloat(loc.lat);
                            const lng = parseFloat(loc.lon);
                            this.updatePinLocation(lat, lng);
                            if (this.map) this.map.setView([lat, lng], 16);
                        } else {
                            alert('Location not found. Try searching for a specific landmark, street, or barangay.');
                        }
                    } catch(e) {
                        console.error('Location search failed:', e);
                    }
                    this.pinSearching = false;
                },

                async reverseGeocode(lat, lon) {
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1&accept-language=en-US`);
                        const data = await res.json();
                        if (data) {
                            this.detectedLocationName = data.display_name || '';
                            if (data.address) {
                                await this.autoFillFromGeocode(data.address);
                            }
                        }
                    } catch(e) {
                        console.error('Reverse geocode failed:', e);
                    }
                },

                async autoFillFromGeocode(addr) {
                    if (!addr) return;
                    const rawRegion = addr.region || addr.state || '';
                    const rawProvince = addr.province || addr.state_district || addr.county || '';
                    const rawCity = addr.city || addr.town || addr.municipality || addr.city_district || '';
                    const rawBarangay = addr.village || addr.suburb || addr.neighbourhood || addr.quarter || addr.residential || '';
                    const rawPostal = addr.postcode || '';

                    if (rawPostal && /^\d{4}$/.test(rawPostal)) {
                        this.addressForm.postalCode = rawPostal;
                    }

                    if (rawProvince) this.addressForm.province = rawProvince;
                    if (rawCity) this.addressForm.city = rawCity;
                    if (rawBarangay) this.addressForm.barangay = rawBarangay;
                    if (rawRegion) this.addressForm.region = rawRegion;

                    try {
                        if (!this.regionsList || this.regionsList.length === 0) {
                            await this.loadRegions();
                        }

                        const normalize = (str) => (str || '').toLowerCase().replace(/[^a-z0-9]/g, '');
                        const normRegion = normalize(rawRegion);
                        const normProv = normalize(rawProvince);
                        const normCity = normalize(rawCity);
                        const normBgy = normalize(rawBarangay);

                        let matchedRegion = this.regionsList.find(r => {
                            const nr = normalize(r.name);
                            return (normRegion && (nr.includes(normRegion) || normRegion.includes(nr))) ||
                                   (normProv && nr.includes(normProv));
                        });

                        if (!matchedRegion && (normRegion.includes('ncr') || normRegion.includes('metromanila') || normRegion.includes('nationalcapital') || normProv.includes('metromanila') || normCity.includes('manila') || normCity.includes('quezoncity'))) {
                            matchedRegion = this.regionsList.find(r => r.code === '130000000');
                        }

                        if (matchedRegion) {
                            this.selectedRegion = matchedRegion;
                            this.addressForm.region = matchedRegion.name;

                            if (matchedRegion.code === '130000000') {
                                this.hasProvinces = false;
                                this.selectedProvince = { code: '130000000', name: 'Metro Manila' };
                                this.addressForm.province = 'Metro Manila';
                                await this.loadNCRCities();
                            } else {
                                this.hasProvinces = true;
                                await this.loadProvinces(matchedRegion.code);

                                let matchedProv = this.provincesList.find(p => {
                                    const np = normalize(p.name);
                                    return normProv && (np.includes(normProv) || normProv.includes(np));
                                });
                                if (matchedProv) {
                                    this.selectedProvince = matchedProv;
                                    this.addressForm.province = matchedProv.name;
                                    await this.loadCities(matchedProv.code);
                                }
                            }

                            if (this.citiesList && this.citiesList.length > 0) {
                                let matchedCity = this.citiesList.find(c => {
                                    const nc = normalize(c.name);
                                    return normCity && (nc.includes(normCity) || normCity.includes(nc));
                                });
                                if (matchedCity) {
                                    this.selectedCity = matchedCity;
                                    this.addressForm.city = matchedCity.name;
                                    await this.loadBarangays(matchedCity.code);

                                    if (this.barangaysList && this.barangaysList.length > 0) {
                                        let matchedBgy = this.barangaysList.find(b => {
                                            const nb = normalize(b.name);
                                            return normBgy && (nb.includes(normBgy) || normBgy.includes(nb));
                                        });
                                        if (matchedBgy) {
                                            this.selectedBarangay = matchedBgy;
                                            this.addressForm.barangay = matchedBgy.name;
                                        }
                                    }
                                }
                            }
                        }
                    } catch(e) {
                        console.error('PSGC autofill match error:', e);
                    }
                },

                // PSGC 4-Tier Dropdown Methods
                async toggleLocationDropdown() {
                    this.locationDropdownOpen = !this.locationDropdownOpen;
                    if (this.locationDropdownOpen && this.regionsList.length === 0) {
                        await this.loadRegions();
                    }
                },

                async loadRegions() {
                    this.loadingGeoData = true;
                    try {
                        const res = await fetch('https://psgc.gitlab.io/api/regions/');
                        if (res.ok) this.regionsList = await res.json();
                    } catch(e) {
                        console.error('Failed to load PSGC regions:', e);
                    }
                    this.loadingGeoData = false;
                },

                async selectRegion(region) {
                    this.selectedRegion = region;
                    this.addressForm.region = region.name;
                    this.selectedProvince = null; this.addressForm.province = '';
                    this.selectedCity = null; this.addressForm.city = '';
                    this.selectedBarangay = null; this.addressForm.barangay = '';
                    this.locationSearch = '';
                    if (region.code === '130000000') {
                        this.hasProvinces = false;
                        this.provincesList = [];
                        this.addressForm.province = 'Metro Manila';
                        this.selectedProvince = { code: '130000000', name: 'Metro Manila' };
                        this.activeTab = 'city';
                        await this.loadNCRCities();
                    } else {
                        this.hasProvinces = true;
                        this.activeTab = 'province';
                        await this.loadProvinces(region.code);
                    }
                },

                async loadNCRCities() {
                    this.loadingGeoData = true;
                    try {
                        const res = await fetch('https://psgc.gitlab.io/api/regions/130000000/cities-municipalities/');
                        if (res.ok) this.citiesList = await res.json();
                    } catch(e) {}
                    this.loadingGeoData = false;
                },

                async loadProvinces(regionCode) {
                    this.loadingGeoData = true;
                    try {
                        const res = await fetch(`https://psgc.gitlab.io/api/regions/${regionCode}/provinces/`);
                        if (res.ok) this.provincesList = await res.json();
                    } catch(e) {}
                    this.loadingGeoData = false;
                },

                async selectProvince(province) {
                    this.selectedProvince = province;
                    this.addressForm.province = province.name;
                    this.selectedCity = null; this.addressForm.city = '';
                    this.selectedBarangay = null; this.addressForm.barangay = '';
                    this.locationSearch = '';
                    this.activeTab = 'city';
                    await this.loadCities(province.code);
                },

                async loadCities(provinceCode) {
                    this.loadingGeoData = true;
                    try {
                        const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
                        if (res.ok) this.citiesList = await res.json();
                    } catch(e) {}
                    this.loadingGeoData = false;
                },

                async selectCity(city) {
                    this.selectedCity = city;
                    this.addressForm.city = city.name;
                    this.selectedBarangay = null; this.addressForm.barangay = '';
                    this.locationSearch = '';
                    this.activeTab = 'barangay';
                    await this.loadBarangays(city.code);
                    this.syncMapToSelectedLocation();
                },

                async loadBarangays(cityCode) {
                    this.loadingGeoData = true;
                    try {
                        const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                        if (res.ok) this.barangaysList = await res.json();
                    } catch(e) {}
                    this.loadingGeoData = false;
                },

                selectBarangay(barangay) {
                    this.selectedBarangay = barangay;
                    this.addressForm.barangay = barangay.name;
                    this.locationDropdownOpen = false;
                    this.locationSearch = '';
                    this.syncMapToSelectedLocation();
                },

                filteredGeoList(list) {
                    if (!this.locationSearch) return list;
                    const q = this.locationSearch.toLowerCase();
                    return list.filter(item => item.name && item.name.toLowerCase().includes(q));
                },

                getLocationSummary() {
                    if (this.addressForm.region || this.addressForm.province || this.addressForm.city || this.addressForm.barangay) {
                        return [this.addressForm.region, this.addressForm.province, this.addressForm.city, this.addressForm.barangay].filter(Boolean).join(', ');
                    }
                    return '';
                },

                async syncMapToSelectedLocation() {
                    const parts = [
                        this.addressForm.barangay,
                        this.addressForm.city,
                        this.addressForm.province,
                        'Philippines'
                    ].filter(Boolean);
                    if (parts.length <= 1) return;
                    try {
                        const query = encodeURIComponent(parts.join(', '));
                        const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`);
                        const data = await res.json();
                        if (data && data.length > 0) {
                            const loc = data[0];
                            const lat = parseFloat(loc.lat);
                            const lng = parseFloat(loc.lon);
                            this.updatePinLocation(lat, lng, false);
                            if (this.map) this.map.setView([lat, lng], 15);
                        }
                    } catch(e) {}
                }
            };
        }
    </script>
</body>
</html>
