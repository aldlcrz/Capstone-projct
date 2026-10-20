@extends(request()->is('superadmin*') ? 'layouts.superadmin' : 'layouts.admin')
@section('title', 'Logistics & Shipping Matrix')

@php
    $routePrefix = request()->is('superadmin*') ? 'superadmin' : 'admin';
@endphp

@section('content')
<div class="space-y-8" x-data="{
    showAddRateModal: false,
    showAddAreaModal: false,
    showEditRateModal: false,
    showEditProviderModal: false,
    showSimulator: false,
    editRateData: {
        id: '',
        min_weight: '',
        max_weight: '',
        base_rate: '',
        additional_weight_rate: '',
        estimated_days_min: '',
        estimated_days_max: '',
        is_active: '1'
    },
    editProviderData: {
        id: '',
        name: '',
        default_volumetric_divisor: 3500,
        is_active: '1'
    },
    openEditRate(rate) {
        this.editRateData = { ...rate };
        this.showEditRateModal = true;
    },
    openEditProvider(provider) {
        this.editProviderData = {
            id: provider.id,
            name: provider.name,
            default_volumetric_divisor: provider.default_volumetric_divisor || 3500,
            is_active: provider.is_active ? '1' : '0'
        };
        this.showEditProviderModal = true;
    },
    sim: {
        dest_province: 'Metro Manila',
        dest_city: 'Manila',
        dest_postal_code: '1000',
        weight_kg: 1.0,
        length_cm: 35,
        width_cm: 25,
        height_cm: 5,
        loading: false,
        result: null,
        error: null,
        runTest() {
            this.loading = true;
            this.error = null;
            this.result = null;

            fetch('{{ route($routePrefix . \".shipping.test-rate\") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    dest_province: this.dest_province,
                    dest_city: this.dest_city,
                    dest_postal_code: this.dest_postal_code,
                    weight_kg: parseFloat(this.weight_kg),
                    length_cm: parseFloat(this.length_cm),
                    width_cm: parseFloat(this.width_cm),
                    height_cm: parseFloat(this.height_cm)
                })
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                this.result = data;
                this.loading = false;
            })
            .catch(err => {
                this.error = err.message || 'Failed to query live courier rates';
                this.loading = false;
            });
        }
    }
}">
    {{-- Top Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-gray-900 font-serif">Logistics &amp; Shipping Matrix</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-orange-100 text-[#C0420A] border border-orange-200">
                    {{ strtoupper($routePrefix) }} CONSOLE
                </span>
            </div>
            <p class="text-sm text-gray-500 mt-1">Manage database-driven shipping providers, delivery zones, area mappings, rate brackets, and real-time courier Open APIs.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" @click="showSimulator = !showSimulator" class="px-4 py-2.5 bg-amber-50 border border-amber-300 text-amber-900 text-xs font-bold uppercase rounded-xl hover:bg-amber-100 transition-all shadow-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span x-text="showSimulator ? 'Close Live Tester' : 'Live Rate Tester'">Live Rate Tester</span>
            </button>
            <button type="button" @click="showAddAreaModal = true" class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 text-xs font-bold uppercase rounded-xl hover:bg-gray-50 transition-all shadow-xs">
                + Map Geographic Area
            </button>
            <button type="button" @click="showAddRateModal = true" class="px-5 py-2.5 bg-[#C0420A] text-white text-xs font-bold uppercase rounded-xl hover:bg-[#a03608] transition-all shadow-md">
                + Add Rate Bracket
            </button>
        </div>
    </div>

    {{-- Flash Notifications --}}
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-sm font-semibold flex items-center gap-3 shadow-xs">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Interactive Live Rate Tester (Collapsible Drawer) --}}
    <div x-show="showSimulator" x-transition class="bg-gradient-to-br from-stone-900 via-zinc-900 to-neutral-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl border border-zinc-700 space-y-6">
        <div class="flex items-center justify-between border-b border-zinc-700/80 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#C0420A] flex items-center justify-center text-white shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <h2 class="text-base font-bold font-serif tracking-wide">Real-Time Courier Rate &amp; Route Simulator</h2>
                    <p class="text-xs text-zinc-400">Pings live courier endpoints (J&amp;T, Flash, LBC) from Artisan Hub (Lumban, Laguna 4014) and validates database fallback parity.</p>
                </div>
            </div>
            <button type="button" @click="showSimulator = false" class="text-zinc-400 hover:text-white text-xs uppercase font-bold px-3 py-1.5 rounded-lg border border-zinc-700 hover:bg-zinc-800">
                Hide
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div>
                <label class="block text-zinc-400 font-semibold mb-1 uppercase tracking-wider text-[11px]">Destination Province</label>
                <input type="text" x-model="sim.dest_province" class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white font-medium focus:border-[#C0420A] focus:outline-hidden" placeholder="e.g. Metro Manila">
            </div>
            <div>
                <label class="block text-zinc-400 font-semibold mb-1 uppercase tracking-wider text-[11px]">Destination City</label>
                <input type="text" x-model="sim.dest_city" class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white font-medium focus:border-[#C0420A] focus:outline-hidden" placeholder="e.g. Manila">
            </div>
            <div>
                <label class="block text-zinc-400 font-semibold mb-1 uppercase tracking-wider text-[11px]">Postal Code</label>
                <input type="text" x-model="sim.dest_postal_code" class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white font-medium focus:border-[#C0420A] focus:outline-hidden" placeholder="e.g. 1000">
            </div>
            <div>
                <label class="block text-zinc-400 font-semibold mb-1 uppercase tracking-wider text-[11px]">Weight (kg)</label>
                <input type="number" step="0.1" min="0.1" x-model="sim.weight_kg" class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white font-medium focus:border-[#C0420A] focus:outline-hidden">
            </div>
            <div>
                <label class="block text-zinc-400 font-semibold mb-1 uppercase tracking-wider text-[11px]">Length (cm)</label>
                <input type="number" step="1" min="1" x-model="sim.length_cm" class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white font-medium focus:border-[#C0420A] focus:outline-hidden">
            </div>
            <div>
                <label class="block text-zinc-400 font-semibold mb-1 uppercase tracking-wider text-[11px]">Width (cm)</label>
                <input type="number" step="1" min="1" x-model="sim.width_cm" class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white font-medium focus:border-[#C0420A] focus:outline-hidden">
            </div>
            <div>
                <label class="block text-zinc-400 font-semibold mb-1 uppercase tracking-wider text-[11px]">Height (cm)</label>
                <input type="number" step="1" min="1" x-model="sim.height_cm" class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-xl text-white font-medium focus:border-[#C0420A] focus:outline-hidden">
            </div>
            <div class="flex items-end">
                <button type="button" @click="sim.runTest()" :disabled="sim.loading" class="w-full py-2.5 bg-[#C0420A] hover:bg-[#a03608] disabled:opacity-50 text-white font-bold uppercase rounded-xl transition-all shadow-md flex items-center justify-center gap-2">
                    <svg x-show="sim.loading" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                    <span x-text="sim.loading ? 'Querying APIs...' : 'Run Live Route Test'">Run Live Route Test</span>
                </button>
            </div>
        </div>

        {{-- Simulation Results Display --}}
        <template x-if="sim.result">
            <div class="mt-4 p-5 rounded-2xl bg-zinc-800/90 border border-zinc-700 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-zinc-700 text-xs">
                    <div class="flex items-center gap-2">
                        <span class="text-zinc-400 font-semibold">Origin Zone:</span>
                        <span class="font-bold text-white bg-zinc-700 px-2.5 py-0.5 rounded-lg" x-text="sim.result.origin_zone"></span>
                        <span class="text-zinc-500">→</span>
                        <span class="text-zinc-400 font-semibold">Destination Zone:</span>
                        <span class="font-bold text-amber-400 bg-amber-950/60 border border-amber-800 px-2.5 py-0.5 rounded-lg" x-text="sim.result.destination_zone"></span>
                    </div>
                    <span class="text-zinc-400 text-[11px]">Real-Time Response Latency Verified</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <template x-for="r in sim.result.results" :key="r.code">
                        <div class="p-4 rounded-xl bg-zinc-900 border border-zinc-700/80 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-white" x-text="r.provider_name"></span>
                                <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full"
                                      :class="r.live_quote ? 'bg-emerald-950 text-emerald-400 border border-emerald-800' : 'bg-amber-950 text-amber-400 border border-amber-800'"
                                      x-text="r.live_quote ? '⚡ LIVE API' : '📋 DB MATRIX'"></span>
                            </div>
                            <div class="text-xs space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-zinc-400">Calculated Fee:</span>
                                    <span class="font-bold font-mono text-emerald-400 text-sm"
                                          x-text="r.live_quote ? '₱' + r.live_quote.fee.toFixed(2) : (r.db_rate ? '₱' + r.db_rate.fee.toFixed(2) : 'No Rate')"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-400">Transit Days:</span>
                                    <span class="font-mono text-zinc-300"
                                          x-text="r.live_quote ? (r.live_quote.min_days + '–' + r.live_quote.max_days + ' days') : (r.db_rate ? r.db_rate.days : '—')"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-zinc-400">API Latency:</span>
                                    <span class="font-mono text-zinc-400 text-[11px]" x-text="r.latency_ms + ' ms'"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </template>
        <template x-if="sim.error">
            <div class="p-3 bg-red-950/60 border border-red-800 text-red-300 rounded-xl text-xs font-semibold" x-text="sim.error"></div>
        </template>
    </div>

    {{-- Section 1: Registered Logistics Providers --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-5">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400">
                    Registered Logistics Providers
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Real-time open courier connectivity and database rate configuration</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach($providers as $provider)
                @php
                    $apiInfo = $courierApiStatus[$provider->code] ?? null;
                    $isLive = $apiInfo && $apiInfo['has_credentials'];
                @endphp
                <div class="border rounded-2xl p-5 {{ $provider->is_active ? 'border-gray-200 bg-stone-50/50' : 'border-red-200 bg-red-50/30 opacity-75' }} flex flex-col justify-between transition-all hover:shadow-xs">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-[11px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full {{ $provider->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $provider->is_active ? 'Active' : 'Disabled' }}
                            </span>
                            <span class="text-xs text-gray-400 font-mono font-bold uppercase">{{ $provider->code }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 font-serif">{{ $provider->name }}</h3>

                        {{-- Real-Time API Status Badge --}}
                        <div class="mt-2.5 mb-3">
                            @if($isLive)
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] font-bold">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>⚡ Live Open API Active</span>
                                </div>
                            @else
                                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-[11px] font-semibold">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <span>📋 Matrix Fallback Mode</span>
                                </div>
                            @endif
                        </div>

                        <div class="space-y-1.5 text-xs text-gray-600 bg-white p-3 rounded-xl border border-gray-100">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Volumetric Divisor:</span>
                                <span class="font-bold font-mono text-gray-800">{{ number_format($provider->default_volumetric_divisor ?: 3500) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Total Rate Brackets:</span>
                                <span class="font-bold font-mono text-[#C0420A]">{{ $provider->rates_count }} brackets</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Tracking Method:</span>
                                <span class="font-semibold text-gray-700">Open API &amp; Webhooks</span>
                            </div>
                            @if($apiInfo && $apiInfo['customer_code_mask'])
                                <div class="flex justify-between pt-1 border-t border-gray-100 text-[11px]">
                                    <span class="text-gray-400">Account Code:</span>
                                    <span class="font-mono text-gray-500">{{ $apiInfo['customer_code_mask'] }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
                        <button type="button" @click="openEditProvider({{ json_encode($provider) }})" class="text-xs font-bold uppercase tracking-wider text-gray-600 hover:text-gray-900 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            <span>Configure</span>
                        </button>
                        <form action="{{ route($routePrefix . '.shipping.providers.toggle', $provider->id) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs font-bold uppercase tracking-wider {{ $provider->is_active ? 'text-amber-700 hover:text-amber-900' : 'text-emerald-700 hover:text-emerald-900' }}">
                                {{ $provider->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 2: Shipping Zones & Area Coverage --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400">
                    Logistics Zones Overview
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Geographic clustering across the Philippine archipelago</p>
            </div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
            @foreach($zones as $zone)
                <div class="p-4 rounded-xl border border-gray-200 bg-white text-center hover:border-orange-300 transition-colors">
                    <div class="text-xs font-black text-[#C0420A] tracking-wider">{{ $zone->code }}</div>
                    <div class="text-sm font-bold text-gray-900 mt-1 truncate">{{ $zone->name }}</div>
                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $zone->areas_count }} mapped areas</div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-bold text-gray-900">Geographic Area Mappings</h3>
            <span class="text-xs text-gray-400">Showing page {{ $zoneAreas->currentPage() }} of {{ $zoneAreas->lastPage() }}</span>
        </div>
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="py-3 px-4">Zone</th>
                        <th class="py-3 px-4">Province</th>
                        <th class="py-3 px-4">City</th>
                        <th class="py-3 px-4">Barangay</th>
                        <th class="py-3 px-4">Postal Code</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($zoneAreas as $area)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-bold text-[#C0420A]">{{ $area->zone->code ?? 'N/A' }}</td>
                            <td class="py-3 px-4 font-semibold text-gray-900">{{ $area->province }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $area->city ?? 'All Cities' }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $area->barangay ?? 'All Barangays' }}</td>
                            <td class="py-3 px-4 font-mono text-gray-500">{{ $area->postal_code ?? ($area->postal_code_prefix ? $area->postal_code_prefix.'*' : '—') }}</td>
                            <td class="py-3 px-4 text-right">
                                <form action="{{ route($routePrefix . '.shipping.areas.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Remove area mapping?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-bold uppercase text-[10px]">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-gray-400 italic">No geographic areas configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $zoneAreas->appends(['rates_page' => $rates->currentPage()])->links() }}
        </div>
    </div>

    {{-- Section 3: Rate Brackets Matrix with Filter Bar --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 mb-4 gap-3">
            <div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400">
                    Rate Matrix Brackets (Fallback Tier)
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">Authoritative fallback pricing applied when live courier APIs are offline</p>
            </div>
            <span class="text-xs text-gray-400">Showing page {{ $rates->currentPage() }} of {{ $rates->lastPage() }}</span>
        </div>

        {{-- Filter Bar --}}
        <form method="GET" action="{{ route($routePrefix . '.shipping.index') }}" class="mb-5 p-3.5 rounded-xl bg-stone-50 border border-gray-200 flex flex-wrap items-center gap-3 text-xs">
            <div class="flex items-center gap-2">
                <label class="font-bold text-gray-600 uppercase text-[11px]">Courier:</label>
                <select name="filter_provider_id" class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white font-medium text-xs">
                    <option value="">All Couriers</option>
                    @foreach($providers as $p)
                        <option value="{{ $p->id }}" {{ request('filter_provider_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label class="font-bold text-gray-600 uppercase text-[11px]">Zone:</label>
                <select name="filter_zone_id" class="px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white font-medium text-xs">
                    <option value="">All Zones</option>
                    @foreach($zones as $z)
                        <option value="{{ $z->id }}" {{ request('filter_zone_id') == $z->id ? 'selected' : '' }}>{{ $z->code }} ({{ $z->name }})</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-3.5 py-1.5 rounded-lg bg-gray-900 text-white font-bold uppercase text-[11px] hover:bg-gray-800">
                Filter
            </button>
            @if(request()->hasAny(['filter_provider_id', 'filter_zone_id']))
                <a href="{{ route($routePrefix . '.shipping.index') }}" class="text-gray-500 hover:text-gray-700 font-bold uppercase text-[11px]">
                    Reset
                </a>
            @endif
        </form>

        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-500 uppercase tracking-wider font-bold">
                    <tr>
                        <th class="py-3 px-4">Courier</th>
                        <th class="py-3 px-4">Origin → Destination</th>
                        <th class="py-3 px-4">Weight Bracket (kg)</th>
                        <th class="py-3 px-4">Base Rate</th>
                        <th class="py-3 px-4">Add'l / kg</th>
                        <th class="py-3 px-4">Transit Time</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($rates as $rate)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 font-bold text-gray-900">{{ $rate->provider->name ?? $rate->provider_id }}</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-md bg-stone-100 text-stone-800 font-mono font-bold">{{ $rate->originZone->code ?? 'N/A' }}</span>
                                <span class="text-gray-400 mx-1">→</span>
                                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-mono font-bold">{{ $rate->destinationZone->code ?? 'N/A' }}</span>
                            </td>
                            <td class="py-3 px-4 font-mono font-bold text-gray-700">
                                {{ number_format($rate->min_weight, 2) }} – {{ $rate->max_weight !== null ? number_format($rate->max_weight, 2) : '∞' }}
                            </td>
                            <td class="py-3 px-4 font-bold text-gray-900">₱{{ number_format($rate->base_rate, 2) }}</td>
                            <td class="py-3 px-4 font-mono text-gray-600">₱{{ number_format($rate->additional_weight_rate, 2) }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $rate->estimated_days_min }}–{{ $rate->estimated_days_max }} days</td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ $rate->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-100 text-stone-600' }}">
                                    {{ $rate->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right space-x-2">
                                <button type="button" @click="openEditRate({{ json_encode($rate) }})" class="text-[#C0420A] hover:text-[#a03608] font-bold uppercase text-[10px]">
                                    Edit
                                </button>
                                <form action="{{ route($routePrefix . '.shipping.rates.destroy', $rate->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this rate bracket?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-bold uppercase text-[10px]">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-gray-400 italic">No rate brackets matched the filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $rates->links() }}
        </div>
    </div>

    {{-- MODAL 1: Add Rate Bracket --}}
    <div x-show="showAddRateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" style="display:none;" x-cloak>
        <div @click.away="showAddRateModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-200">
            <h3 class="font-serif text-xl font-bold text-gray-900 mb-4">Add Rate Bracket</h3>
            <form action="{{ route($routePrefix . '.shipping.rates.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Provider</label>
                    <select name="provider_id" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                        @foreach($providers as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Origin Zone</label>
                        <select name="origin_zone_id" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                            @foreach($zones as $z)
                                <option value="{{ $z->id }}">{{ $z->code }} ({{ $z->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Destination Zone</label>
                        <select name="destination_zone_id" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                            @foreach($zones as $z)
                                <option value="{{ $z->id }}">{{ $z->code }} ({{ $z->name }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Min Weight (kg)</label>
                        <input type="number" step="0.01" min="0" name="min_weight" required class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Max Weight (kg, optional)</label>
                        <input type="number" step="0.01" min="0" name="max_weight" class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="Leave blank for open-ended">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Base Rate (₱)</label>
                        <input type="number" step="0.01" min="0" name="base_rate" required class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="0.00">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Additional / kg (₱)</label>
                        <input type="number" step="0.01" min="0" name="additional_weight_rate" required class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="0.00">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Est Days Min</label>
                        <input type="number" min="1" name="estimated_days_min" required class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="1">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Est Days Max</label>
                        <input type="number" min="1" name="estimated_days_max" required class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="3">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Status</label>
                    <select name="is_active" class="w-full px-3 py-2 rounded-xl border border-gray-300">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAddRateModal = false" class="px-4 py-2 rounded-xl border text-gray-600 font-bold uppercase">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#C0420A] text-white font-bold uppercase">Save Bracket</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: Add Area Mapping --}}
    <div x-show="showAddAreaModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" style="display:none;" x-cloak>
        <div @click.away="showAddAreaModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-200">
            <h3 class="font-serif text-xl font-bold text-gray-900 mb-4">Map Geographic Area</h3>
            <form action="{{ route($routePrefix . '.shipping.areas.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Assign to Zone</label>
                    <select name="zone_id" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                        @foreach($zones as $z)
                            <option value="{{ $z->id }}">{{ $z->code }} ({{ $z->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Province</label>
                    <input type="text" name="province" required class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="e.g. Laguna">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">City / Municipality (Optional)</label>
                    <input type="text" name="city" class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="Leave blank for entire province">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Barangay (Optional)</label>
                    <input type="text" name="barangay" class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="Leave blank for entire city">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Postal Code (Exact)</label>
                        <input type="text" name="postal_code" class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="e.g. 4011">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Postal Prefix</label>
                        <input type="text" name="postal_code_prefix" class="w-full px-3 py-2 rounded-xl border border-gray-300" placeholder="e.g. 40">
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showAddAreaModal = false" class="px-4 py-2 rounded-xl border text-gray-600 font-bold uppercase">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#C0420A] text-white font-bold uppercase">Save Area Mapping</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 3: Edit Rate Bracket --}}
    <div x-show="showEditRateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" style="display:none;" x-cloak>
        <div @click.away="showEditRateModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-200">
            <h3 class="font-serif text-xl font-bold text-gray-900 mb-4">Edit Rate Bracket</h3>
            <form :action="'/' + '{{ $routePrefix }}' + '/shipping/rates/' + editRateData.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Min Weight (kg)</label>
                        <input type="number" step="0.01" min="0" name="min_weight" x-model="editRateData.min_weight" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Max Weight (kg)</label>
                        <input type="number" step="0.01" min="0" name="max_weight" x-model="editRateData.max_weight" class="w-full px-3 py-2 rounded-xl border border-gray-300">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Base Rate (₱)</label>
                        <input type="number" step="0.01" min="0" name="base_rate" x-model="editRateData.base_rate" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Additional / kg (₱)</label>
                        <input type="number" step="0.01" min="0" name="additional_weight_rate" x-model="editRateData.additional_weight_rate" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Est Days Min</label>
                        <input type="number" min="1" name="estimated_days_min" x-model="editRateData.estimated_days_min" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                    </div>
                    <div>
                        <label class="block font-bold text-gray-700 uppercase mb-1">Est Days Max</label>
                        <input type="number" min="1" name="estimated_days_max" x-model="editRateData.estimated_days_max" required class="w-full px-3 py-2 rounded-xl border border-gray-300">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Status</label>
                    <select name="is_active" x-model="editRateData.is_active" class="w-full px-3 py-2 rounded-xl border border-gray-300">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showEditRateModal = false" class="px-4 py-2 rounded-xl border text-gray-600 font-bold uppercase">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#C0420A] text-white font-bold uppercase">Update Bracket</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 4: Edit Provider Modal --}}
    <div x-show="showEditProviderModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" style="display:none;" x-cloak>
        <div @click.away="showEditProviderModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-200">
            <h3 class="font-serif text-xl font-bold text-gray-900 mb-2">Configure Logistics Provider</h3>
            <p class="text-xs text-gray-500 mb-5">Adjust courier provider metadata and volumetric divisor rules.</p>
            <form :action="'/' + '{{ $routePrefix }}' + '/shipping/providers/' + editProviderData.id" method="POST" class="space-y-4 text-xs">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Provider Name</label>
                    <input type="text" name="name" x-model="editProviderData.name" required class="w-full px-3 py-2 rounded-xl border border-gray-300 font-medium">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Default Volumetric Divisor</label>
                    <input type="number" name="default_volumetric_divisor" x-model="editProviderData.default_volumetric_divisor" required min="1" class="w-full px-3 py-2 rounded-xl border border-gray-300 font-mono">
                    <p class="text-[11px] text-gray-400 mt-1">Standard courier divisor in the Philippines is 3500 (L × W × H in cm / 3500 = Volumetric kg).</p>
                </div>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1">Status</label>
                    <select name="is_active" x-model="editProviderData.is_active" class="w-full px-3 py-2 rounded-xl border border-gray-300">
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t border-gray-100">
                    <button type="button" @click="showEditProviderModal = false" class="px-4 py-2 rounded-xl border text-gray-600 font-bold uppercase">Cancel</button>
                    <button type="submit" class="px-5 py-2 rounded-xl bg-[#C0420A] text-white font-bold uppercase">Save Provider</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
