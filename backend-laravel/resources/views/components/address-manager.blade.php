<div x-data="addressManager()" x-init="init()" class="space-y-8">
    <!-- List View -->
    <template x-if="view === 'list'">
        <div class="space-y-8">
            <div class="flex flex-col sm:flex-row gap-4 items-center justify-between">
                <div class="relative w-full sm:w-80 group">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <input type="text" x-model="searchQuery" placeholder="Search address..." class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-100 rounded-lg text-sm focus:bg-white focus:border-gray-300 outline-none transition-all font-medium">
                </div>
                <button @click="handleAddNew" class="w-full sm:w-auto flex items-center justify-center gap-2 bg-black text-white px-8 py-3 rounded-lg text-[10px] font-bold uppercase tracking-widest shadow-lg hover:bg-[#C0420A] transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add New Address
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <template x-if="loading && addresses.length === 0">
                    <div class="py-20 text-center space-y-4">
                        <svg class="w-10 h-10 animate-spin text-gray-200 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <p class="text-sm text-gray-400 font-serif italic tracking-wide">Loading your address book...</p>
                    </div>
                </template>
                
                <template x-for="addr in filteredAddresses" :key="addr.id">
                    <div class="group relative bg-white border rounded-xl p-6 transition-all hover:border-gray-300" :class="addr.isDefault ? 'border-[#C0420A] shadow-sm' : 'border-gray-100'">
                        <div class="flex flex-col md:flex-row justify-between gap-6">
                            <div class="flex gap-6">
                                <div class="hidden sm:flex w-24 h-24 bg-gray-50 rounded-xl items-center justify-center shrink-0 border border-gray-100">
                                    <svg class="w-8 h-8 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                </div>
                                <div class="space-y-2">
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm font-bold text-black" x-text="addr.recipientName"></span>
                                        <div class="h-4 w-px bg-gray-200"></div>
                                        <span class="text-sm text-gray-500" x-text="addr.phone"></span>
                                    </div>
                                    <p class="text-sm text-gray-500 leading-relaxed max-w-xl" x-text="`${addr.houseNo} ${addr.street}, ${addr.barangay}, ${addr.city}, ${addr.province}, ${addr.region} ${addr.postalCode}`"></p>
                                    <div class="flex items-center gap-2 pt-1">
                                        <template x-if="addr.isDefault">
                                            <span class="px-2 py-0.5 border border-[#C0420A] text-[9px] font-bold text-[#C0420A] uppercase tracking-widest rounded">Default</span>
                                        </template>
                                        <template x-if="addr.label">
                                            <span class="px-2 py-0.5 border border-gray-200 text-[9px] font-bold text-gray-400 uppercase tracking-widest rounded" x-text="addr.label"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="flex md:flex-col items-center md:items-end justify-between md:justify-start gap-4 shrink-0">
                                <div class="flex gap-4">
                                    <button @click="handleEdit(addr)" class="text-sm font-bold text-black hover:text-[#C0420A] transition-all">Edit</button>
                                    <button @click="handleDelete(addr.id)" class="text-sm font-bold text-gray-400 hover:text-red-500 transition-all">Delete</button>
                                </div>
                                <template x-if="!addr.isDefault">
                                    <button @click="setDefault(addr.id)" class="text-[10px] font-bold text-[#C0420A] uppercase tracking-widest hover:underline">Set as default</button>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="filteredAddresses.length === 0 && !loading">
                    <div class="py-20 text-center border-2 border-dashed border-gray-100 rounded-xl">
                        <svg class="w-12 h-12 text-gray-100 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <h3 class="text-black font-bold">No addresses found</h3>
                        <p class="text-sm text-gray-400 mt-1">Start by adding a new delivery registry node.</p>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- Form View -->
    <template x-if="view === 'form'">
        <div class="space-y-8 max-w-2xl">
            <div class="flex items-center gap-4 border-b border-gray-100 pb-6">
                <button @click="view = 'list'" class="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-400 hover:text-[#C0420A]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </button>
                <h2 class="text-xl font-bold text-black" x-text="editingAddress.id ? 'Edit Address' : 'Add New Address'"></h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Recipient Name</label>
                    <input type="text" x-model="editingAddress.recipientName" class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] text-sm">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Phone Number</label>
                    <input type="text" x-model="editingAddress.phone" class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] text-sm">
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Region, Province, City, Barangay</label>
                <x-psgc-selector @psgc-change="updatePsgc($event.detail)" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Postal Code</label>
                    <input type="text" x-model="editingAddress.postalCode" class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] text-sm">
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Label (Home/Work)</label>
                    <select x-model="editingAddress.label" class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] text-sm">
                        <option value="Home">Home</option>
                        <option value="Work">Work</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">House No., Street, Building</label>
                <textarea x-model="editingAddress.houseNo" rows="3" class="w-full px-5 py-4 bg-gray-50/30 border border-gray-100 rounded-xl outline-none focus:border-[#C0420A] text-sm resize-none"></textarea>
            </div>

            <div class="flex items-center gap-3">
                <input type="checkbox" x-model="editingAddress.isDefault" id="is-default" class="w-5 h-5 accent-[#C0420A] rounded border-gray-200">
                <label for="is-default" class="text-sm font-bold text-gray-600 select-none">Set as default address</label>
            </div>

            <div class="pt-8 flex items-center justify-end gap-6">
                <button @click="view = 'list'" class="text-sm font-bold text-gray-400 hover:text-black">Cancel</button>
                <button @click="handleSave" :disabled="loading" class="px-12 py-4 bg-[#C0420A] text-white rounded-xl text-[10px] font-bold uppercase tracking-[0.2em] shadow-xl hover:bg-black transition-all flex items-center justify-center gap-3">
                    <template x-if="!loading">
                        <span>Save Address</span>
                    </template>
                    <template x-if="loading">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </template>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
