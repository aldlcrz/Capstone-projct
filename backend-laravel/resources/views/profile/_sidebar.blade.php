<aside class="w-full md:w-56 shrink-0">
    {{-- User identity --}}
    <div class="flex items-center gap-3 pb-5 border-b border-gray-100 mb-4 px-1">
        <div class="w-12 h-12 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden shrink-0">
            @if($user->profilePhoto)
                <img src="{{ $user->profilePhoto }}" class="w-full h-full object-cover">
            @else
                <span class="text-lg font-bold text-gray-400">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            @endif
        </div>
        <div class="min-w-0">
            <div class="text-sm font-semibold text-gray-900 truncate">{{ $user->name }}</div>
            <a href="{{ route('profile') }}" class="text-xs text-gray-400 hover:text-[#C0420A] flex items-center gap-1 mt-0.5 transition-colors">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Profile
            </a>
        </div>
    </div>

    {{-- My Account section --}}
    <div class="mb-4">
        <div class="flex items-center gap-2 px-1 mb-2">
            <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-sm font-semibold text-gray-800">My Account</span>
        </div>
        <nav class="space-y-0.5 pl-6">
            <a href="{{ route('profile') }}"
               class="block px-3 py-2 text-sm rounded transition-colors
               {{ request()->routeIs('profile') && !request()->routeIs('profile.addresses') && !request()->routeIs('profile.change-password')
                  ? 'text-[#C0420A] font-semibold' : 'text-gray-500 hover:text-[#C0420A]' }}">
                Profile
            </a>
            <a href="{{ route('profile.addresses') }}"
               class="block px-3 py-2 text-sm rounded transition-colors
               {{ request()->routeIs('profile.addresses') ? 'text-[#C0420A] font-semibold' : 'text-gray-500 hover:text-[#C0420A]' }}">
                Addresses
            </a>
            <a href="{{ route('profile.change-password') }}"
               class="block px-3 py-2 text-sm rounded transition-colors
               {{ request()->routeIs('profile.change-password') ? 'text-[#C0420A] font-semibold' : 'text-gray-500 hover:text-[#C0420A]' }}">
                Change Password
            </a>
        </nav>
    </div>

    {{-- My Purchase section --}}
    <div>
        <a href="{{ route('orders') }}" class="flex items-center gap-2 px-1 py-1 group">
            <svg class="w-4 h-4 text-[#C0420A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <span class="text-sm font-semibold text-gray-800 group-hover:text-[#C0420A] transition-colors">My Purchase</span>
        </a>
    </div>
</aside>
