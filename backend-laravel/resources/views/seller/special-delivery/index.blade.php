@extends('layouts.seller')

@section('content')
<div x-data="specialDeliverySettings()" class="space-y-6 sm:space-y-8 max-w-6xl pb-28 lg:pb-12 px-2 sm:px-6">
    
    {{-- Top Header with Back Navigation --}}
    <div class="flex items-center gap-3 pb-2 border-b" style="border-color: #E8DECB;">
        <a href="{{ route('seller.profile') }}" class="w-10 h-10 rounded-2xl bg-white border border-[#E8DECB] hover:border-[#C49520] hover:bg-[#FDF8EE] flex items-center justify-center text-[#1E1915] transition-all shadow-2xs group" title="Back to Profile">
            <svg class="w-5 h-5 group-hover:-translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <div class="text-[10px] font-bold text-[#C49520] uppercase tracking-[0.2em]">✦ Logistics &amp; Artisan Rider</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#1E1915]">Special Delivery <span class="text-[#766C60] font-light italic">Settings</span></h1>
            <p class="text-xs text-[#766C60] mt-0.5 font-medium">Set your delivery coverage, areas, and delivery fee for Special Delivery. This will be used for customers in your selected areas.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2.5 shadow-2xs">
            <span class="text-base">✓</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1 shadow-2xs">
            <div class="font-bold uppercase tracking-wider text-[10px]">Please correct the following errors:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('seller.special-delivery.update') }}" method="POST" class="space-y-6 sm:space-y-8">
        @csrf

        {{-- 1. Enable / Disable Status Card --}}
        <div class="rounded-3xl p-5 sm:p-6 transition-all border shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4"
             :style="isEnabled ? 'background: #F4FBF4; border-color: #BFE5BF;' : 'background: #FAF8F5; border-color: #E8DECB;'">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-3xl shadow-2xs shrink-0"
                     :style="isEnabled ? 'background: #E1F5E1; color: #2E7D32;' : 'background: #F0EAE1; color: #A09585;'">
                    🏍️
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-bold text-[#1E1915]">Enable Special Delivery</h2>
                    <p class="text-xs text-[#766C60] mt-0.5">Let customers order from your local artisan rider within your selected Laguna towns.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 shrink-0 self-end sm:self-center">
                <span class="text-xs font-black uppercase tracking-widest px-3 py-1 rounded-full text-[10px]"
                      :style="isEnabled ? 'background: #2E7D32; color: #FFFFFF;' : 'background: #A09585; color: #FFFFFF;'"
                      x-text="isEnabled ? 'Enabled' : 'Disabled'"></span>
                <input type="hidden" name="special_delivery_enabled" :value="isEnabled ? '1' : '0'">
                <button type="button" @click="isEnabled = !isEnabled" role="switch" :aria-checked="isEnabled.toString()"
                        class="relative w-14 h-7 rounded-full transition-colors cursor-pointer shadow-inner"
                        :style="isEnabled ? 'background: #2E7D32;' : 'background: #D6CCBA;'">
                    <span class="absolute top-1 left-1 w-5 h-5 rounded-full bg-white shadow-md transition-transform"
                          :class="isEnabled ? 'translate-x-7' : ''"></span>
                </button>
            </div>
        </div>

        {{-- 2. Main Two-Column Grid: Delivery Coverage Area (Left) & Delivery Fee Preview (Right) --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8">
            
            {{-- Left Column: Coverage Area & Base Fee (7 cols) --}}
            <div class="lg:col-span-7 space-y-6">
                
                {{-- Delivery Coverage Area Box --}}
                <div class="rounded-3xl p-5 sm:p-7 bg-[#FFFCF7] border border-[#E8DECB] shadow-xs space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-[#C49520] border border-amber-200/60 flex items-center justify-center font-bold text-base shrink-0">
                            📍
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black uppercase tracking-wider text-[#1E1915]">Delivery Coverage Area</h3>
                            <p class="text-[11px] sm:text-xs text-[#766C60]">Choose where your artisan rider can deliver. Coverage is exclusively within Laguna province.</p>
                        </div>
                    </div>

                    {{-- Fixed Province Badge --}}
                    <div class="space-y-1.5">
                        <label class="text-[10px] font-bold text-[#766C60] uppercase tracking-widest block">Province / Region</label>
                        <div class="flex items-center justify-between p-3.5 rounded-2xl bg-[#FDF8EE] border border-[#E8DECB]">
                            <div class="flex items-center gap-2.5">
                                <span class="text-base">🇵🇭</span>
                                <div>
                                    <span class="text-xs font-black text-[#1E1915] block">Laguna, Philippines</span>
                                    <span class="text-[10px] text-[#766C60]">Special Delivery is strictly limited to Laguna artisan municipalities</span>
                                </div>
                            </div>
                            <span class="text-[9px] font-black uppercase tracking-widest bg-amber-100/70 text-[#996515] border border-[#E6D8BA] px-2.5 py-1 rounded-lg">
                                Fixed Scope
                            </span>
                        </div>
                    </div>

                    {{-- Search & Bulk Actions Bar --}}
                    <div class="pt-2 border-t border-[#ECE3D2] space-y-3">
                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
                            {{-- Search Input --}}
                            <div class="relative flex-1">
                                <input type="text" x-model="searchQuery" placeholder="Search Laguna towns (e.g. Lumban, Pagsanjan)..."
                                       class="w-full pl-9 pr-4 py-2 bg-white border border-[#E8DECB] rounded-xl text-xs font-medium focus:outline-none focus:border-[#C49520] text-[#1E1915]">
                                <svg class="w-4 h-4 text-[#A09585] absolute left-3 top-2.5 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>

                            {{-- Bulk Action Buttons --}}
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button" @click="selectAll()"
                                        class="px-3 py-2 bg-[#FDF8EE] hover:bg-[#1E1915] text-[#996515] hover:text-[#DFC97A] border border-[#E8DECB] rounded-xl text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                    ✓ Select All (30)
                                </button>
                                <button type="button" @click="clearAll()"
                                        class="px-3 py-2 bg-white hover:bg-rose-50 text-[#766C60] hover:text-rose-700 border border-[#E8DECB] hover:border-rose-200 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all cursor-pointer">
                                    ✕ Clear All
                                </button>
                            </div>
                        </div>

                        {{-- Selection Count Pill --}}
                        <div class="flex items-center justify-between text-[11px] text-[#766C60]">
                            <span class="font-bold text-[#1E1915]">Laguna Municipalities (<span x-text="filteredMunicipalities.length"></span> shown)</span>
                            <span class="font-black text-[#996515] bg-[#FAF5EA] border border-[#E6D8BA] px-2 py-0.5 rounded-md"
                                  x-text="selectedCount + ' of ' + totalCount + ' Covered'"></span>
                        </div>
                    </div>

                    {{-- Municipalities Checkbox & Surcharge Grid --}}
                    <div class="max-h-96 overflow-y-auto pr-1 space-y-2.5 custom-scrollbar border-t border-[#ECE3D2] pt-3">
                        <template x-for="muni in filteredMunicipalities" :key="muni.key">
                            <div class="p-3 rounded-2xl border transition-all flex items-center justify-between gap-3"
                                 :style="isMuniSelected(muni.key) ? 'background: #FFFFFF; border-color: #C49520; box-shadow: 0 1px 4px rgba(196,149,32,0.1);' : 'background: #FAF8F5; border-color: #E8DECB; opacity: 0.85;'">
                                
                                {{-- Checkbox and Name --}}
                                <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                                    <input type="checkbox" name="municipalities[]" :value="muni.key"
                                           :checked="isMuniSelected(muni.key)"
                                           @change="toggleMuni(muni.key)"
                                           class="w-4 h-4 rounded text-[#2E7D32] focus:ring-[#2E7D32] border-[#E8DECB] cursor-pointer">
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-[#1E1915] truncate" x-text="muni.name"></div>
                                        <div class="text-[10px] text-[#A09585] font-mono" x-text="'Postal: ' + muni.postal_code"></div>
                                    </div>
                                </label>

                                {{-- Inline Surcharge Input (Active when Selected) --}}
                                <div x-show="isMuniSelected(muni.key)" class="flex items-center gap-2 shrink-0">
                                    <span class="text-[9px] font-bold text-[#766C60] uppercase tracking-wider hidden sm:inline">Surcharge:</span>
                                    <div class="flex items-center rounded-xl border bg-[#FDF8EE] overflow-hidden px-2 py-1" style="border-color: #E8DECB;">
                                        <span class="text-[10px] font-bold text-[#996515] mr-1">+₱</span>
                                        <input type="number" :name="'surcharges[' + muni.key + ']'"
                                               x-model="muniSurcharges[muni.key]"
                                               @input="updateFee(muni.key)"
                                               min="0" max="10000" step="0.01"
                                               class="w-16 text-xs font-bold text-[#1E1915] bg-transparent outline-none font-mono text-right"
                                               placeholder="0.00">
                                    </div>
                                    <div class="w-16 text-right font-mono font-bold text-xs text-[#2E7D32]" x-text="'₱' + formatMoney(calculateMuniFee(muni.key))"></div>
                                </div>
                            </div>
                        </template>

                        <div x-show="filteredMunicipalities.length === 0" class="py-8 text-center text-xs text-[#A09585] italic">
                            No Laguna municipalities match "<span x-text="searchQuery"></span>"
                        </div>
                    </div>
                </div>

                {{-- Base Delivery Fee Card --}}
                <div class="rounded-3xl p-5 sm:p-7 bg-[#FFFCF7] border border-[#E8DECB] shadow-xs space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/60 flex items-center justify-center font-bold text-base shrink-0">
                            ₱
                        </div>
                        <div>
                            <h3 class="text-sm sm:text-base font-black uppercase tracking-wider text-[#1E1915]">Base Delivery Fee</h3>
                            <p class="text-[11px] sm:text-xs text-[#766C60]">The starting flat delivery fee charged before municipal surcharges.</p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="baseDeliveryFeeInput" class="text-[10px] font-bold text-[#766C60] uppercase tracking-widest block">Base Fee Amount (PHP) *</label>
                        <div class="flex items-center rounded-2xl border bg-white overflow-hidden p-2" style="border-color: #E8DECB;">
                            <span class="px-3 text-base font-bold text-[#A16D19]">₱</span>
                            <input type="number" id="baseDeliveryFeeInput" name="base_delivery_fee"
                                   x-model="baseFee"
                                   @input="recalculateAll()"
                                   min="0" max="10000" step="0.01" required
                                   class="w-full py-2 pr-3 text-base font-black outline-none bg-white text-[#1E1915] font-mono"
                                   placeholder="50.00">
                        </div>
                        <p class="text-[10px] text-[#A09585]">
                            Formula: <strong>Final Delivery Fee = Base Delivery Fee + Municipality Surcharge</strong>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Right Column: Live Delivery Fee Preview Table (5 cols) --}}
            <div class="lg:col-span-5 space-y-6">
                <div class="rounded-3xl p-5 sm:p-7 bg-[#FFFCF7] border border-[#E8DECB] shadow-xs space-y-4 sticky top-6">
                    <div class="flex items-center justify-between pb-3 border-b border-[#ECE3D2]">
                        <div class="flex items-center gap-2.5">
                            <span class="text-base">📊</span>
                            <div>
                                <h3 class="text-xs sm:text-sm font-black uppercase tracking-wider text-[#1E1915]">Delivery Fee Preview</h3>
                                <p class="text-[10px] text-[#766C60]">Live customer fee calculation per destination</p>
                            </div>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-full"
                              x-text="selectedCount + ' Active'"></span>
                    </div>

                    {{-- Live Fee Table --}}
                    <div class="max-h-120 overflow-y-auto custom-scrollbar">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-[9px] font-black uppercase tracking-widest text-[#766C60] border-b border-[#ECE3D2] pb-2">
                                    <th class="pb-2">Destination</th>
                                    <th class="pb-2 text-right">Surcharge</th>
                                    <th class="pb-2 text-right">Final Fee</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#F0EAE1]">
                                <template x-for="muni in selectedMunicipalitiesList" :key="muni.key">
                                    <tr class="hover:bg-[#FDF8EE] transition-colors">
                                        <td class="py-2.5 font-bold text-[#1E1915]">
                                            <span x-text="muni.name"></span>
                                        </td>
                                        <td class="py-2.5 text-right font-mono text-[#766C60]">
                                            <span x-text="getSurcharge(muni.key) > 0 ? '+₱' + formatMoney(getSurcharge(muni.key)) : '—'"></span>
                                        </td>
                                        <td class="py-2.5 text-right font-mono font-black text-[#2E7D32]">
                                            <span x-text="'₱' + formatMoney(calculateMuniFee(muni.key))"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>

                        <div x-show="selectedCount === 0" class="py-12 text-center text-xs text-[#A09585] italic space-y-2">
                            <div class="text-2xl">🛵</div>
                            <p class="font-semibold text-[#1E1915]">No Laguna municipalities covered</p>
                            <p class="text-[11px]">Check the boxes on the left to activate Special Delivery for specific towns.</p>
                        </div>
                    </div>

                    {{-- Preview Footer Note --}}
                    <div class="pt-3 border-t border-[#ECE3D2] flex items-start gap-2 text-[10px] text-[#766C60] leading-snug">
                        <span class="text-xs shrink-0">ℹ️</span>
                        <span>During checkout, buyers with delivery addresses in your selected municipalities will see these exact fees.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 3. Live Coverage Summary Box --}}
        <div class="rounded-3xl p-5 sm:p-7 bg-[#F4FBF4] border border-[#BFE5BF] shadow-xs space-y-3">
            <div class="flex items-center gap-2 text-emerald-800 text-xs font-black uppercase tracking-wider">
                <span class="w-5 h-5 rounded-full bg-[#2E7D32] text-white flex items-center justify-center text-[10px]">✓</span>
                <span>Coverage Summary</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div class="p-3.5 rounded-2xl bg-white/80 border border-[#BFE5BF] space-y-0.5">
                    <span class="text-[9px] font-bold text-[#766C60] uppercase tracking-widest block">Scope &amp; Province</span>
                    <span class="font-bold text-[#1E1915] text-sm block">Laguna, Philippines</span>
                </div>

                <div class="p-3.5 rounded-2xl bg-white/80 border border-[#BFE5BF] space-y-0.5">
                    <span class="text-[9px] font-bold text-[#766C60] uppercase tracking-widest block">Selected Municipalities</span>
                    <span class="font-bold text-[#1E1915] text-sm block">
                        <span x-text="selectedCount"></span> of 30 Towns Covered
                    </span>
                </div>

                <div class="p-3.5 rounded-2xl bg-white/80 border border-[#BFE5BF] space-y-0.5">
                    <span class="text-[9px] font-bold text-[#766C60] uppercase tracking-widest block">Base Fee &amp; Surcharge Range</span>
                    <span class="font-black text-[#2E7D32] text-sm font-mono block">
                        Base: ₱<span x-text="formatMoney(baseFee)"></span>
                    </span>
                </div>
            </div>

            <div x-show="selectedCount > 0" class="pt-1 text-[11px] text-[#2E7D32] font-medium leading-relaxed">
                <strong>Active Towns:</strong> <span x-text="selectedMunicipalitiesNames.join(', ')"></span>
            </div>
        </div>

        {{-- 4. Important Notes Card --}}
        <div class="p-5 rounded-3xl bg-[#F0F7FF] border border-[#CDE1F8] text-xs space-y-2 text-[#1E429F]">
            <div class="flex items-center gap-2 font-black uppercase text-[10px] tracking-wider text-[#1A56DB]">
                <span>ℹ️</span>
                <span>Important Guidelines for Artisan Riders</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-[11px] text-[#1E429F]/90 leading-relaxed">
                <li>Special Delivery is exclusively available for customer delivery addresses inside <strong>Laguna province</strong>.</li>
                <li>Each seller independently configures their coverage area. Only customers located in your checked municipalities can select Special Delivery.</li>
                <li>The final delivery fee charged to the buyer is strictly <code>Base Delivery Fee + Municipality Surcharge</code>.</li>
                <li>Customers outside your covered towns will automatically see standard courier shipping options (J&amp;T Express, LBC, Flash Express).</li>
            </ul>
        </div>

        {{-- 5. Sticky / Fixed Bottom Action Buttons --}}
        <div class="pt-4 border-t border-[#ECE3D2] flex items-center justify-between gap-4">
            <a href="{{ route('seller.profile') }}" class="px-6 py-3 rounded-2xl text-xs font-bold uppercase tracking-widest bg-white border border-[#E8DECB] text-[#766C60] hover:bg-[#FDF8EE] hover:text-[#1E1915] transition-all cursor-pointer">
                Cancel
            </a>
            <button type="submit" class="px-8 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest text-white transition-all shadow-md cursor-pointer flex items-center gap-2 hover:scale-[1.02]"
                    style="background: #2E7D32;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Save Special Delivery Settings</span>
            </button>
        </div>
    </form>
</div>

<script>
function specialDeliverySettings() {
    const rawMunicipalities = @json($municipalitiesData);

    // Build initial state
    const selectedMunis = {};
    const muniSurcharges = {};
    const allList = [];

    Object.keys(rawMunicipalities).forEach(key => {
        const item = rawMunicipalities[key];
        allList.push(item);
        if (item.selected) {
            selectedMunis[key] = true;
        }
        muniSurcharges[key] = item.surcharge !== null && item.surcharge !== undefined ? item.surcharge : 0.00;
    });

    return {
        isEnabled: @json($isEnabled),
        baseFee: @json($baseFee),
        searchQuery: '',
        allMunicipalities: allList,
        selectedMunis: selectedMunis,
        muniSurcharges: muniSurcharges,

        get totalCount() {
            return this.allMunicipalities.length;
        },

        get selectedCount() {
            return Object.keys(this.selectedMunis).filter(k => this.selectedMunis[k]).length;
        },

        get filteredMunicipalities() {
            const q = this.searchQuery.toLowerCase().trim();
            if (!q) return this.allMunicipalities;
            return this.allMunicipalities.filter(m => 
                m.name.toLowerCase().includes(q) || 
                m.key.toLowerCase().includes(q) ||
                m.postal_code.includes(q)
            );
        },

        get selectedMunicipalitiesList() {
            return this.allMunicipalities.filter(m => this.selectedMunis[m.key]);
        },

        get selectedMunicipalitiesNames() {
            return this.selectedMunicipalitiesList.map(m => m.name);
        },

        isMuniSelected(key) {
            return !!this.selectedMunis[key];
        },

        toggleMuni(key) {
            if (this.selectedMunis[key]) {
                delete this.selectedMunis[key];
            } else {
                this.selectedMunis[key] = true;
                if (this.muniSurcharges[key] === undefined) {
                    this.muniSurcharges[key] = 0.00;
                }
            }
        },

        selectAll() {
            this.allMunicipalities.forEach(m => {
                this.selectedMunis[m.key] = true;
                if (this.muniSurcharges[m.key] === undefined) {
                    this.muniSurcharges[m.key] = 0.00;
                }
            });
        },

        clearAll() {
            this.selectedMunis = {};
        },

        getSurcharge(key) {
            const val = parseFloat(this.muniSurcharges[key]);
            return isNaN(val) || val < 0 ? 0.00 : val;
        },

        calculateMuniFee(key) {
            const base = parseFloat(this.baseFee) || 0.00;
            const surcharge = this.getSurcharge(key);
            return Math.max(0, base + surcharge);
        },

        updateFee(key) {
            // Triggers reactivity
        },

        recalculateAll() {
            // Triggers reactivity
        },

        formatMoney(amount) {
            const num = parseFloat(amount) || 0;
            return num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endsection
