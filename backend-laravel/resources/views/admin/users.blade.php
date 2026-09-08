@extends('layouts.admin')

@section('content')
<div class="space-y-6 relative" x-data="{
    banModal: false,
    banUserId: null,
    banUserName: '',
    banReason: '',
    deleteModal: false,
    deleteUserId: null,
    deleteUserName: '',
    deleteReason: '',
    deleteConfirmChecked: false,
    viewModal: false,
    selectedUser: null,
    addCustomerModal: false,
    selectedUsers: [],
    allIds: {{ json_encode($users->pluck('id')->values()->all()) }},
    get allSelected() {
        return this.allIds.length > 0 && this.allIds.every(id => this.selectedUsers.includes(id));
    },
    toggleAll(e) {
        if (e.target.checked) {
            this.selectedUsers = [...this.allIds];
        } else {
            this.selectedUsers = [];
        }
    },
    openView(user) {
        this.selectedUser = user;
        this.viewModal = true;
    },
    openBan(user) {
        this.banUserId = user.id;
        this.banUserName = user.name;
        this.banReason = 'Violation of platform customer terms';
        this.banModal = true;
    },
    openDelete(user) {
        this.deleteUserId = user.id;
        this.deleteUserName = user.name;
        this.deleteReason = '';
        this.deleteConfirmChecked = false;
        this.deleteModal = true;
    }
}">

    {{-- Heritage Radial Motif Watermark in Top-Right Corner --}}
    <div class="absolute -top-10 -right-10 pointer-events-none select-none opacity-[0.07] w-72 h-72 sm:w-88 sm:h-88 z-0">
        <svg viewBox="0 0 200 200" class="w-full h-full text-[#93622E]" fill="currentColor">
            <g transform="translate(100, 100)">
                @for($i = 0; $i < 24; $i++)
                    <path d="M0 -30 C12 -55 18 -80 0 -98 C-18 -80 -12 -55 0 -30 Z" transform="rotate({{ $i * 15 }})" opacity="0.4" fill="currentColor"/>
                    <circle cx="0" cy="-65" r="3.5" transform="rotate({{ $i * 15 }})" fill="currentColor" opacity="0.6"/>
                    <circle cx="0" cy="-45" r="2.5" transform="rotate({{ $i * 15 }})" fill="currentColor" opacity="0.5"/>
                @endfor
                <circle cx="0" cy="0" r="30" fill="none" stroke="currentColor" stroke-width="2" opacity="0.5"/>
                <circle cx="0" cy="0" r="18" fill="none" stroke="currentColor" stroke-width="1.5" opacity="0.4"/>
                <circle cx="0" cy="0" r="6" fill="currentColor" opacity="0.5"/>
            </g>
        </svg>
    </div>

    {{-- ═══ PAGE HEADER ═══ --}}
    <div class="relative z-10 flex flex-col gap-1 pb-1">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-[#FAF5EE] border border-[#F0E6D8] flex items-center justify-center text-[#93622E] shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight">
                Customer <span class="font-light italic text-[#93622E]">Management</span>
            </h1>
        </div>
        <p class="text-xs sm:text-[13px] text-gray-500 font-normal pl-12">Manage registered marketplace buyers and their account status.</p>
    </div>

    {{-- ═══ 4-METRIC STATUS CARDS ═══ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 relative z-10">
        {{-- Total Customers --}}
        <div class="bg-white rounded-2xl border border-gray-100/90 shadow-2xs p-5 flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-full bg-[#FAF5EE] border border-[#F0E6D8] flex items-center justify-center text-[#93622E] shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500">Total Customers</div>
                <div class="text-2xl font-black text-gray-900 leading-tight mt-0.5">{{ $counts['all'] ?? $users->total() }}</div>
                <div class="flex items-center gap-1 text-[11px] text-gray-400 mt-0.5">
                    <span class="text-emerald-600 font-bold flex items-center gap-0.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        {{ $trends['all'] ?? 12 }}%
                    </span>
                    <span>vs. last 30 days</span>
                </div>
            </div>
        </div>

        {{-- Active Accounts --}}
        <div class="bg-white rounded-2xl border border-gray-100/90 shadow-2xs p-5 flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500">Active Accounts</div>
                <div class="text-2xl font-black text-gray-900 leading-tight mt-0.5">{{ $counts['active'] ?? 0 }}</div>
                <div class="flex items-center gap-1 text-[11px] text-gray-400 mt-0.5">
                    <span class="text-emerald-600 font-bold flex items-center gap-0.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        {{ $trends['active'] ?? 12 }}%
                    </span>
                    <span>vs. last 30 days</span>
                </div>
            </div>
        </div>

        {{-- Blocked Accounts --}}
        <div class="bg-white rounded-2xl border border-gray-100/90 shadow-2xs p-5 flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-full bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-500 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500">Blocked Accounts</div>
                <div class="text-2xl font-black text-gray-900 leading-tight mt-0.5">{{ $counts['blocked'] ?? 0 }}</div>
                <div class="flex items-center gap-1 text-[11px] text-gray-400 mt-0.5">
                    <span class="text-gray-400 font-semibold">{{ $trends['blocked'] ?? 0 }}%</span>
                    <span>vs. last 30 days</span>
                </div>
            </div>
        </div>

        {{-- Frozen Accounts --}}
        <div class="bg-white rounded-2xl border border-gray-100/90 shadow-2xs p-5 flex items-center gap-4 hover:shadow-md transition-all">
            <div class="w-12 h-12 rounded-full bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-500 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v20m10-10H2m15.07-5.07L4.93 19.07m14.14 0L4.93 4.93M12 6l-2-2m2 2l2-2m-2 16l-2 2m2-2l2 2m6-10l2-2m-2 2l2 2M4 12l-2-2m2 2l-2 2"/></svg>
            </div>
            <div>
                <div class="text-xs font-semibold text-gray-500">Frozen Accounts</div>
                <div class="text-2xl font-black text-gray-900 leading-tight mt-0.5">{{ $counts['frozen'] ?? 0 }}</div>
                <div class="flex items-center gap-1 text-[11px] text-gray-400 mt-0.5">
                    <span class="text-gray-400 font-semibold">{{ $trends['frozen'] ?? 0 }}%</span>
                    <span>vs. last 30 days</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ SEARCH, FILTER PILLS & ACTION BUTTONS STRIP ═══ --}}
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 relative z-10 pt-1">
        {{-- Left: Search input and Status filter pills --}}
        <div class="flex flex-wrap items-center gap-2.5 flex-1">
            {{-- Search Bar --}}
            <form method="GET" action="{{ route('admin.users') }}" class="relative w-full sm:w-72 md:w-80">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('sort_by'))
                    <input type="hidden" name="sort_by" value="{{ request('sort_by') }}">
                @endif
                @if(request('sort_dir'))
                    <input type="hidden" name="sort_dir" value="{{ request('sort_dir') }}">
                @endif
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by name, email, or phone number..."
                       class="w-full pl-9 pr-7 py-2 bg-white border border-gray-200/90 rounded-full text-xs text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-[#93622E]/20 focus:border-[#93622E] shadow-2xs transition-all">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                @if(request('search'))
                    <a href="{{ request()->fullUrlWithQuery(['search' => null, 'page' => 1]) }}" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 text-xs font-bold">✕</a>
                @endif
            </form>

            {{-- Filter Pills --}}
            @php $currentStatus = request('status'); @endphp
            <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar">
                {{-- All --}}
                <a href="{{ request()->fullUrlWithQuery(['status' => null, 'page' => 1]) }}"
                   class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs transition-all whitespace-nowrap {{ empty($currentStatus) || $currentStatus === 'all' ? 'bg-stone-900 text-white font-bold shadow-xs' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50 font-medium' }}">
                    All ({{ $counts['all'] ?? $users->total() }})
                </a>
                {{-- Active --}}
                <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs transition-all whitespace-nowrap {{ $currentStatus === 'active' ? 'bg-emerald-700 text-white font-bold shadow-xs' : 'bg-white text-gray-600 border border-gray-200 hover:bg-emerald-50 font-medium' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $currentStatus === 'active' ? 'bg-emerald-200' : 'bg-emerald-500' }}"></span>
                    Active ({{ $counts['active'] ?? 0 }})
                </a>
                {{-- Blocked --}}
                <a href="{{ request()->fullUrlWithQuery(['status' => 'blocked', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs transition-all whitespace-nowrap {{ $currentStatus === 'blocked' ? 'bg-rose-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 border border-gray-200 hover:bg-rose-50 font-medium' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $currentStatus === 'blocked' ? 'bg-rose-200' : 'bg-rose-500' }}"></span>
                    Blocked ({{ $counts['blocked'] ?? 0 }})
                </a>
                {{-- Frozen --}}
                <a href="{{ request()->fullUrlWithQuery(['status' => 'frozen', 'page' => 1]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs transition-all whitespace-nowrap {{ $currentStatus === 'frozen' ? 'bg-amber-600 text-white font-bold shadow-xs' : 'bg-white text-gray-600 border border-gray-200 hover:bg-amber-50 font-medium' }}">
                    <span class="w-1.5 h-1.5 rounded-full {{ $currentStatus === 'frozen' ? 'bg-amber-200' : 'bg-amber-500' }}"></span>
                    Frozen ({{ $counts['frozen'] ?? 0 }})
                </a>
            </div>
        </div>

        {{-- Right: Export and + Add Customer --}}
        <div class="flex items-center gap-2.5 shrink-0 self-end xl:self-center">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200/90 rounded-full text-xs font-semibold shadow-2xs transition-all">
                <svg class="w-3.5 h-3.5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </a>
            <button type="button" @click="addCustomerModal = true"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-[#93622E] hover:bg-[#7E5224] text-white rounded-full text-xs font-semibold shadow-xs transition-all cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add Customer
            </button>
        </div>
    </div>

    {{-- ═══ CUSTOMER TABLE ═══ --}}
    @php
        $nextSortDir = (request('sort_dir') === 'asc') ? 'desc' : 'asc';
        $currentSortBy = request('sort_by', 'createdAt');
        $currentSortDir = request('sort_dir', 'desc');

        // Harmonious avatar background map for letters
        $avatarColors = [
            'A' => 'bg-[#2D6A4F] text-white',
            'B' => 'bg-[#3D5A80] text-white',
            'C' => 'bg-[#93622E] text-white',
            'D' => 'bg-[#2E5A54] text-white',
            'E' => 'bg-[#8E44AD] text-white',
            'F' => 'bg-[#B5838D] text-white',
            'G' => 'bg-[#4A4E69] text-white',
            'H' => 'bg-[#2D6A4F] text-white',
            'I' => 'bg-[#8E44AD] text-white',
            'J' => 'bg-[#3D5A80] text-white',
            'K' => 'bg-[#93622E] text-white',
            'L' => 'bg-[#2E5A54] text-white',
            'M' => 'bg-[#B5838D] text-white',
            'N' => 'bg-[#4A4E69] text-white',
            'O' => 'bg-[#2D6A4F] text-white',
            'P' => 'bg-[#8E44AD] text-white',
            'Q' => 'bg-[#3D5A80] text-white',
            'R' => 'bg-[#93622E] text-white',
            'S' => 'bg-[#2E5A54] text-white',
            'T' => 'bg-[#B5838D] text-white',
            'U' => 'bg-[#4A4E69] text-white',
            'V' => 'bg-[#2D6A4F] text-white',
            'W' => 'bg-[#8E44AD] text-white',
            'X' => 'bg-[#4A4E69] text-white',
            'Y' => 'bg-[#3D5A80] text-white',
            'Z' => 'bg-[#93622E] text-white',
        ];
    @endphp

    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden relative z-10">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left min-w-[700px]">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        {{-- Select All Checkbox --}}
                        <th class="w-12 px-5 py-4">
                            <input type="checkbox" @change="toggleAll($event)" :checked="allSelected"
                                   class="w-4 h-4 rounded border-gray-300 text-[#93622E] focus:ring-[#93622E]/30 cursor-pointer">
                        </th>
                        {{-- Customer Sortable --}}
                        <th class="px-4 py-4 text-[10px] font-black uppercase tracking-wider text-gray-400">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_dir' => $currentSortBy === 'name' ? $nextSortDir : 'asc', 'page' => 1]) }}"
                               class="inline-flex items-center gap-1.5 hover:text-gray-700 transition-colors">
                                <span>Customer</span>
                                <span class="text-gray-300 text-xs font-bold {{ $currentSortBy === 'name' ? 'text-[#93622E]' : '' }}">⇅</span>
                            </a>
                        </th>
                        {{-- Joined Sortable --}}
                        <th class="px-4 py-4 text-[10px] font-black uppercase tracking-wider text-gray-400">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'createdAt', 'sort_dir' => $currentSortBy === 'createdAt' ? $nextSortDir : 'desc', 'page' => 1]) }}"
                               class="inline-flex items-center gap-1.5 hover:text-gray-700 transition-colors">
                                <span>Joined</span>
                                <span class="text-gray-300 text-xs font-bold {{ $currentSortBy === 'createdAt' ? 'text-[#93622E]' : '' }}">⇅</span>
                            </a>
                        </th>
                        {{-- Status Sortable --}}
                        <th class="px-4 py-4 text-[10px] font-black uppercase tracking-wider text-gray-400">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_dir' => $currentSortBy === 'status' ? $nextSortDir : 'asc', 'page' => 1]) }}"
                               class="inline-flex items-center gap-1.5 hover:text-gray-700 transition-colors">
                                <span>Status</span>
                                <span class="text-gray-300 text-xs font-bold {{ $currentSortBy === 'status' ? 'text-[#93622E]' : '' }}">⇅</span>
                            </a>
                        </th>
                        {{-- Actions --}}
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-wider text-gray-400 text-center">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $user)
                        @php
                            $initial = strtoupper(substr($user->name ?? 'C', 0, 1));
                            $avatarClass = $avatarColors[$initial] ?? 'bg-[#93622E] text-white';
                        @endphp
                        <tr class="transition-colors group"
                            :class="selectedUsers.includes('{{ $user->id }}') ? 'bg-[#FCF9F2] border-l-4 border-[#C08A3E]' : 'hover:bg-gray-50/70 border-l-4 border-transparent'">
                            {{-- Row Checkbox --}}
                            <td class="w-12 px-5 py-4">
                                <input type="checkbox" :value="'{{ $user->id }}'" x-model="selectedUsers"
                                       class="w-4 h-4 rounded border-gray-300 text-[#93622E] focus:ring-[#93622E]/30 cursor-pointer">
                            </td>

                            {{-- Customer Identity --}}
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full {{ $avatarClass }} flex items-center justify-center font-bold text-sm shrink-0 overflow-hidden shadow-2xs">
                                        @if($user->profilePhoto)
                                            <img src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}"
                                                 alt="{{ $user->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span>{{ $initial }}</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-sm font-bold text-gray-900 truncate leading-tight">{{ $user->name }}</div>
                                        <div class="text-xs text-gray-400 font-normal truncate mt-0.5 leading-tight">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            {{-- Joined Date with Clock Icon --}}
                            <td class="px-4 py-4">
                                <div class="inline-flex items-center gap-1.5 text-xs text-gray-500 font-medium whitespace-nowrap">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>{{ $user->createdAt ? $user->createdAt->format('M d, Y') : 'N/A' }}</span>
                                </div>
                            </td>

                            {{-- Status Badge --}}
                            <td class="px-4 py-4">
                                @if($user->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        ACTIVE
                                    </span>
                                @elseif($user->status === 'blocked')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wider bg-rose-50 text-rose-700 border border-rose-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        BLOCKED
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wider bg-amber-50 text-amber-700 border border-amber-200/80">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        FROZEN
                                    </span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    {{-- View Button --}}
                                    <button type="button" @click="openView({{ json_encode($user) }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium text-gray-700 bg-gray-50 border border-gray-200 hover:bg-gray-100 transition-all cursor-pointer shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                        <span>View</span>
                                    </button>

                                    {{-- Ban / Restore Button --}}
                                    @if($user->status === 'active')
                                        <button type="button" @click="openBan({{ json_encode($user) }})"
                                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium text-rose-700 bg-rose-50 border border-rose-200 hover:bg-rose-100 transition-all cursor-pointer shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                            <span>Ban</span>
                                        </button>
                                    @else
                                        <form action="/admin/users/{{ $user->id }}/unban" method="POST" class="inline">
                                            @csrf @method('PATCH')
                                            <button type="submit"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 transition-all cursor-pointer shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span>Restore</span>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Delete Button --}}
                                    <button type="button" @click="openDelete({{ json_encode($user) }})"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-xs font-medium text-gray-700 hover:text-rose-700 bg-gray-50 hover:bg-rose-50 border border-gray-200 hover:border-rose-200 transition-all cursor-pointer shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        <span>Delete</span>
                                    </button>

                                    {{-- Kebab Menu Dropdown --}}
                                    <div class="relative" x-data="{ menuOpen: false }">
                                        <button type="button" @click="menuOpen = !menuOpen"
                                                class="p-1.5 text-gray-400 hover:text-gray-700 rounded-lg hover:bg-gray-100 transition-all cursor-pointer">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <circle cx="5" cy="12" r="1.8"/>
                                                <circle cx="12" cy="12" r="1.8"/>
                                                <circle cx="19" cy="12" r="1.8"/>
                                            </svg>
                                        </button>
                                        <div x-show="menuOpen" @click.outside="menuOpen = false" x-cloak
                                             class="absolute right-0 top-full mt-1 w-44 bg-white rounded-2xl shadow-xl border border-gray-100 py-1.5 z-30 text-xs text-gray-700">
                                            <button type="button" @click="menuOpen = false; openView({{ json_encode($user) }})"
                                                    class="w-full text-left px-3.5 py-2 hover:bg-gray-50 flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                                Account Details
                                            </button>
                                            <a href="mailto:{{ $user->email }}" @click="menuOpen = false"
                                               class="w-full text-left px-3.5 py-2 hover:bg-gray-50 flex items-center gap-2">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                Send Email
                                            </a>
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <button type="button" @click="menuOpen = false; navigator.clipboard.writeText('{{ $user->id }}')"
                                                    class="w-full text-left px-3.5 py-2 hover:bg-gray-50 flex items-center gap-2 text-gray-500">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                Copy User ID
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="py-20 text-center">
                                    <div class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                        <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">No customers found</p>
                                    <p class="text-xs text-gray-300 mt-1">Try adjusting your search or filter criteria</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ═══ TABLE FOOTER & PAGINATION ═══ --}}
        <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 bg-white">
            <span class="text-xs text-gray-400">
                Showing {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} customers
            </span>

            @if($users->hasPages())
                <div class="flex items-center gap-1.5">
                    {{-- Previous Page Link --}}
                    @if($users->onFirstPage())
                        <span class="w-7 h-7 rounded-full text-gray-300 flex items-center justify-center text-xs cursor-not-allowed">‹</span>
                    @else
                        <a href="{{ $users->previousPageUrl() }}" class="w-7 h-7 rounded-full text-gray-600 hover:bg-gray-100 flex items-center justify-center text-xs transition-colors">‹</a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach($users->getUrlRange(max(1, $users->currentPage() - 2), min($users->lastPage(), $users->currentPage() + 2)) as $page => $url)
                        @if($page == $users->currentPage())
                            <span class="w-7 h-7 rounded-full bg-[#93622E] text-white font-bold text-xs flex items-center justify-center shadow-2xs">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="w-7 h-7 rounded-full text-gray-600 hover:bg-gray-100 font-medium text-xs flex items-center justify-center transition-colors">{{ $page }}</a>
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if($users->hasMorePages())
                        <a href="{{ $users->nextPageUrl() }}" class="w-7 h-7 rounded-full text-gray-600 hover:bg-gray-100 flex items-center justify-center text-xs transition-colors">›</a>
                    @else
                        <span class="w-7 h-7 rounded-full text-gray-300 flex items-center justify-center text-xs cursor-not-allowed">›</span>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ═══ FLOATING BULK ACTIONS TOOLBAR ═══ --}}
    <div x-show="selectedUsers.length > 0" x-cloak
         class="fixed bottom-6 left-1/2 -translate-x-1/2 z-40 bg-gray-900/95 backdrop-blur-md text-white rounded-full px-5 py-2.5 shadow-2xl flex items-center gap-4 text-xs">
        <span class="font-bold">
            <span x-text="selectedUsers.length"></span> customers selected
        </span>
        <div class="h-4 w-px bg-white/20"></div>
        <button type="button" @click="selectedUsers = []" class="text-gray-300 hover:text-white transition-colors cursor-pointer">
            Deselect All
        </button>
    </div>

    {{-- ═══ VIEW CUSTOMER PROFILE MODAL ═══ --}}
    <div x-show="viewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-xs" @click="viewModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 sm:p-7 space-y-5 z-10 border border-gray-100">
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-full bg-[#FAF5EE] border border-[#F0E6D8] flex items-center justify-center text-[#93622E] font-bold text-lg overflow-hidden shadow-2xs">
                        <template x-if="selectedUser && selectedUser.profilePhoto">
                            <img :src="selectedUser.profilePhoto.startsWith('http') || selectedUser.profilePhoto.startsWith('/') ? selectedUser.profilePhoto : '/storage/' + selectedUser.profilePhoto" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!selectedUser || !selectedUser.profilePhoto">
                            <span x-text="selectedUser ? selectedUser.name.charAt(0).toUpperCase() : 'C'"></span>
                        </template>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-tight" x-text="selectedUser ? selectedUser.name : ''"></h3>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="selectedUser ? selectedUser.email : ''"></p>
                    </div>
                </div>
                <button type="button" @click="viewModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-full hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3 p-4 bg-gray-50/80 rounded-2xl border border-gray-100 text-xs">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Account Status</span>
                    <span class="font-bold capitalize text-gray-800 mt-0.5 block" x-text="selectedUser ? selectedUser.status : 'N/A'"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Phone Number</span>
                    <span class="font-bold text-gray-800 mt-0.5 block" x-text="selectedUser && selectedUser.mobileNumber ? selectedUser.mobileNumber : 'Not provided'"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Role</span>
                    <span class="font-bold uppercase text-gray-800 mt-0.5 block" x-text="selectedUser ? selectedUser.role : 'Customer'"></span>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Email Verified</span>
                    <span class="font-bold text-emerald-600 mt-0.5 block" x-text="selectedUser && selectedUser.isVerified ? 'Verified' : 'Unverified'"></span>
                </div>
            </div>

            <div class="flex gap-2.5 pt-2">
                <button type="button" @click="viewModal = false"
                        class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-full hover:bg-gray-50 transition-all cursor-pointer">
                    Close
                </button>
                <template x-if="selectedUser && selectedUser.status === 'active'">
                    <button type="button" @click="viewModal = false; openBan(selectedUser)"
                            class="py-2.5 px-5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-full transition-all cursor-pointer">
                        Ban Account
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- ═══ ADD CUSTOMER MODAL ═══ --}}
    <div x-show="addCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-xs" @click="addCustomerModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 sm:p-7 space-y-4 z-10 border border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-[#FAF5EE] border border-[#F0E6D8] flex items-center justify-center text-[#93622E]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <h3 class="text-base font-bold text-gray-900 leading-tight">Add New Customer</h3>
                </div>
                <button type="button" @click="addCustomerModal = false" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-full hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-3.5">
                @csrf
                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1">Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Maria Santos"
                           class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#93622E] focus:bg-white transition-all">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1">Email Address *</label>
                    <input type="email" name="email" required placeholder="e.g. maria@example.com"
                           class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#93622E] focus:bg-white transition-all">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1">Mobile / Phone Number</label>
                    <input type="text" name="mobileNumber" placeholder="e.g. 09171234567"
                           class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#93622E] focus:bg-white transition-all">
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1">Temporary Password *</label>
                    <input type="password" name="password" required minlength="6" value="Welcome123!"
                           class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#93622E] focus:bg-white transition-all">
                    <p class="text-[10px] text-gray-400 mt-1">Default set to <code class="text-gray-600 bg-gray-100 px-1 py-0.5 rounded">Welcome123!</code>. Customer can reset upon login.</p>
                </div>

                <div>
                    <label class="text-[11px] font-bold text-gray-700 uppercase tracking-wider block mb-1">Initial Status</label>
                    <select name="status" class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs text-gray-800 focus:outline-none focus:border-[#93622E] focus:bg-white transition-all">
                        <option value="active" selected>Active</option>
                        <option value="blocked">Blocked</option>
                        <option value="frozen">Frozen</option>
                    </select>
                </div>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="addCustomerModal = false"
                            class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-full hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="flex-1 py-2.5 bg-[#93622E] hover:bg-[#7E5224] text-white text-xs font-bold uppercase tracking-wider rounded-full shadow-sm transition-all cursor-pointer">
                        Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ BAN CONFIRMATION MODAL ═══ --}}
    <div x-show="banModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/40 backdrop-blur-xs" @click="banModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-4 z-10 border border-gray-100">
            <h3 class="text-base font-bold text-gray-900">Ban Customer Account</h3>
            <p class="text-xs text-gray-500 leading-relaxed">
                Are you sure you want to ban customer <strong x-text="banUserName" class="text-gray-900"></strong>? Please provide a reason below for record keeping and user notification.
            </p>
            <form :action="'/admin/users/' + banUserId + '/ban'" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-3">
                        <button type="button" @click="banReason = 'Violation of Terms of Service'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Terms Violation
                        </button>
                        <button type="button" @click="banReason = 'Fraudulent transaction / payment dispute'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Payment Fraud
                        </button>
                        <button type="button" @click="banReason = 'Abusive behavior towards sellers or staff'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Abusive Behavior
                        </button>
                        <button type="button" @click="banReason = 'Suspicious or automated spam activity'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Spam Activity
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 block">Explanation / Reason *</label>
                    <textarea name="reason" x-model="banReason" required rows="3" placeholder="Specify why this account is being suspended..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-red-500"></textarea>
                </div>
                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="banModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-semibold text-gray-600 rounded-full hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold uppercase tracking-wider rounded-full shadow-xs">Confirm Ban</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ═══ DELETE CONFIRMATION MODAL ═══ --}}
    <div x-show="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50 backdrop-blur-xs" @click="deleteModal = false"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 sm:p-7 space-y-5 z-10 border border-gray-100">
            <div class="flex items-start gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-rose-100 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 leading-tight">Delete Customer Account</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Permanently purge <strong x-text="deleteUserName" class="text-gray-900"></strong> and all associated customer account records.
                    </p>
                </div>
            </div>

            <div class="p-3 bg-rose-50/80 border border-rose-200 rounded-2xl text-[11px] text-rose-800 leading-relaxed font-medium">
                ⚠️ <strong>Critical Warning:</strong> This action cannot be undone. All active sessions, authentication tokens, and customer records will be permanently deleted from the system.
            </div>

            <form :action="'/admin/users/' + deleteUserId" method="POST" class="space-y-4">
                @csrf @method('DELETE')
                
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5 block">Quick Reason Presets</label>
                    <div class="flex flex-wrap gap-1.5 mb-2.5">
                        <button type="button" @click="deleteReason = 'Violation of platform customer terms and conditions'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Terms Violation
                        </button>
                        <button type="button" @click="deleteReason = 'Requested by customer account closure'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            User Request
                        </button>
                        <button type="button" @click="deleteReason = 'Inactive or duplicate customer account cleanup'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Inactive Account
                        </button>
                        <button type="button" @click="deleteReason = 'Fraudulent chargeback activity or abusive behavior'"
                            class="px-2.5 py-1 rounded-lg text-[10px] font-semibold bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors">
                            Fraud / Abuse
                        </button>
                    </div>

                    <label class="text-[10px] font-bold text-gray-600 uppercase tracking-widest mb-1 block">Reason for Permanent Deletion *</label>
                    <textarea name="reason" x-model="deleteReason" required rows="2.5" placeholder="Specify reason for deletion for platform audit logs..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-rose-500 font-medium"></textarea>
                </div>

                <label class="flex items-start gap-2.5 cursor-pointer select-none pt-1">
                    <input type="checkbox" x-model="deleteConfirmChecked" class="mt-0.5 rounded border-gray-300 text-rose-600 focus:ring-rose-500 w-4 h-4 cursor-pointer">
                    <span class="text-[11px] text-gray-600 font-medium leading-snug">
                        I confirm that I want to permanently delete this customer account and understand that this action cannot be undone.
                    </span>
                </label>

                <div class="flex gap-2.5 pt-2">
                    <button type="button" @click="deleteModal = false" class="flex-1 py-2.5 border border-gray-200 text-xs font-bold text-gray-600 rounded-full hover:bg-gray-50 transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" :disabled="!deleteConfirmChecked || !deleteReason.trim()" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-bold uppercase tracking-wider rounded-full transition-all shadow-sm cursor-pointer">
                        Confirm &amp; Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
