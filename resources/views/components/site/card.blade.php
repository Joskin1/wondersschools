@props([
    'bg' => 'bg-white',
    'border' => 'border border-rule',
    'padding' => 'p-8 sm:p-10',
    'class' => '',
])

<div {{ $attributes->merge(['class' => "$bg $border $padding rounded-none shadow-none $class"]) }}>
    {{ $slot }}
</div>
