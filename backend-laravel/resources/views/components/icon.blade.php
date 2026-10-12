@props([
    'name' => 'circle',
    'class' => 'w-4 h-4',
])

<x-lucide :name="$name" :class="$class" {{ $attributes }} />
