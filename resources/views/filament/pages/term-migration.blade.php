<x-filament-panels::page>
    <div class="space-y-6">
        @php
            $currentTerm = $this->getCurrentTerm();
        @endphp

        @if($currentTerm)
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Current Term Information</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Current Term</p>
                        <p class="text-lg font-semibold">{{ $currentTerm->name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Academic Session</p>
                        <p class="text-lg font-semibold">{{ $currentTerm->academicSession->name }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Start Date</p>
                        <p class="text-lg font-semibold">{{ $currentTerm->start_date->format('M d, Y') }}</p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">End Date</p>
                        <p class="text-lg font-semibold">{{ $currentTerm->end_date->format('M d, Y') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Migration Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Allowed Next Term</p>
                        <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                            {{ $currentTerm->getAllowedNextTerm() }}
                        </p>
                    </div>

                    @if($currentTerm->isLastTerm())
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <strong>Important:</strong> Migrating from Third Term to First Term will:
                            </p>
                            <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 mt-2 space-y-1">
                                <li>Start a new academic session</li>
                                <li>Promote all students to the next class</li>
                                <li>Mark students in terminal classes (Year 6) as graduated</li>
                            </ul>
                        </div>
                    @endif

                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            <strong>Term Progression Rules:</strong>
                        </p>
                        <ul class="list-disc list-inside text-sm text-blue-700 dark:text-blue-300 mt-2 space-y-1">
                            <li>First Term → Second Term only</li>
                            <li>Second Term → Third Term only</li>
                            <li>Third Term → First Term only (starts new session)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-2xl font-bold mb-4">Recent Migrations</h2>
                
                @php
                    $recentMigrations = \App\Models\TermMigrationLog::with(['oldTerm', 'newTerm', 'migratedBy'])
                        ->latest('migrated_at')
                        ->take(5)
                        ->get();
                @endphp

                @if($recentMigrations->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">From</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">To</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Promoted</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Graduated</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">By</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($recentMigrations as $migration)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $migration->migrated_at->format('M d, Y H:i') }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $migration->old_term_name }}</td>
                                        <td class="px-4 py-2 text-sm font-semibold text-green-600">{{ $migration->new_term_name }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $migration->students_promoted }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $migration->students_graduated }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $migration->migratedBy->name ?? 'N/A' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-gray-500 dark:text-gray-400">No migration history available.</p>
                @endif
            </div>
        @else
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                <p class="text-red-800 dark:text-red-200">
                    No current term found. Please create a term and mark it as current before attempting migration.
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
