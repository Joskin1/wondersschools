<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <x-filament::section>
            <x-slot name="heading">
                Current System State
            </x-slot>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Session</label>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ \App\Models\AcademicSession::find($currentSessionId)?->name ?? 'Not Set' }}
                    </p>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Term</label>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ \App\Models\Term::find($currentTermId)?->name ?? 'Not Set' }}
                    </p>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
