@props(['items' => []])

<nav class="lg:hidden fixed bottom-0 left-0 right-0 z-[100] flex items-center justify-around gap-1 border-t border-gray-100 bg-white/90 px-2 py-2 shadow-[0_-8px_30px_rgba(0,0,0,0.04)] backdrop-blur-xl h-[calc(110px+var(--safe-bottom,0px))] pb-[calc(2.5rem+var(--safe-bottom,0px))] sm:px-4">
    @foreach($items as $item)
        @php
            $path = ltrim($item['path'], '/');
            $active = ($path === '' && request()->is('/')) || ($path !== '' && request()->is($path . '*'));
        @endphp
        <a href="{{ $item['path'] }}" class="group relative flex min-w-0 flex-1 flex-col items-center justify-center gap-1.5 rounded-2xl py-1">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl transition-all duration-300 {{ $active ? 'bg-black text-white shadow-lg -translate-y-1' : 'text-gray-400 group-hover:text-black group-hover:bg-gray-50' }}">
                @if(isset($item['icon']))
                    {!! $item['icon'] !!}
                @endif
            </div>
            <span class="max-w-full truncate px-1 text-[11px] font-semibold uppercase tracking-[0.14em] transition-opacity duration-300 {{ $active ? 'opacity-100 text-black' : 'opacity-70 text-gray-500' }}">
                {{ $item['label'] }}
            </span>

            @if($active)
                <div class="absolute -bottom-0.5 h-1 w-1 rounded-full bg-black"></div>
            @endif
        </a>
    @endforeach
</nav>
