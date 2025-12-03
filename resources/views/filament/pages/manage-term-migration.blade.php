<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Current State Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Current Academic Period</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Current Session</p>
                    <p class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $currentSessionName }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Current Term</p>
                    <p class="text-xl font-bold text-primary-600 dark:text-primary-400">{{ $currentTermName }}</p>
                </div>
            </div>
        </div>

        {{-- Migration Form --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Migrate Term</h2>
            
            @if (empty($allowedTerms))
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <p class="text-yellow-800 dark:text-yellow-200">
                        No migration available. Please ensure current session and term are set.
                    </p>
                </div>
            @else
                <form wire:submit="migrate">
                    {{ $this->form }}
                    
                    <div class="mt-6">
                        {{ $this->getMountedActionForm() }}
                    </div>
                </form>

                <div class="mt-6 flex justify-end">
                    @foreach ($this->getFormActions() as $action)
                        {{ $action }}
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Information Card --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">Migration Rules</h3>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• First Term → Second Term (allowed)</li>
                <li>• Second Term → Third Term (allowed)</li>
                <li>• Third Term → First Term (allowed, increments session and promotes students)</li>
                <li>• All other transitions are blocked</li>
            </ul>
        </div>

        <x-filament-actions::modals />
    </div>
</x-filament-panels::page>
