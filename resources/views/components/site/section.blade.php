@props([
    'id' => null,
    'bg' => 'bg-paper',
    'textColor' => 'text-body',
    'py' => 'py-24 md:py-32',
    'container' => true,
    'class' => '',
])

<section {{ $id ? 'id=' . $id : '' }} {{ $attributes->merge(['class' => "relative overflow-hidden $py $bg $textColor $class"]) }}>
    @if($container)
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</section>
