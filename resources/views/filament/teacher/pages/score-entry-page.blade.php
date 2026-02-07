<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Select Criteria
            </x-slot>
            
            <x-slot name="description">
                Choose the class, subject, and score type to enter scores for
            </x-slot>

            <form wire:submit.prevent="save">
                {{ $this->filterForm }}
            </form>
        </x-filament::section>

        {{-- Score Entry Table --}}
        @if($classroom_id && $subject_id && $score_header_id)
            <x-filament::section>
                <x-slot name="heading">
                    Student Scores
                </x-slot>
                
                <x-slot name="description">
                    Enter scores for each student. Click "Save All Scores" when done.
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::button
                        wire:click="save"
                        color="success"
                        icon="heroicon-o-check"
                    >
                        Save All Scores
                    </x-filament::button>
                </x-slot>

                {{ $this->table }}
            </x-filament::section>
        @else
            <x-filament::section>
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No criteria selected</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Please select all criteria above to load students and enter scores.
                        </p>
                    </div>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
