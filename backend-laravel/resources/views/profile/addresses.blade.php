@extends('layouts.app')

@section('content')
<div class="max-w-[1100px] mx-auto py-8">
    <div class="flex flex-col md:flex-row gap-6">

        @include('profile._sidebar', ['user' => $user])

        <main class="flex-1 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" x-data="addressManager()">

            {{-- Header --}}
            <div class="flex items-center justify-between px-8 py-6 border-b border-gray-100">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">My Addresses</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Manage your delivery registry</p>
                </div>
                <button @click="openAdd()"
                    class="flex items-center gap-2 px-5 py-2.5 bg-[#C0420A] text-white text-sm font-semibold rounded-lg hover:bg-[#a83808] transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add New Address
                </button>
            </div>

            {{-- Address List --}}
            <div class="px-8 py-6 min-h-[260px]">
                {{-- Search --}}
                <div class="relative mb-5 max-w-xs">
                    <input x-model="search" type="text" placeholder="Search address..."
                        class="w-full h-9 pl-9 pr-4 bg-gray-50 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A] transition-colors">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <template x-if="loading">
                    <div class="py-16 text-center text-gray-400 text-sm">Loading addresses…</div>
                </template>

                <template x-if="!loading && filteredAddresses().length === 0">
                    <div class="py-16 text-center">
                        <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm font-semibold text-gray-400">No addresses found</p>
                        <p class="text-xs text-gray-400 mt-1">Start by adding a new delivery address.</p>
                    </div>
                </template>

                <template x-if="!loading">
                    <div class="space-y-3">
                        <template x-for="addr in filteredAddresses()" :key="addr.id">
                            <div class="p-4 border border-gray-200 rounded-xl hover:border-gray-300 transition-colors relative">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                                            <span class="text-sm font-bold text-gray-900" x-text="addr.recipientName"></span>
                                            <span class="text-gray-300">|</span>
                                            <span class="text-sm text-gray-500" x-text="addr.phone"></span>
                                            <template x-if="addr.isDefault">
                                                <span class="px-2 py-0.5 bg-red-50 border border-[#C0420A] text-[#C0420A] text-[10px] font-bold rounded">Default</span>
                                            </template>
                                        </div>
                                        <p class="text-sm text-gray-600 leading-relaxed"
                                           x-text="[addr.houseNo, addr.street, addr.barangay, addr.city, addr.province, addr.postalCode].filter(Boolean).join(', ')">
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-end gap-2 shrink-0">
                                        <div class="flex gap-3">
                                            <button @click="openEdit(addr)" class="text-[#C0420A] text-xs font-semibold hover:underline">Edit</button>
                                            <button @click="deleteAddress(addr.id)" class="text-gray-400 text-xs font-semibold hover:text-red-500 hover:underline">Delete</button>
                                        </div>
                                        <template x-if="!addr.isDefault">
                                            <button @click="setDefault(addr.id)"
                                                class="text-[11px] border border-gray-300 text-gray-500 px-2 py-0.5 rounded hover:border-[#C0420A] hover:text-[#C0420A] transition-colors">
                                                Set as Default
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Add / Edit Modal --}}
            <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="modalOpen = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 space-y-5 max-h-[90vh] overflow-y-auto">
                    <h3 class="text-base font-bold text-gray-900" x-text="editId ? 'Edit Address' : 'Add New Address'"></h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="text-xs font-semibold text-gray-500 mb-1 block">Full Name *</label>
                            <input x-model="form.recipientName" type="text" placeholder="Recipient's full name"
                                class="w-full h-10 px-3 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A]">
                        </div>
                        <div class="col-span-2">
                            <label class="text-xs font-semibold text-gray-500 mb-1 block">Phone Number *</label>
                            <input x-model="form.phone" type="text" placeholder="e.g. 09xxxxxxxxx"
                                class="w-full h-10 px-3 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A]">
                        </div>

                        <!-- Region, Province, City, Barangay Dropdown Selector -->
                        <div class="col-span-2 relative">
                            <label class="text-xs font-semibold text-gray-500 mb-1 block">Region, Province, City, Barangay *</label>
                            <div @click="toggleLocationDropdown()"
                                 class="w-full h-10 px-3 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A] flex items-center justify-between cursor-pointer bg-white">
                                <span class="truncate" :class="getLocationSummary() ? 'text-gray-900' : 'text-gray-400'" x-text="getLocationSummary() || 'Select Region, Province, City, Barangay'"></span>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="locationDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            <!-- Location Dropdown Panel -->
                            <div x-show="locationDropdownOpen"
                                 @click.away="locationDropdownOpen = false"
                                 class="absolute left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden flex flex-col"
                                 style="max-height: 350px;"
                                 x-cloak>

                                 <!-- Tabs -->
                                 <div class="flex border-b border-gray-150 bg-gray-50 text-xs font-bold text-gray-500">
                                     <button @click="activeTab = 'region'"
                                             type="button"
                                             :class="activeTab === 'region' ? 'text-[#C0420A] border-b-2 border-[#C0420A] bg-white' : ''"
                                             class="flex-1 py-3 text-center border-b border-transparent hover:bg-white transition-colors">
                                         Region
                                     </button>
                                     <button @click="if(selectedRegion && hasProvinces) activeTab = 'province'"
                                             type="button"
                                             :disabled="!selectedRegion || !hasProvinces"
                                             :class="activeTab === 'province' ? 'text-[#C0420A] border-b-2 border-[#C0420A] bg-white' : ''"
                                             class="flex-1 py-3 text-center border-b border-transparent hover:bg-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                                         Province
                                         <span x-show="!selectedRegion" class="text-[10px] text-red-500">🚫</span>
                                     </button>
                                     <button @click="if(selectedProvince || (selectedRegion && !hasProvinces)) activeTab = 'city'"
                                             type="button"
                                             :disabled="!selectedProvince && (hasProvinces || !selectedRegion)"
                                             :class="activeTab === 'city' ? 'text-[#C0420A] border-b-2 border-[#C0420A] bg-white' : ''"
                                             class="flex-1 py-3 text-center border-b border-transparent hover:bg-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                                         City
                                         <span x-show="!selectedProvince && hasProvinces" class="text-[10px] text-red-500">🚫</span>
                                     </button>
                                     <button @click="if(selectedCity) activeTab = 'barangay'"
                                             type="button"
                                             :disabled="!selectedCity"
                                             :class="activeTab === 'barangay' ? 'text-[#C0420A] border-b-2 border-[#C0420A] bg-white' : ''"
                                             class="flex-1 py-3 text-center border-b border-transparent hover:bg-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                                         Barangay
                                         <span x-show="!selectedCity" class="text-[10px] text-red-500">🚫</span>
                                     </button>
                                 </div>

                                 <!-- Inline Search -->
                                 <div class="p-2 border-b border-gray-100 bg-gray-50/50">
                                     <input type="text" x-model="locationSearch" :placeholder="'Search ' + activeTab + '...'"
                                            class="w-full h-8 px-3 bg-white border border-gray-200 rounded-lg text-xs outline-none focus:border-[#C0420A] transition-colors">
                                 </div>

                                 <!-- Scrollable List -->
                                 <div class="flex-1 overflow-y-auto min-h-[180px] max-h-[220px] divide-y divide-gray-50">
                                     <!-- Loading Geo Data Spinner -->
                                     <div x-show="loadingGeoData" class="flex items-center justify-center py-10 text-xs text-gray-400 gap-2">
                                         <svg class="animate-spin h-4 w-4 text-[#C0420A]" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                             <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                             <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                         </svg>
                                         <span>Loading geographical data...</span>
                                     </div>

                                     <!-- Region List -->
                                     <template x-if="activeTab === 'region' && !loadingGeoData">
                                         <div class="py-1">
                                             <template x-for="reg in filteredGeoList(regionsList)" :key="reg.code">
                                                 <button type="button" @click="selectRegion(reg)"
                                                      :class="selectedRegion?.code === reg.code ? 'bg-[#C0420A]/5 text-[#C0420A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                                      class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                                      <span x-text="reg.name + ' (' + reg.regionName + ')'"></span>
                                                      <span x-show="selectedRegion?.code === reg.code" class="text-xs">✓</span>
                                                 </button>
                                             </template>
                                         </div>
                                     </template>

                                     <!-- Province List -->
                                     <template x-if="activeTab === 'province' && !loadingGeoData">
                                         <div class="py-1">
                                             <template x-for="prov in filteredGeoList(provincesList)" :key="prov.code">
                                                 <button type="button" @click="selectProvince(prov)"
                                                      :class="selectedProvince?.code === prov.code ? 'bg-[#C0420A]/5 text-[#C0420A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                                      class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                                      <span x-text="prov.name"></span>
                                                      <span x-show="selectedProvince?.code === prov.code" class="text-xs">✓</span>
                                                 </button>
                                             </template>
                                         </div>
                                     </template>

                                     <!-- City List -->
                                     <template x-if="activeTab === 'city' && !loadingGeoData">
                                         <div class="py-1">
                                             <template x-for="ct in filteredGeoList(citiesList)" :key="ct.code">
                                                 <button type="button" @click="selectCity(ct)"
                                                      :class="selectedCity?.code === ct.code ? 'bg-[#C0420A]/5 text-[#C0420A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                                      class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                                      <span x-text="ct.name"></span>
                                                      <span x-show="selectedCity?.code === ct.code" class="text-xs">✓</span>
                                                 </button>
                                             </template>
                                         </div>
                                     </template>

                                     <!-- Barangay List -->
                                     <template x-if="activeTab === 'barangay' && !loadingGeoData">
                                         <div class="py-1">
                                             <template x-for="brgy in filteredGeoList(barangaysList)" :key="brgy.code">
                                                 <button type="button" @click="selectBarangay(brgy)"
                                                      :class="selectedBarangay?.code === brgy.code ? 'bg-[#C0420A]/5 text-[#C0420A] font-bold' : 'text-gray-700 hover:bg-gray-50'"
                                                      class="w-full text-left px-4 py-2.5 text-xs transition-colors flex items-center justify-between">
                                                      <span x-text="brgy.name"></span>
                                                      <span x-show="selectedBarangay?.code === brgy.code" class="text-xs">✓</span>
                                                 </button>
                                             </template>
                                         </div>
                                     </template>

                                     <!-- Empty Geo Results -->
                                     <div x-show="!loadingGeoData && filteredGeoList(getCurrentTabList()).length === 0"
                                          class="p-8 text-center text-xs text-gray-400">
                                          No items found.
                                     </div>
                                 </div>
                            </div>
                        </div>

                        <div class="col-span-2">
                            <label class="text-xs font-semibold text-gray-500 mb-1 block">House / Unit / Bldg No. *</label>
                            <input x-model="form.houseNo" type="text" placeholder="House no., building, unit"
                                class="w-full h-10 px-3 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A]">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 mb-1 block">Street</label>
                            <input x-model="form.street" type="text" placeholder="Street name"
                                class="w-full h-10 px-3 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A]">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 mb-1 block">Postal Code</label>
                            <input x-model="form.postalCode" type="text" placeholder="Postal code"
                                class="w-full h-10 px-3 border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A]">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="form.isDefault" class="accent-[#C0420A]">
                        <span class="text-sm text-gray-600">Set as default address</span>
                    </label>

                    <div x-show="formError" class="px-4 py-2 bg-red-50 border border-red-100 rounded-lg text-red-600 text-xs font-semibold" x-text="formError"></div>

                    <div class="flex gap-3 pt-2">
                        <button @click="modalOpen = false"
                            class="flex-1 py-2.5 border border-gray-200 text-sm text-gray-500 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button @click="saveAddress()" :disabled="saving"
                            class="flex-1 py-2.5 bg-[#C0420A] text-white text-sm font-semibold rounded-lg hover:bg-[#a83808] transition-colors disabled:opacity-60">
                            <span x-text="saving ? 'Saving…' : 'Confirm'"></span>
                        </button>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function addressManager() {
    return {
        addresses: [],
        loading: true,
        search: '',
        modalOpen: false,
        saving: false,
        editId: null,
        formError: '',
        form: { recipientName:'', phone:'', houseNo:'', street:'', barangay:'', city:'', province:'', region:'', postalCode:'', isDefault: false },

        // Location picker variables
        locationDropdownOpen: false,
        activeTab: 'region',
        locationSearch: '',
        regionsList: [],
        provincesList: [],
        citiesList: [],
        barangaysList: [],
        selectedRegion: null,
        selectedProvince: null,
        selectedCity: null,
        selectedBarangay: null,
        loadingGeoData: false,
        hasProvinces: true,

        async init() {
            await this.fetchAddresses();
        },

        async fetchAddresses() {
            this.loading = true;
            try {
                const r = await fetch('/api/addresses', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                this.addresses = await r.json();
            } catch(e) { this.addresses = []; }
            this.loading = false;
        },

        filteredAddresses() {
            if (!this.search) return this.addresses;
            const q = this.search.toLowerCase();
            return this.addresses.filter(a =>
                [a.recipientName, a.phone, a.houseNo, a.street, a.barangay, a.city, a.province, a.postalCode]
                    .some(v => v && v.toLowerCase().includes(q))
            );
        },

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
                if (res.ok) {
                    this.regionsList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load regions", e);
            }
            this.loadingGeoData = false;
        },

        async selectRegion(region) {
            this.selectedRegion = region;
            this.form.region = region.name;

            this.selectedProvince = null;
            this.form.province = '';
            this.selectedCity = null;
            this.form.city = '';
            this.selectedBarangay = null;
            this.form.barangay = '';
            this.locationSearch = '';

            if (region.code === '130000000') {
                this.hasProvinces = false;
                this.provincesList = [];
                this.form.province = 'Metro Manila';
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
                if (res.ok) {
                    this.citiesList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load NCR cities", e);
            }
            this.loadingGeoData = false;
        },

        async loadProvinces(regionCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/regions/${regionCode}/provinces/`);
                if (res.ok) {
                    this.provincesList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load provinces", e);
            }
            this.loadingGeoData = false;
        },

        async selectProvince(province) {
            this.selectedProvince = province;
            this.form.province = province.name;

            this.selectedCity = null;
            this.form.city = '';
            this.selectedBarangay = null;
            this.form.barangay = '';
            this.locationSearch = '';

            this.activeTab = 'city';
            await this.loadCities(province.code);
        },

        async loadCities(provinceCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/provinces/${provinceCode}/cities-municipalities/`);
                if (res.ok) {
                    this.citiesList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load cities", e);
            }
            this.loadingGeoData = false;
        },

        async selectCity(city) {
            this.selectedCity = city;
            this.form.city = city.name;

            this.selectedBarangay = null;
            this.form.barangay = '';
            this.locationSearch = '';

            this.activeTab = 'barangay';
            await this.loadBarangays(city.code);
        },

        async loadBarangays(cityCode) {
            this.loadingGeoData = true;
            try {
                const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${cityCode}/barangays/`);
                if (res.ok) {
                    this.barangaysList = await res.json();
                }
            } catch(e) {
                console.error("Failed to load barangays", e);
            }
            this.loadingGeoData = false;
        },

        selectBarangay(barangay) {
            this.selectedBarangay = barangay;
            this.form.barangay = barangay.name;
            this.locationDropdownOpen = false;
            this.locationSearch = '';
        },

        filteredGeoList(list) {
            if (!list) return [];
            if (!this.locationSearch) return list;
            const q = this.locationSearch.toLowerCase();
            return list.filter(item =>
                (item.name && item.name.toLowerCase().includes(q)) ||
                (item.regionName && item.regionName.toLowerCase().includes(q))
            );
        },

        getCurrentTabList() {
            if (this.activeTab === 'region') return this.regionsList;
            if (this.activeTab === 'province') return this.provincesList;
            if (this.activeTab === 'city') return this.citiesList;
            if (this.activeTab === 'barangay') return this.barangaysList;
            return [];
        },

        getLocationSummary() {
            if (this.selectedRegion || this.selectedProvince || this.selectedCity || this.selectedBarangay) {
                return [
                    this.selectedRegion?.name,
                    this.selectedProvince?.name,
                    this.selectedCity?.name,
                    this.selectedBarangay?.name
                ].filter(Boolean).join(', ');
            }
            if (this.form.region || this.form.province || this.form.city || this.form.barangay) {
                return [
                    this.form.region,
                    this.form.province,
                    this.form.city,
                    this.form.barangay
                ].filter(Boolean).join(', ');
            }
            return '';
        },

        openAdd() {
            this.editId = null;
            this.form = { recipientName:'', phone:'', houseNo:'', street:'', barangay:'', city:'', province:'', region:'', postalCode:'', isDefault: false };
            this.formError = '';

            this.selectedRegion = null;
            this.selectedProvince = null;
            this.selectedCity = null;
            this.selectedBarangay = null;
            this.activeTab = 'region';
            this.locationSearch = '';
            this.locationDropdownOpen = false;

            this.modalOpen = true;
        },

        openEdit(addr) {
            this.editId = addr.id;
            this.form = { ...addr };
            this.formError = '';

            this.selectedRegion = addr.region ? { name: addr.region } : null;
            this.selectedProvince = addr.province ? { name: addr.province } : null;
            this.selectedCity = addr.city ? { name: addr.city } : null;
            this.selectedBarangay = addr.barangay ? { name: addr.barangay } : null;
            this.activeTab = 'region';
            this.locationSearch = '';
            this.locationDropdownOpen = false;

            this.modalOpen = true;
        },

        async saveAddress() {
            if (!this.form.recipientName || !this.form.phone || !this.form.houseNo || !this.form.city || !this.form.province) {
                this.formError = 'Please fill in all required fields.';
                return;
            }
            this.formError = '';
            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            try {
                const url = this.editId ? `/api/addresses/${this.editId}` : '/api/addresses';
                const method = this.editId ? 'PUT' : 'POST';
                const r = await fetch(url, {
                    method, headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(this.form)
                });
                if (!r.ok) { const d = await r.json(); this.formError = d.message ?? 'Failed to save.'; }
                else { this.modalOpen = false; await this.fetchAddresses(); }
            } catch(e) { this.formError = 'Network error. Please try again.'; }
            this.saving = false;
        },

        async deleteAddress(id) {
            if (!confirm('Delete this address?')) return;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`/api/addresses/${id}`, { method:'DELETE', headers:{ 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' } });
            await this.fetchAddresses();
        },

        async setDefault(id) {
            const token = document.querySelector('meta[name="csrf-token"]').content;
            await fetch(`/api/addresses/${id}/set-default`, { method:'PATCH', headers:{ 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' } });
            await this.fetchAddresses();
        },
    }
}
</script>
@endsection
