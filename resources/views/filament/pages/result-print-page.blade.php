<div>
    <x-filament-panels::page>
        <div class="space-y-6">
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>

            @if($this->session_id && $this->term_id)
                <div class="mt-6">
                    {{ $this->table }}
                </div>
            @else
                <x-filament::section>
                    <x-slot name="heading">
                        No Filters Selected
                    </x-slot>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Please select an academic session and term to view results.
                    </p>
                </x-filament::section>
            @endif
        </div>
    </x-filament-panels::page>
</div>
