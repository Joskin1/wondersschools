<x-filament-panels::page>
    <div class="max-w-3xl space-y-6">
        <form wire:submit="save" class="space-y-6">
            {{ $this->form }}

            <div class="flex justify-end gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Save Signature
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
