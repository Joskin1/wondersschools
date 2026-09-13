@props([
    'number' => null,
    'text' => null,
    'rule' => true,
    'ruleClass' => 'bg-rule',
    'textColor' => 'text-accent',
    'center' => false,
])

<div {{ $attributes->merge(['class' => 'flex items-center ' . ($center ? 'justify-center ' : '') . 'gap-3']) }}>
    @if($center)
        <span class="w-8 h-[1px] bg-accent"></span>
    @endif

    <span class="text-xs uppercase tracking-[0.2em] font-sans font-bold {{ $textColor }}">
        @if($number)
            {{ $number }} &mdash;
        @endif
        {{ $text ?? $slot }}
    </span>

    @if($center)
        <span class="w-8 h-[1px] bg-accent"></span>
    @elseif($rule)
        <span class="flex-grow h-[1px] {{ $ruleClass }}"></span>
    @endif
</div>
