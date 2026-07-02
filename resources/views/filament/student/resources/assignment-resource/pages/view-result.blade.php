<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose max-w-none mb-6">
            <h2 class="text-xl font-bold">{{ $record->title }}</h2>
            <p>{{ $record->description }}</p>
        </div>

        {{ $this->infolist }}

        <div class="mt-6 flex justify-end">
            <x-filament::button tag="a" href="{{ static::getResource()::getUrl('index') }}" color="gray">
                Back to Assignments
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
