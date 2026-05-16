@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{ 
    showAddModal: false, 
    showEditModal: false, 
    editingCategory: { id: '', name: '', description: '' } 
}">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Catalog Management</div>
            <h1 class="font-serif text-3xl font-bold text-black">Product <span class="text-gray-300 font-light italic">Categories</span></h1>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.categories.initialize') }}" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-2 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">
                    Initialize Defaults
                </button>
            </form>
            <button @click="showAddModal = true" class="flex items-center gap-2 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Category
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-100 text-green-700 px-4 py-3 rounded-xl text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border border-red-100 text-red-700 px-4 py-3 rounded-xl text-xs font-bold">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-gray-100 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50">
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Name</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Description</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Target</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Products</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50/50 transition-colors group">
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
                            <button @click="editingCategory = { id: '{{ $category->id }}', name: '{{ $category->name }}', description: '{{ $category->description }}', target_group: {{ json_encode($category->target_group ?? []) }} }; showEditModal = true" class="p-2 text-gray-400 hover:text-black transition-colors" title="Edit Category">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </button>
                            <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors" title="Delete Category">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center">
                        <div class="text-gray-400 text-sm italic">No categories found.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Add Modal -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-md p-8 shadow-2xl" @click.away="showAddModal = false">
            <h2 class="font-serif text-2xl font-bold mb-6">Add New <span class="text-[#C0422A] italic">Category</span></h2>
            <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Category Name</label>
                    <input type="text" name="name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all"></textarea>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Select Tags</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            <label class="cursor-pointer group">
                                <input type="checkbox" name="target_group[]" value="{{ $group }}" class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-100 bg-gray-50/50 text-[10px] font-black text-gray-400 peer-checked:bg-[#C0422A] peer-checked:text-white peer-checked:border-[#C0422A] transition-all uppercase tracking-widest">
                                    {{ $group }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showAddModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">Create Category</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white rounded-3xl w-full max-w-md p-8 shadow-2xl" @click.away="showEditModal = false">
            <h2 class="font-serif text-2xl font-bold mb-6">Edit <span class="text-[#C0422A] italic">Category</span></h2>
            <form :action="'/admin/categories/' + editingCategory.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Category Name</label>
                    <input type="text" name="name" x-model="editingCategory.name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Description</label>
                    <textarea name="description" x-model="editingCategory.description" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#C0422A]/20 transition-all"></textarea>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Select Tags</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['Men', 'Women', 'Kids'] as $group)
                            <label class="cursor-pointer group">
                                <input type="checkbox" name="target_group[]" value="{{ $group }}" 
                                    :checked="editingCategory.target_group && editingCategory.target_group.includes('{{ $group }}')"
                                    class="hidden peer">
                                <div class="px-4 py-2 rounded-xl border border-gray-100 bg-gray-50/50 text-[10px] font-black text-gray-400 peer-checked:bg-[#C0422A] peer-checked:text-white peer-checked:border-[#C0422A] transition-all uppercase tracking-widest">
                                    {{ $group }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showEditModal = false" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 px-6 py-3 bg-[#3D2B1F] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
