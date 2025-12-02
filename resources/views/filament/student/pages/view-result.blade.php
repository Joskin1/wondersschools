<x-filament-panels::page>
    <div class="space-y-6">
        @if($results->isEmpty())
            <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                <p class="text-gray-500 dark:text-gray-400">No results found.</p>
            </div>
        @else
            @foreach($results as $result)
                <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 print:shadow-none print:border print:border-gray-200">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $result->academicSession->name }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $result->term->name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Class: {{ $result->classroom->name }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total Score</p>
                            <p class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $result->total_score }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Average</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $result->average_score }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Position</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $result->position }}</p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-700">
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">Grade</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $result->grade }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                        <div class="mb-4">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Teacher's Remark</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $result->teacher_remark }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Principal's Remark</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300">{{ $result->principal_remark }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end print:hidden">
                        <x-filament::button
                            tag="button"
                            type="button"
                            onclick="window.print()"
                        >
                            Print Result
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .fi-main-content, .fi-main-content * {
                visibility: visible;
            }
            .fi-main-content {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .fi-sidebar, .fi-topbar {
                display: none;
            }
        }
    </style>
</x-filament-panels::page>
