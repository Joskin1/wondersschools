@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block pl-4 pr-4 py-3 border-l-4 text-base font-semibold text-white bg-white/10 transition duration-150 ease-in-out'
            : 'block pl-4 pr-4 py-3 border-l-4 border-transparent text-base font-medium text-white/80 hover:text-white hover:bg-white/5 hover:border-white/40 transition duration-150 ease-in-out';
$classes .= ($active ?? false) ? ' border-white' : '';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
