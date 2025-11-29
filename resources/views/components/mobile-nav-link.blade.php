@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block pl-3 pr-4 py-2 border-l-4 border-lime-green text-base font-medium text-white bg-white/10 focus:outline-none focus:text-white focus:bg-white/10 focus:border-lime-green transition duration-150 ease-in-out'
            : 'block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-300 hover:text-white hover:bg-white/5 hover:border-gray-300 focus:outline-none focus:text-white focus:bg-white/5 focus:border-gray-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
