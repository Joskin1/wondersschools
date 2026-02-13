<div class="fi-wi-classroom-filter">
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="p-4">
            <div class="flex items-center gap-4">
                <!-- Dropdown Filter -->
                <div class="flex-1">
                    <select 
                        wire:model.live="selectedClassroom"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white sm:text-sm"
                    >
                        <option value="">All Classes</option>
                        @foreach($this->getClassrooms() as $classroom)
                            <option value="{{ $classroom->id }}">
                                {{ $classroom->name }}
                                @if(isset($classroom->assignments_count) && $classroom->assignments_count > 0)
                                    ({{ $classroom->assignments_count }} {{ Str::plural('teacher', $classroom->assignments_count) }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Clear Button -->
                @if($selectedClassroom)
                    <button 
                        wire:click="clearFilter"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('classroom-filter-updated', (event) => {
        Livewire.dispatch('refreshTable');
    });
</script>
@endscript
