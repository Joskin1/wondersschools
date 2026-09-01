<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <div class="space-y-4">
                <div>
                    <label for="student-import-upload" class="block text-sm font-medium text-gray-950 dark:text-white">
                        Student spreadsheet
                    </label>
                    <div class="mt-2">
                        <input
                            id="student-import-upload"
                            type="file"
                            wire:model="upload"
                            accept=".xlsx,.xls,.csv"
                            class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-950 shadow-sm file:me-4 file:border-0 file:bg-gray-50 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-100 focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:file:bg-white/10 dark:file:text-gray-200"
                        />
                    </div>
                    @error('upload')
                        <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                    @else
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            Accepted formats: xlsx, xls, csv. Maximum size: 5 MB.
                        </p>
                    @enderror
                </div>

                <div class="flex flex-wrap gap-3">
                    <x-filament::button
                        type="button"
                        icon="heroicon-o-eye"
                        wire:click="preview"
                        wire:loading.attr="disabled"
                        wire:target="preview,upload"
                    >
                        Preview
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        color="gray"
                        icon="heroicon-o-x-mark"
                        wire:click="clearPreview"
                        wire:loading.attr="disabled"
                    >
                        Clear
                    </x-filament::button>

                    @if (count($previewRows) > 0 && $invalidCount === 0)
                        <x-filament::button
                            type="button"
                            color="success"
                            icon="heroicon-o-check"
                            wire:click="saveImport"
                            wire:confirm="Create {{ $validCount }} student records now?"
                            wire:loading.attr="disabled"
                        >
                            Save Students
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        @if (count($previewRows) > 0)
            <div class="grid gap-4 sm:grid-cols-3">
                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Rows</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ count($previewRows) }}</div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Ready</div>
                    <div class="mt-1 text-2xl font-semibold text-success-600 dark:text-success-400">{{ $validCount }}</div>
                </x-filament::section>

                <x-filament::section>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Needs Attention</div>
                    <div class="mt-1 text-2xl font-semibold text-danger-600 dark:text-danger-400">{{ $invalidCount }}</div>
                </x-filament::section>
            </div>

            <x-filament::section>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-white/10">
                        <thead>
                            <tr class="text-left font-semibold text-gray-700 dark:text-gray-200">
                                <th class="whitespace-nowrap px-3 py-3">Row</th>
                                <th class="whitespace-nowrap px-3 py-3">Student</th>
                                <th class="whitespace-nowrap px-3 py-3">Adm No</th>
                                <th class="whitespace-nowrap px-3 py-3">Password</th>
                                <th class="whitespace-nowrap px-3 py-3">Classroom</th>
                                <th class="whitespace-nowrap px-3 py-3">Session</th>
                                <th class="whitespace-nowrap px-3 py-3">Gender</th>
                                <th class="whitespace-nowrap px-3 py-3">Date of Birth</th>
                                <th class="whitespace-nowrap px-3 py-3">Parent</th>
                                <th class="whitespace-nowrap px-3 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                            @foreach ($previewRows as $row)
                                <tr @class([
                                    'align-top',
                                    'bg-danger-50/60 dark:bg-danger-950/20' => ! $row['valid'],
                                ])>
                                    <td class="whitespace-nowrap px-3 py-3 text-gray-500 dark:text-gray-400">
                                        {{ $row['row'] }}
                                    </td>
                                    <td class="min-w-48 px-3 py-3 font-medium text-gray-950 dark:text-white">
                                        {{ $row['data']['full_name'] ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-gray-700 dark:text-gray-200">
                                        Auto
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-gray-700 dark:text-gray-200">
                                        {{ $row['data']['password'] ? 'Set' : '-' }}
                                    </td>
                                    <td class="min-w-40 px-3 py-3 text-gray-700 dark:text-gray-200">
                                        {{ ($row['classroom_name'] ?? $row['data']['classroom']) ?: '-' }}
                                    </td>
                                    <td class="min-w-40 px-3 py-3 text-gray-700 dark:text-gray-200">
                                        {{ ($row['session_name'] ?? $row['data']['session']) ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-gray-700 dark:text-gray-200">
                                        {{ $row['data']['gender'] ?: '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-gray-700 dark:text-gray-200">
                                        {{ $row['data']['date_of_birth'] ?: '-' }}
                                    </td>
                                    <td class="min-w-48 px-3 py-3 text-gray-700 dark:text-gray-200">
                                        <div>{{ $row['data']['parent_name'] ?: '-' }}</div>
                                        @if ($row['data']['parent_phone'] || $row['data']['parent_email'])
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ collect([$row['data']['parent_phone'], $row['data']['parent_email']])->filter()->join(' · ') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="min-w-56 px-3 py-3">
                                        @if ($row['valid'])
                                            <x-filament::badge color="success">Ready</x-filament::badge>
                                        @else
                                            <div class="space-y-1">
                                                <x-filament::badge color="danger">Fix row</x-filament::badge>
                                                @foreach ($row['errors'] as $error)
                                                    <div class="text-xs text-danger-700 dark:text-danger-300">{{ $error }}</div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
