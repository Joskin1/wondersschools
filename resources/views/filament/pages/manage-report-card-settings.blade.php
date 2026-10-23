<x-filament-panels::page>
    <form wire:submit="submit" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
            <div class="text-xs text-gray-500 dark:text-gray-400">
                Any modifications made here apply immediately to generated terminal report card PDFs and student portal views.
            </div>

            <x-filament::button type="submit" size="lg" icon="heroicon-m-check">
                Save Report Card Settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
