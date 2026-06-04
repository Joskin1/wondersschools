@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center text-sm font-semibold text-white border-b-2 border-white pb-0.5 transition duration-300 ease-in-out'
            : 'inline-flex items-center text-sm font-medium text-white/80 hover:text-white border-b-2 border-transparent hover:border-white/50 pb-0.5 transition duration-300 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