function addressManager() {
    return {
        view: 'list',
        loading: false,
        addresses: [],
        searchQuery: '',
        editingAddress: {},
        get filteredAddresses() {
            if (!this.searchQuery) return this.addresses;
            const q = this.searchQuery.toLowerCase();
            return this.addresses.filter(a => 
                (a.recipientName?.toLowerCase().includes(q)) || 
                (a.city?.toLowerCase().includes(q)) || 
                (a.street?.toLowerCase().includes(q))
            );
        },
        async init() {
            this.fetchAddresses();
        },
        async fetchAddresses() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/addresses');
                this.addresses = await res.json();
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        handleAddNew() {
            this.editingAddress = {
                recipientName: '',
                phone: '',
                region: '',
                province: '',
                city: '',
                barangay: '',
                postalCode: '',
                houseNo: '',
                street: '',
                label: 'Home',
                isDefault: false
            };
            this.view = 'form';
            // Wait for DOM to update then dispatch to psgc
            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('psgc-set', { detail: this.editingAddress }));
            });
        },
        handleEdit(addr) {
            this.editingAddress = { ...addr };
            this.view = 'form';
            this.$nextTick(() => {
                window.dispatchEvent(new CustomEvent('psgc-set', { detail: this.editingAddress }));
            });
        },
        updatePsgc(data) {
            this.editingAddress.region = data.region;
            this.editingAddress.province = data.province;
            this.editingAddress.city = data.city;
            this.editingAddress.barangay = data.barangay;
        },
        async handleSave() {
            this.loading = true;
            try {
                const method = this.editingAddress.id ? 'PUT' : 'POST';
                const url = this.editingAddress.id ? `/api/v1/addresses/${this.editingAddress.id}` : '/api/v1/addresses';
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.editingAddress)
                });
                if (res.ok) {
                    await this.fetchAddresses();
                    this.view = 'list';
                }
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        async handleDelete(id) {
            if (!confirm('Are you sure?')) return;
            try {
                const res = await fetch(`/api/v1/addresses/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (res.ok) this.fetchAddresses();
            } catch (e) { console.error(e); }
        },
        async setDefault(id) {
            try {
                const res = await fetch(`/api/v1/addresses/${id}/set-default`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (res.ok) this.fetchAddresses();
            } catch (e) { console.error(e); }
        }
    }
}
</script>
