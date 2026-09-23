@extends('layouts.admin')
@section('title', 'Logistics & Shipping Matrix')

@section('content')
<div class="space-y-8" x-data="{
    showAddRateModal: false,
    showAddAreaModal: false,
    showEditRateModal: false,
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
    openEditRate(rate) {
        this.editRateData = { ...rate };
        this.showEditRateModal = true;
    }
}">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 font-serif">Logistics &amp; Shipping Matrix</h1>
            <p class="text-sm text-gray-500 mt-1">Manage database-driven shipping providers, delivery zones, area mappings, and rate brackets.</p>
        </div>
        <div class="flex items-center gap-3">
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
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Section 1: Couriers / Providers --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400 pb-3 border-b border-gray-100 mb-4">
            Registered Logistics Providers
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach($providers as $provider)
                <div class="border rounded-2xl p-5 {{ $provider->is_active ? 'border-gray-200 bg-stone-50/50' : 'border-red-200 bg-red-50/30 opacity-75' }} flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded-full {{ $provider->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                {{ $provider->is_active ? 'Active' : 'Disabled' }}
                            </span>
                            <span class="text-xs text-gray-500 font-mono">{{ $provider->id }}</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 font-serif">{{ $provider->name }}</h3>
                        <div class="space-y-1.5 mt-3 text-xs text-gray-600">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Volumetric Divisor:</span>
                                <span class="font-bold font-mono">{{ $provider->volumetric_divisor }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">Total Rate Brackets:</span>
                                <span class="font-bold font-mono">{{ $provider->rates_count }}</span>
                            </div>
                            <div class="pt-2 text-[11px] text-gray-400 truncate">
                                {{ $provider->tracking_url_template ? 'Tracking integrated' : 'No tracking template' }}
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <form action="{{ route('admin.shipping.providers.toggle', $provider->id) }}" method="POST">
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
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400">
                Logistics Zones Overview
            </h2>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-6">
            @foreach($zones as $zone)
                <div class="p-4 rounded-xl border border-gray-200 bg-white text-center">
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
                                <form action="{{ route('admin.shipping.areas.destroy', $area->id) }}" method="POST" onsubmit="return confirm('Remove area mapping?');">
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

    {{-- Section 3: Rate Brackets Matrix --}}
    <div class="bg-white rounded-2xl border border-gray-200 p-6 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400">
                Rate Matrix Brackets
            </h2>
            <span class="text-xs text-gray-400">Showing page {{ $rates->currentPage() }} of {{ $rates->lastPage() }}</span>
        </div>

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
                                <form action="{{ route('admin.shipping.rates.destroy', $rate->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this rate bracket?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-bold uppercase text-[10px]">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-gray-400 italic">No rate brackets configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $rates->appends(['areas_page' => $zoneAreas->currentPage()])->links() }}
        </div>
    </div>

    {{-- MODAL 1: Add Rate Bracket --}}
    <div x-show="showAddRateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs" style="display:none;" x-cloak>
        <div @click.away="showAddRateModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-200">
            <h3 class="font-serif text-xl font-bold text-gray-900 mb-4">Add Rate Bracket</h3>
            <form action="{{ route('admin.shipping.rates.store') }}" method="POST" class="space-y-4 text-xs">
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
            <form action="{{ route('admin.shipping.areas.store') }}" method="POST" class="space-y-4 text-xs">
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
            <form :action="'/admin/shipping/rates/' + editRateData.id" method="POST" class="space-y-4 text-xs">
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
</div>
@endsection
