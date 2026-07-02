<x-filament-panels::page>
    <div class="space-y-6">
        <div class="prose max-w-none">
            <p>{{ $record->description }}</p>
        </div>

        <form wire:submit="submit">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" size="lg">
                    Submit Assignment
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
