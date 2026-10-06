@extends('layouts.superadmin')

@section('content')
<div class="space-y-6">
    {{-- ═══ PAGE HEADER ═══ --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="text-left space-y-0.5">
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Taxonomy Control</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Marketplace Taxonomy</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-black uppercase tracking-wider bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full border border-gray-200">View Only</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Product <span class="text-[#C0420A] font-light italic">Categories</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Browse marketplace catalog categories, target demographics, and active inventory</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left border-collapse min-w-137.5 text-xs">
            <thead>
                <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                    <th class="px-6 py-3.5">Image</th>
                    <th class="px-6 py-3.5">Category Name</th>
                    <th class="px-6 py-3.5">Description</th>
                    <th class="px-6 py-3.5">Target Audience</th>
                    <th class="px-6 py-3.5 text-right">Active Products</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($categories as $category)
                <tr class="hover:bg-gray-50/80 transition-colors">
                    <td class="px-6 py-4">
                        <div class="w-12 h-12 rounded-xl overflow-hidden bg-gray-50 border border-gray-200">
                            <img src="{{ $category->getImageUrl() }}" alt="{{ $category->name }}" class="w-full h-full object-cover">
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-bold text-gray-900">{{ $category->name }}</div>
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
                                <span class="px-2.5 py-0.5 rounded-full text-[9px] font-bold tracking-wider uppercase
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
                    <td class="px-6 py-4 text-right">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-gray-100 rounded-full text-[10px] font-bold text-gray-700">
                            {{ $category->products_count }} {{ Str::plural('item', $category->products_count) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">
                        No categories found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
