<div x-data="psgcSelector()" @click.away="isOpen = false" @psgc-set.window="set($event.detail)" class="relative">
    <button type="button" @click="toggle" class="w-full px-4 py-3 border border-gray-200 rounded-lg flex justify-between items-center text-sm text-gray-600 bg-white hover:border-[#C0422A] transition-colors focus:outline-none focus:border-[#C0422A] shadow-sm">
        <span x-text="displayValue() || 'Region, Province, City, Barangay'" :class="displayValue() ? 'text-black' : 'text-gray-400'"></span>
        <svg class="w-4 h-4 text-gray-400 transition-transform" :class="isOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>

    <div x-show="isOpen" 
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="absolute top-full left-0 right-0 z-50 mt-1 bg-white border border-gray-200 rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[350px]"
    >
        <div class="flex border-b border-gray-100 shrink-0 bg-gray-50/50">
            <template x-for="t in ['Region', 'Province', 'City', 'Barangay']">
                <button type="button" 
                    @click="tab = t.toLowerCase()" 
                    class="flex-1 py-3 text-[11px] font-bold uppercase tracking-widest transition-colors border-b-2" 
                    :class="tab === t.toLowerCase() ? 'border-[#C0422A] text-[#C0422A]' : 'border-transparent text-gray-400 hover:text-gray-600'" 
                    x-text="t"
                ></button>
            </template>
        </div>
        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar">
            <div x-show="loading" class="flex justify-center items-center py-12">
                <svg class="w-6 h-6 text-[#C0422A] animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
            <div x-show="!loading" class="space-y-1">
                <template x-for="loc in list" :key="loc.code">
                    <button type="button" 
                        @click="select(loc)" 
                        class="w-full text-left px-4 py-2.5 text-[13px] font-medium rounded-lg hover:bg-[#FFF5F2] hover:text-[#C0422A] transition-colors"
                        :class="(selected.region === loc.name || selected.province === loc.name || selected.city === loc.name || selected.barangay === loc.name) ? 'bg-[#FFF5F2] text-[#C0422A]' : 'text-gray-700'"
                        x-text="loc.name"
                    ></button>
                </template>
                <div x-show="list.length === 0" class="py-12 text-center text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                    Select previous level first
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function psgcSelector() {
    return {
        isOpen: false,
        tab: 'region',
        loading: false,
        regions: [],
        provinces: [],
        cities: [],
        barangays: [],
        selected: { region: '', province: '', city: '', barangay: '' },
        get list() {
            if (this.tab === 'region') return this.regions;
            if (this.tab === 'province') return this.provinces;
            if (this.tab === 'city') return this.cities;
            if (this.tab === 'barangay') return this.barangays;
            return [];
        },
        set(data) {
            this.selected = { 
                region: data.region || '', 
                province: data.province || '', 
                city: data.city || '', 
                barangay: data.barangay || '' 
            };
        },
        toggle() {
            this.isOpen = !this.isOpen;
            if (this.isOpen && this.regions.length === 0) this.fetchRegions();
        },
        displayValue() {
            return [this.selected.province || this.selected.region, this.selected.city, this.selected.barangay].filter(Boolean).join(', ');
        },
        async fetchRegions() {
            this.loading = true;
            try {
                const res = await fetch('https://psgc.gitlab.io/api/regions/');
                const data = await res.json();
                this.regions = data.sort((a, b) => a.name.localeCompare(b.name));
            } finally { this.loading = false; }
        },
        async select(loc) {
            if (this.tab === 'region') {
                this.selected.region = loc.name;
                this.selected.province = ''; this.selected.city = ''; this.selected.barangay = '';
                this.loading = true;
                const res = await fetch(`https://psgc.gitlab.io/api/regions/${loc.code}/provinces/`);
                let data = await res.json();
                if (data.length > 0) {
                    this.provinces = data.sort((a, b) => a.name.localeCompare(b.name));
                    this.tab = 'province';
                } else {
                    const resCity = await fetch(`https://psgc.gitlab.io/api/regions/${loc.code}/cities-municipalities/`);
                    data = await resCity.json();
                    this.cities = data.sort((a, b) => a.name.localeCompare(b.name));
                    this.tab = 'city';
                }
                this.loading = false;
            } else if (this.tab === 'province') {
                this.selected.province = loc.name;
                this.selected.city = ''; this.selected.barangay = '';
                this.loading = true;
                const res = await fetch(`https://psgc.gitlab.io/api/provinces/${loc.code}/cities-municipalities/`);
                let data = await res.json();
                this.cities = data.sort((a, b) => a.name.localeCompare(b.name));
                this.tab = 'city';
                this.loading = false;
            } else if (this.tab === 'city') {
                this.selected.city = loc.name;
                this.selected.barangay = '';
                this.loading = true;
                const res = await fetch(`https://psgc.gitlab.io/api/cities-municipalities/${loc.code}/barangays/`);
                let data = await res.json();
                this.barangays = data.sort((a, b) => a.name.localeCompare(b.name));
                this.tab = 'barangay';
                this.loading = false;
            } else if (this.tab === 'barangay') {
                this.selected.barangay = loc.name;
                this.isOpen = false;
            }
            this.$dispatch('psgc-change', this.selected);
        }
    }
}
</script>
