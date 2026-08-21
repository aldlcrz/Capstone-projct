@extends('layouts.superadmin')

@section('content')
<div class="space-y-8" x-data="{ 
    showAddModal: false, 
    showEditModal: false, 
    showDeleteModal: false,
    deletingCategory: { id: '', name: '', products_count: 0 },
    addPreview: null,
    editPreview: null,
    addName: '{{ old('name', '') }}',
    addTags: {{ json_encode(old('target_group', [])) }},
    addSubmitted: false,
    originalEditName: '',
    editSubmitted: false,
    editingCategory: { id: '', name: '', description: '', target_group: [], image: '' },
    existingNames: {{ json_encode($categories->pluck('name')->map(fn($n) => strtolower(trim($n)))) }},
    
    openDeleteModal(category) {
        this.deletingCategory = category;
        this.showDeleteModal = true;
    },
    
    previewAddImage(e) {
        const file = e.target.files[0];
        if (file) {
            this.addPreview = URL.createObjectURL(file);
        }
    },
    previewEditImage(e) {
        const file = e.target.files[0];
        if (file) {
            this.editPreview = URL.createObjectURL(file);
        }
    },
    isDuplicateAddName() {
        const n = this.addName.trim().toLowerCase();
        return n.length > 0 && this.existingNames.includes(n);
    },
    isDuplicateEditName() {
        const n = (this.editingCategory.name || '').trim().toLowerCase();
        return n.length > 0 && this.existingNames.filter(x => x !== this.originalEditName.toLowerCase()).includes(n);
    },
    validateAddForm(e) {
        this.addSubmitted = true;
        if (this.isDuplicateAddName() || this.addTags.length === 0) {
            e.preventDefault();
            return false;
        }
    },
    validateEditForm(e) {
        this.editSubmitted = true;
        if (this.isDuplicateEditName() || !this.editingCategory.target_group || this.editingCategory.target_group.length === 0) {
            e.preventDefault();
            return false;
        }
    }
}">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Catalog Management</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#3D2B1F]">Product <span class="text-[#C0422A] font-light italic">Categories</span></h1>
            <p class="text-xs text-gray-500 mt-1">Manage marketplace product categories, target audiences, and featured taxonomy images.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <form action="{{ route('superadmin.categories.initialize') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 bg-gray-100 text-gray-700 rounded-xl text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all cursor-pointer">
                    Initialize Defaults
                </button>
            </form>
            <button @click="showAddModal = true; addPreview = null; addName = ''; addTags = []; addSubmitted = false;" class="flex items-center gap-2 px-4 sm:px-6 py-2.5 sm:py-3 bg-[#3D2B1F] text-white rounded-xl text-[9px] sm:text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Category
            </button>
        </div>
    </div>

    @if($errors->any())
    <div class="p-4 bg-red-50 border border-red-200 rounded-2xl">
        <div class="flex items-center gap-2 text-red-700 font-bold text-xs mb-1">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>Please correct the errors below:</span>
        </div>
        <ul class="list-disc list-inside text-xs text-red-600 space-y-0.5 ml-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-3xl border border-[#E5DDD5] overflow-hidden shadow-xs">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left border-collapse min-w-137.5">
            <thead>
                <tr class="bg-gray-50/70 border-b border-[#E5DDD5]">
                    <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Image</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Name</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Description</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Target</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Products</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                <tr class="hover:bg-amber-50/20 transition-colors">
                    <td class="px-6 py-4">
                        <div class="w-14 h-14 rounded-2xl overflow-hidden bg-gray-50 border border-gray-200">
                            <img src="{{ $category->getImageUrl() }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-[#3D2B1F]">{{ $category->name }}</div>
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        <p class="text-xs text-gray-500 truncate">{{ $category->description ?? 'No description provided.' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            @php
                                $groups = is_array($category->target_group) ? $category->target_group : (json_decode($category->target_group, true) ?? []);
                            @endphp
                            @forelse($groups as $group)
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold tracking-wider uppercase
                                    {{ $group === 'Men' ? 'bg-blue-50 text-blue-700 border border-blue-200' : '' }}
                                    {{ $group === 'Women' ? 'bg-pink-50 text-pink-700 border border-pink-200' : '' }}
                                    {{ $group === 'Kids' ? 'bg-amber-50 text-amber-700 border border-amber-200' : '' }}">
                                    {{ $group }}
                                </span>
                            @empty
                                <span class="text-[10px] text-gray-400 italic">None</span>
                            @endforelse
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 rounded-full text-xs font-bold text-gray-700">
                            {{ $category->products_count }} {{ Str::plural('item', $category->products_count) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="editingCategory = {
                                        id: '{{ $category->id }}',
                                        name: '{{ addslashes($category->name) }}',
                                        description: '{{ addslashes($category->description ?? '') }}',
                                        target_group: {{ json_encode($groups) }},
                                        image: '{{ $category->getImageUrl() }}'
                                    }; 
                                    originalEditName = '{{ addslashes($category->name) }}';
                                    editPreview = null; 
                                    editSubmitted = false;
                                    showEditModal = true;" 
                                    class="p-2 text-gray-500 hover:text-black hover:bg-gray-100 rounded-xl transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <button @click="openDeleteModal({
                                        id: '{{ $category->id }}',
                                        name: '{{ addslashes($category->name) }}',
                                        products_count: {{ $category->products_count }}
                                    })" 
                                    class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-xl transition-all cursor-pointer">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <div class="text-xs italic">No categories found. Click 'Initialize Defaults' or 'Add Category'.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Add Category Modal --}}
    <div x-show="showAddModal" 
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="showAddModal = false">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-serif text-xl font-bold text-black">New Product Category</h3>
                <button @click="showAddModal = false" class="p-2 text-gray-400 hover:text-black rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('superadmin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4" @submit="validateAddForm($event)">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="addName" required placeholder="e.g., Piña Formal Barong"
                           class="w-full px-4 py-3 bg-gray-50 border rounded-xl text-sm focus:outline-none focus:border-black transition-colors"
                           :class="isDuplicateAddName() ? 'border-red-400 bg-red-50/30' : 'border-gray-200'">
                    <p x-show="isDuplicateAddName()" class="text-red-600 text-xs font-semibold mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>A category with this name already exists.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Description</label>
                    <textarea name="description" rows="3" placeholder="Describe the category collection..." 
                              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-black transition-colors">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Target Group Tag <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <template x-for="tag in ['Men', 'Women', 'Kids']" :key="tag">
                            <label class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold cursor-pointer hover:bg-gray-100 transition-colors select-none">
                                <input type="checkbox" name="target_group[]" :value="tag" x-model="addTags" class="rounded text-[#C0422A] focus:ring-0">
                                <span x-text="tag"></span>
                            </label>
                        </template>
                    </div>
                    <p x-show="addSubmitted && addTags.length === 0" class="text-red-600 text-xs font-semibold mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Please select at least one tag.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Category Image <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 overflow-hidden shrink-0 flex items-center justify-center">
                            <template x-if="addPreview">
                                <img :src="addPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!addPreview">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </template>
                        </div>
                        <input type="file" name="image" required accept="image/*" @change="previewAddImage($event)"
                               class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer">
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <button type="button" @click="showAddModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors cursor-pointer">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 bg-[#3D2B1F] text-white rounded-xl text-xs font-bold hover:bg-[#C0422A] transition-colors cursor-pointer">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Category Modal --}}
    <div x-show="showEditModal" 
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-2xl relative max-h-[90vh] overflow-y-auto" @click.away="showEditModal = false">
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-serif text-xl font-bold text-black">Edit Category</h3>
                <button @click="showEditModal = false" class="p-2 text-gray-400 hover:text-black rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'/superadmin/categories/' + editingCategory.id" method="POST" enctype="multipart/form-data" class="space-y-4" @submit="validateEditForm($event)">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Category Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="editingCategory.name" required
                           class="w-full px-4 py-3 bg-gray-50 border rounded-xl text-sm focus:outline-none focus:border-black transition-colors"
                           :class="isDuplicateEditName() ? 'border-red-400 bg-red-50/30' : 'border-gray-200'">
                    <p x-show="isDuplicateEditName()" class="text-red-600 text-xs font-semibold mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>A category with this name already exists.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Description</label>
                    <textarea name="description" rows="3" x-model="editingCategory.description" 
                              class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-black transition-colors"></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Target Group Tag <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <template x-for="tag in ['Men', 'Women', 'Kids']" :key="tag">
                            <label class="flex items-center gap-2 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold cursor-pointer hover:bg-gray-100 transition-colors select-none">
                                <input type="checkbox" name="target_group[]" :value="tag" x-model="editingCategory.target_group" class="rounded text-[#C0422A] focus:ring-0">
                                <span x-text="tag"></span>
                            </label>
                        </template>
                    </div>
                    <p x-show="editSubmitted && (!editingCategory.target_group || editingCategory.target_group.length === 0)" class="text-red-600 text-xs font-semibold mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Please select at least one tag.</span>
                    </p>
                </div>

                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Change Image</label>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-gray-100 border border-gray-200 overflow-hidden shrink-0">
                            <template x-if="editPreview">
                                <img :src="editPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!editPreview">
                                <img :src="editingCategory.image" class="w-full h-full object-cover">
                            </template>
                        </div>
                        <input type="file" name="image" accept="image/*" @change="previewEditImage($event)"
                               class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer">
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-3">
                    <button type="button" @click="showEditModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-200 transition-colors cursor-pointer">Cancel</button>
                    <button type="submit" class="px-6 py-2.5 bg-[#3D2B1F] text-white rounded-xl text-xs font-bold hover:bg-[#C0422A] transition-colors cursor-pointer">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Confirmation Modal --}}
    <div x-show="showDeleteModal" 
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-md w-full shadow-2xl relative border border-gray-100" @click.away="showDeleteModal = false">
            <div class="w-14 h-14 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center mx-auto mb-5 border border-red-100">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
            </div>
            
            <h3 class="font-serif text-xl font-bold text-center text-gray-900 mb-2">Delete Category</h3>
            
            <template x-if="deletingCategory.products_count > 0">
                <div class="text-center">
                    <p class="text-xs text-red-600 font-semibold mb-4 leading-relaxed">
                        Cannot delete "<span x-text="deletingCategory.name"></span>" because it currently contains <span x-text="deletingCategory.products_count"></span> active products.
                    </p>
                    <p class="text-xs text-gray-500 mb-6">Please reassign or delete these products first.</p>
                    <button type="button" @click="showDeleteModal = false" class="w-full py-3 bg-gray-100 text-gray-700 text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-gray-200 transition-all cursor-pointer">
                        Understood
                    </button>
                </div>
            </template>

            <template x-if="deletingCategory.products_count === 0">
                <div>
                    <p class="text-xs text-gray-500 text-center mb-6 leading-relaxed">
                        Are you sure you want to delete <span class="font-bold text-gray-900" x-text="deletingCategory.name"></span>? This category will be archived.
                    </p>
                    
                    <form :action="'/superadmin/categories/' + deletingCategory.id" method="POST" class="space-y-4">
                        @csrf
                        @method('DELETE')
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-1.5">Reason for Deletion</label>
                            <input type="text" name="reason" placeholder="e.g. Obsolete category collection" required
                                   class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-black">
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <button type="button" @click="showDeleteModal = false" class="flex-1 py-3 bg-gray-100 text-gray-700 text-xs font-bold rounded-xl hover:bg-gray-200 transition-all cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="flex-1 py-3 bg-red-600 text-white text-xs font-bold rounded-xl hover:bg-red-700 transition-all cursor-pointer shadow-sm">
                                Delete &amp; Archive
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </div>
    </div>

</div>
@endsection
