@props([
    'color' => 'bg-rule',
    'container' => true,
])

@if($container)
<div {{ $attributes->merge(['class' => 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8']) }}>
    <div class="h-[1px] w-full {{ $color }}"></div>
</div>
@else
<div {{ $attributes->merge(['class' => "h-[1px] w-full $color"]) }}></div>
@endif
