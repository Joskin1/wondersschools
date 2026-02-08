<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            My Classroom
        </x-slot>

        @if($classroom)
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Classroom:</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $classroom->name }}</span>
                </div>
                
                @if($classroom->description)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Description:</span>
                        <span class="text-sm text-gray-900 dark:text-white">{{ $classroom->description }}</span>
                    </div>
                @endif
                
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Students:</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white">{{ $studentCount }}</span>
                </div>
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400">
                You are not currently assigned to a classroom.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
