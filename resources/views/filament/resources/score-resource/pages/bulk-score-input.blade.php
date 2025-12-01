<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Select Session, Term, Class and Subject
            </x-slot>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="academicSessionId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Academic Session
                    </label>
                    <select 
                        wire:model.live="academicSessionId" 
                        id="academicSessionId"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">Select a session</option>
                        @foreach(\App\Models\AcademicSession::orderBy('name', 'desc')->get() as $session)
                            <option value="{{ $session->id }}">{{ $session->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="termId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Term
                    </label>
                    <select 
                        wire:model.live="termId" 
                        id="termId"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">Select a term</option>
                        @foreach(\App\Models\Term::orderBy('name')->get() as $term)
                            <option value="{{ $term->id }}">{{ $term->name }} ({{ $term->academicSession->name ?? '' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="classroomId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Classroom
                    </label>
                    <select 
                        wire:model.live="classroomId" 
                        id="classroomId"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">Select a classroom</option>
                        @foreach(\App\Models\Classroom::pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="subjectId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Subject
                    </label>
                    <select 
                        wire:model.live="subjectId" 
                        id="subjectId"
                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    >
                        <option value="">Select a subject</option>
                        @foreach(\App\Models\Subject::pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-filament::section>

        @if($students && count($students) > 0)
            <div class="mt-6">
                <x-filament::section>
                    <x-slot name="heading">
                        Student Scores
                    </x-slot>

                    <x-slot name="description">
                        Enter scores for each student. Maximum scores are shown for each assessment type.
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider sticky left-0 bg-gray-50 dark:bg-gray-800">
                                        Student Name
                                    </th>
                                    @foreach($assessmentTypes as $assessmentType)
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            {{ $assessmentType->name }}
                                            <span class="block text-xs font-normal text-gray-400">
                                                (Max: {{ $assessmentType->max_score }})
                                            </span>
                                        </th>
                                    @endforeach
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($students as $student)
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 sticky left-0 bg-white dark:bg-gray-900">
                                            {{ $student->first_name }} {{ $student->last_name }}
                                        </td>
                                        @php
                                            $total = 0;
                                        @endphp
                                        @foreach($assessmentTypes as $assessmentType)
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <input
                                                    type="number"
                                                    wire:model="scores.{{ $student->id }}.{{ $assessmentType->id }}"
                                                    min="0"
                                                    max="{{ $assessmentType->max_score }}"
                                                    step="0.01"
                                                    class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                                                    placeholder="0"
                                                />
                                                @php
                                                    $scoreValue = $scores[$student->id][$assessmentType->id] ?? 0;
                                                    $total += (float) $scoreValue;
                                                @endphp
                                            </td>
                                        @endforeach
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">
                                            {{ number_format($total, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-filament::section>

                <form wire:submit="save">
                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit">
                            Save Scores
                        </x-filament::button>
                    </div>
                </form>
            </div>
        @elseif($classroomId && $subjectId)
            <x-filament::section>
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No students found in this classroom.
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
