@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{ 
    showAddModal: false, 
    showEditModal: false, 
    addPreview: null,
    editPreview: null,
    addName: '{{ old('name', '') }}',
    addTags: {{ json_encode(old('target_group', [])) }},
    addSubmitted: false,
    originalEditName: '',
    editSubmitted: false,
    editingCategory: { id: '', name: '', description: '', target_group: [], image: '' },
    existingNames: {{ json_encode($categories->pluck('name')->map(fn($n) => strtolower(trim($n)))) }},
    
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
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-black">Product <span class="text-[#C0420A] font-light italic">Categories</span></h1>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <form action="{{ route('admin.categories.initialize') }}" method="POST">
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

    <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left border-collapse min-w-137.5">
            <thead>
                <tr class="bg-gray-50/50">
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Image</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Name</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Description</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Target</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-center">Products</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50/50 transition-colors group">
                    <td class="px-6 py-4">
                        <div class="w-12 h-12 rounded-full overflow-hidden bg-gray-100 border border-gray-200 shadow-xs shrink-0">
                            <img src="{{ $category->getImageUrl() }}" 
                                 onerror="this.src='/uploads/categories/pina_formal.png'" 
                                 class="w-full h-full object-cover" 
                                 alt="{{ $category->name }}">
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-black">{{ $category->name }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-[11px] text-gray-500 max-w-md truncate">{{ $category->description ?: 'No description' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @if(is_array($category->target_group) && count($category->target_group) > 0)
                                @foreach($category->target_group as $group)
                                    <span class="px-2 py-0.5 bg-{{ $group == 'Men' ? 'blue' : ($group == 'Women' ? 'pink' : ($group == 'Kids' ? 'green' : 'gray')) }}-50 text-{{ $group == 'Men' ? 'blue' : ($group == 'Women' ? 'pink' : ($group == 'Kids' ? 'green' : 'gray')) }}-600 rounded text-[8px] font-black uppercase tracking-widest">
                                        {{ $group }}
                                    </span>
                                @endforeach
                            @else
                                <span class="px-2 py-0.5 bg-gray-50 text-gray-400 rounded text-[8px] font-black uppercase tracking-widest">
                                    Universal
                                </span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-[10px] font-bold">
                            {{ $category->products_count }} Products
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button
                                @click="editingCategory = JSON.parse($el.dataset.category); originalEditName = editingCategory.name; editPreview = null; editSubmitted = false; showEditModal = true"
                                data-category="{{ json_encode(['id' => $category->id, 'name' => $category->name, 'description' => $category->description, 'target_group' => $category->target_group ?? [], 'image' => $category->getImageUrl()]) }}"
                                class="p-2 text-gray-400 hover:text-black transition-colors cursor-pointer" title="Edit Category">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors cursor-pointer" title="Delete Category">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="text-gray-400 text-sm italic">No categories found.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <!-- Add Modal -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-lg p-8 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="showAddModal = false">
            <h2 class="font-serif text-2xl font-bold mb-6">Add New <span class="text-[#C0422A] italic">Category</span></h2>
            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" @submit="validateAddForm($event)" class="space-y-4">
                @csrf
                
                {{-- Category Image Upload (Required) --}}
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">
                        Category Image <span class="text-[#C0422A]">* (Required)</span>
                    </label>
                    <div class="flex items-center gap-4 p-4 border border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 shrink-0 border border-gray-300 flex items-center justify-center">
                            <template x-if="addPreview">
                                <img :src="addPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!addPreview">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </template>
                        </div>
                        <div class="grow">
                            <input type="file" name="image" required accept="image/*" @change="previewAddImage($event)"
                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer cursor-pointer">
                            <p class="text-[10px] text-gray-400 mt-1">PNG, JPG, WEBP up to 5MB (Square/Circle recommended)</p>
                        </div>
                    </div>
                </div>

                {{-- Category Name (Unique & Required) --}}
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Category Name <span class="text-[#C0422A]">*</span></label>
                    <input type="text" name="name" x-model="addName" required placeholder="e.g. Wedding Barong" 
                           :class="isDuplicateAddName() ? 'border-red-500 ring-2 ring-red-500/20 bg-red-50/30' : 'border-gray-100 bg-gray-50'"
                           class="w-full px-4 py-3 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    
                    {{-- Duplicate Name Real-Time Warning --}}
                    <div x-show="isDuplicateAddName()" class="flex items-center gap-1.5 text-xs font-bold text-red-500 mt-1.5" x-cloak>
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>A category with this name already exists. Duplicate names are not allowed.</span>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Description</label>
                    <textarea name="description" rows="3" placeholder="Brief details about this category..." class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all"></textarea>
                </div>

                {{-- Select Tags (Required - at least 1) --}}
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">
                        Select Tags <span class="text-[#C0422A]">* (Required)</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            <label class="cursor-pointer group">
                                <input type="checkbox" name="target_group[]" value="{{ $group }}" x-model="addTags" class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-100 bg-gray-50/50 text-[10px] font-black text-gray-400 peer-checked:bg-[#C0422A] peer-checked:text-white peer-checked:border-[#C0422A] transition-all uppercase tracking-widest">
                                    {{ $group }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    {{-- Missing Tag Validation Warning --}}
                    <div x-show="addSubmitted && addTags.length === 0" class="flex items-center gap-1.5 text-xs font-bold text-red-500 mt-1.5" x-cloak>
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Please select at least one tag (Men, Women, or Kids).</span>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showAddModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" 
                            :disabled="isDuplicateAddName()"
                            :class="isDuplicateAddName() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#C0422A] cursor-pointer'"
                            class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-lg p-8 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="showEditModal = false">
            <h2 class="font-serif text-2xl font-bold mb-6">Edit <span class="text-[#C0422A] italic">Category</span></h2>
            <form :action="'/admin/categories/' + editingCategory.id" method="POST" enctype="multipart/form-data" @submit="validateEditForm($event)" class="space-y-4">
                @csrf
                @method('PUT')

                {{-- Category Image Upload (Optional on edit) --}}
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Category Image</label>
                    <div class="flex items-center gap-4 p-4 border border-dashed border-gray-200 rounded-2xl bg-gray-50/50">
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-200 shrink-0 border border-gray-300 flex items-center justify-center">
                            <template x-if="editPreview">
                                <img :src="editPreview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!editPreview && editingCategory.image">
                                <img :src="editingCategory.image" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!editPreview && !editingCategory.image">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </template>
                        </div>
                        <div class="grow">
                            <input type="file" name="image" accept="image/*" @change="previewEditImage($event)"
                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-bold file:uppercase file:tracking-widest file:bg-[#3D2B1F] file:text-white hover:file:bg-[#C0422A] file:cursor-pointer cursor-pointer">
                            <p class="text-[10px] text-gray-400 mt-1">Leave empty to keep existing image</p>
                        </div>
                    </div>
                </div>

                {{-- Category Name (Unique & Required) --}}
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Category Name <span class="text-[#C0422A]">*</span></label>
                    <input type="text" name="name" x-model="editingCategory.name" required 
                           :class="isDuplicateEditName() ? 'border-red-500 ring-2 ring-red-500/20 bg-red-50/30' : 'border-gray-100 bg-gray-50'"
                           class="w-full px-4 py-3 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                    
                    {{-- Duplicate Name Real-Time Warning --}}
                    <div x-show="isDuplicateEditName()" class="flex items-center gap-1.5 text-xs font-bold text-red-500 mt-1.5" x-cloak>
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>A category with this name already exists. Duplicate names are not allowed.</span>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Description</label>
                    <textarea name="description" x-model="editingCategory.description" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all"></textarea>
                </div>

                {{-- Select Tags (Required - at least 1) --}}
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">
                        Select Tags <span class="text-[#C0422A]">* (Required)</span>
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            <label class="cursor-pointer group">
                                <input type="checkbox" name="target_group[]" value="{{ $group }}" 
                                    x-model="editingCategory.target_group"
                                    class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-100 bg-gray-50/50 text-[10px] font-black text-gray-400 peer-checked:bg-[#C0422A] peer-checked:text-white peer-checked:border-[#C0422A] transition-all uppercase tracking-widest">
                                    {{ $group }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    {{-- Missing Tag Validation Warning --}}
                    <div x-show="editSubmitted && (!editingCategory.target_group || editingCategory.target_group.length === 0)" class="flex items-center gap-1.5 text-xs font-bold text-red-500 mt-1.5" x-cloak>
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Please select at least one tag (Men, Women, or Kids).</span>
                    </div>
                </div>

                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showEditModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" 
                            :disabled="isDuplicateEditName()"
                            :class="isDuplicateEditName() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-[#C0422A] cursor-pointer'"
                            class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
