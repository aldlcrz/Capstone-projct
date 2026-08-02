@props(['initialLat' => 14.2952, 'initialLng' => 121.4647, 'readOnly' => false])

<div 
    x-data="locationPicker({ 
        lat: {{ $initialLat }}, 
        lng: {{ $initialLng }}, 
        readOnly: {{ $readOnly ? 'true' : 'false' }} 
    })" 
    x-init="initMap()"
    class="flex flex-col gap-4 border-2 border-gray-100 rounded-3xl p-5 bg-white shadow-xl relative overflow-hidden"
>
    @if(!$readOnly)
    <!-- Search Header -->
    <div class="flex flex-col sm:flex-row gap-3 relative z-[1001]">
        <div class="relative flex-1 group">
            <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            <input
                type="text"
                x-model="searchQuery"
                @keydown.enter.prevent="searchLocation()"
                placeholder="Search city, street, or landmark..."
                class="w-full pl-12 pr-4 py-4 bg-gray-50 text-black placeholder:text-gray-400 border-2 border-transparent outline-none focus:border-[#C0420A] focus:bg-white rounded-2xl text-xs font-bold transition-all shadow-inner"
            />
        </div>
        <button
            type="button"
            @click="searchLocation()"
            :disabled="searching"
            class="bg-[#C0420A] text-white px-8 rounded-2xl text-[10px] uppercase tracking-[0.2em] font-extrabold hover:bg-[#b03b25] transition-all flex items-center justify-center min-h-[56px] gap-3 shrink-0 shadow-lg disabled:opacity-50"
        >
            <template x-if="searching">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </template>
            <template x-if="!searching">
                <div class="flex items-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    <span>Pin Address</span>
                </div>
            </template>
        </button>
    </div>
    @endif

    <!-- Map Area -->
    <div 
        x-ref="mapContainer"
        class="h-72 w-full rounded-2xl overflow-hidden shadow-2xl border-2 border-gray-100 relative z-10"
    ></div>

    @if(!$readOnly)
    <!-- Status Bar -->
    <div class="pt-2">
        <template x-if="addressDetails">
            <div class="bg-[#F8FAF9] border-2 border-green-100 text-green-800 p-4 rounded-2xl flex items-center justify-between gap-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                    </div>
                    <span class="text-[11px] font-bold leading-relaxed" x-text="addressDetails"></span>
                </div>
                <div class="flex flex-col items-end gap-1 shrink-0">
                    <span class="text-[8px] font-extrabold uppercase tracking-widest text-green-600 bg-white px-2 py-1 rounded-md border border-green-200">Locked</span>
                </div>
            </div>
        </template>
        <template x-if="!addressDetails">
            <div class="p-4 rounded-2xl border-2 border-dashed border-gray-100 flex items-center justify-center text-gray-400">
                <span class="text-[10px] font-bold uppercase tracking-[0.2em]">Drop a pin to capture location</span>
            </div>
        </template>
        <div class="text-[9px] text-gray-400 font-bold uppercase tracking-[0.3em] text-center mt-4 opacity-40">
            Drag the pin or tap anywhere on the map to set coordinates
        </div>
    </div>
    @endif
</div>

@once
<script>
function locationPicker(config) {
    return {
        map: null,
        marker: null,
        lat: config.lat,
        lng: config.lng,
        readOnly: config.readOnly,
        searchQuery: '',
        searching: false,
        addressDetails: '',
        initMap() {
            this.$nextTick(() => {
                if (this.map) return;
                
                this.map = L.map(this.$refs.mapContainer).setView([this.lat, this.lng], 13);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(this.map);

                const icon = L.icon({
                    iconUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon.png',
                    iconRetinaUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    shadowUrl: 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/images/marker-shadow.png',
                    iconSize: [25, 41],
                    iconAnchor: [12, 41],
                });

                this.marker = L.marker([this.lat, this.lng], {
                    draggable: !this.readOnly,
                    icon: icon
                }).addTo(this.map);

                if (!this.readOnly) {
                    this.map.on('click', (e) => {
                        this.updateLocation(e.latlng.lat, e.latlng.lng);
                    });

                    this.marker.on('dragend', (e) => {
                        const pos = e.target.getLatLng();
                        this.updateLocation(pos.lat, pos.lng);
                    });
                }

                this.reverseGeocode(this.lat, this.lng);
                
                // Force layout update after slight delay to fix grey box issue in modals
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 500);
            });
        },
        updateLocation(lat, lng) {
            this.lat = lat;
            this.lng = lng;
            this.marker.setLatLng([lat, lng]);
            this.map.panTo([lat, lng]);
            this.reverseGeocode(lat, lng);
            this.$dispatch('location-selected', { lat, lng });
        },
        async reverseGeocode(lat, lon) {
            if (this.readOnly) return;
            this.searching = true;
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&addressdetails=1&accept-language=en-US`);
                const data = await res.json();
                if (data) {
                    this.addressDetails = data.display_name;
                    const address = data.address;
                    this.$dispatch('address-found', {
                        lat, lng: lon,
                        address: {
                            houseNumber: address.house_number || '',
                            street: address.road || address.pedestrian || '',
                            barangay: address.suburb || address.village || '',
                            city: address.city || address.town || address.municipality || '',
                            province: address.state || '',
                            postalCode: address.postcode || ''
                        }
                    });
                }
            } catch (e) { console.error(e); }
            this.searching = false;
        },
        async searchLocation() {
            if (!this.searchQuery.trim()) return;
            this.searching = true;
            try {
                const query = encodeURIComponent(this.searchQuery + ", Philippines");
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`);
                const data = await res.json();
                if (data && data.length > 0) {
                    const loc = data[0];
                    this.updateLocation(parseFloat(loc.lat), parseFloat(loc.lon));
                } else {
                    alert("Location not found.");
                }
            } catch (e) { console.error(e); }
            this.searching = false;
        }
    }
}
</script>
@endonce
