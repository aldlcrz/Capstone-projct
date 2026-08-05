@extends('layouts.app')

@section('content')
<div class="max-w-275 mx-auto py-8">
    <div class="flex flex-col md:flex-row gap-6">

        {{-- Sidebar --}}
        @include('profile._sidebar', ['user' => $user])

        {{-- Main Panel --}}
        <main class="flex-1 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-8 py-6 border-b border-gray-100">
                <h2 class="text-lg font-bold text-gray-900">My Profile</h2>
                <p class="text-xs text-gray-400 mt-0.5">Manage and protect your account</p>
            </div>

            {{-- success is handled by the global floating toast in layouts/app.blade.php --}}

            @if($errors->any())
            <div 
                x-data="{ show: true, init() { setTimeout(() => this.show = false, 7000) } }"
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-2 sm:translate-y-0 sm:translate-x-2"
                x-transition:enter-end="opacity-100 translate-y-0 sm:translate-x-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed top-6 right-6 z-9999 w-full max-w-sm bg-white/95 backdrop-blur-md rounded-2xl shadow-2xl border border-gray-100 p-4 flex items-start gap-3.5"
                style="display: none;"
                x-cloak
            >
                <div class="w-8 h-8 rounded-full bg-red-50 flex items-center justify-center text-red-600 shrink-0 shadow-sm border border-red-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="grow pt-0.5">
                    <h4 class="text-xs font-black text-black uppercase tracking-wider">Please fix the following</h4>
                    <ul class="text-xs text-gray-500 font-medium mt-1 leading-relaxed space-y-0.5 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button @click="show = false" class="text-gray-300 hover:text-gray-500 transition-colors shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            @endif

            <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="px-8 py-8">
                @csrf
                @method('PUT')

                <div class="flex flex-col lg:flex-row gap-10">
                    {{-- Fields --}}
                    <div class="flex-1 space-y-5">

                        {{-- Username (editable) --}}
                        <div class="grid grid-cols-[160px_1fr] items-center gap-4">
                            <label class="text-sm text-gray-500 text-right" for="username">Username</label>
                            <input id="username" type="text" name="username" value="{{ old('username', $user->username ?? $user->name) }}"
                                class="h-10 px-4 bg-white border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A] transition-colors w-full">
                        </div>

                        {{-- Name --}}
                        <div class="grid grid-cols-[160px_1fr] items-center gap-4">
                            <label class="text-sm text-gray-500 text-right" for="name">Name</label>
                            <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}"
                                class="h-10 px-4 bg-white border border-gray-200 rounded-lg text-sm outline-none focus:border-[#C0420A] transition-colors w-full">
                        </div>

                        {{-- Email (read-only) --}}
                        <div class="grid grid-cols-[160px_1fr] items-center gap-4">
                            <label class="text-sm text-gray-500 text-right">Email</label>
                            <div class="flex items-center gap-3">
                                <span class="text-sm text-gray-800">{{ $user->email }}</span>
                            </div>
                        </div>



                        {{-- Submit --}}
                        <div class="grid grid-cols-[160px_1fr] items-center gap-4 pt-2">
                            <div></div>
                            <button type="submit"
                                class="w-32 py-2.5 bg-[#C0420A] text-white text-sm font-semibold rounded-lg hover:bg-[#a83808] transition-colors shadow-sm">
                                Save
                            </button>
                        </div>
                    </div>

                    {{-- Avatar --}}
                    <div class="flex flex-col items-center gap-4 lg:w-48 lg:border-l lg:border-gray-100 lg:pl-10">
                        <div class="w-24 h-24 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center overflow-hidden">
                            @if($user->profilePhoto)
                                <img src="{{ str_starts_with($user->profilePhoto, 'http') || str_starts_with($user->profilePhoto, '/') ? $user->profilePhoto : asset('storage/' . $user->profilePhoto) }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-3xl font-bold text-gray-400">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            @endif
                        </div>
                        <label class="cursor-pointer">
                            <input type="file" name="avatar" accept="image/jpeg,image/png" class="hidden" onchange="previewAvatar(this)">
                            <div class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:border-gray-400 transition-colors">
                                Select Image
                            </div>
                        </label>
                        <div class="text-center text-[11px] text-gray-400 leading-relaxed">
                            File size: maximum 1 MB<br>
                            File extension: JPEG, PNG
                        </div>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            const container = input.closest('label').previousElementSibling;
            container.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover rounded-full">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
