@props([
    'variant' => 'primary', // 'primary', 'secondary-light', 'secondary-dark', 'outline'
    'href' => null,
    'type' => 'button',
])

@php
    $baseClasses = 'uppercase text-xs sm:text-sm tracking-wider font-sans font-semibold transition text-center rounded-none shadow-none inline-block';
    
    $variantClasses = match($variant) {
        'primary' => 'px-8 py-4 bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover',
        'secondary-light' => 'px-8 py-4 border border-white/40 text-white bg-transparent hover:bg-white hover:text-ink',
        'secondary-dark' => 'py-3 px-4 tracking-widest border border-ink text-ink hover:bg-ink hover:text-[color:var(--ink-contrast)]',
        'outline' => 'px-6 py-2.5 text-xs tracking-wider border border-ink/30 text-ink hover:border-ink',
        default => 'px-8 py-4 bg-accent text-[color:var(--accent-contrast)] hover:bg-accent-hover',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => "$baseClasses $variantClasses"]) }}>
        {{ $slot }}
    </button>
@endif
