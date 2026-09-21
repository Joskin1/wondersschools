@php
    use App\Services\LessonSubmissionService;

    $record = $getRecord();
    $status = null;

    if ($record) {
        $svc = app(LessonSubmissionService::class);
        $status = $svc->getSubmissionStatus(
            $record->teacher_id,
            $record->subject_id,
            $record->classroom_id,
            $record->session_id,
            $record->term_id,
            $record->week_number,
        );
    }
@endphp

@if($status)
    @php
        $noteStatus = $status['note_status'];
        $planStatus = $status['plan_status'];
        $hasNote = $status['has_note'];
        $hasPlan = $status['has_plan'];
        $bothSubmitted = in_array($noteStatus, ['pending', 'approved']) && in_array($planStatus, ['pending', 'approved']);
        $bothReadyToSubmit = $hasNote && $hasPlan && in_array($planStatus, ['draft', 'rejected']);
    @endphp

    <div class="rounded-xl border p-4 {{ $bothSubmitted ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : ($bothReadyToSubmit ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20') }}">
        <div class="flex items-start gap-3">
            @if($bothSubmitted)
                <x-heroicon-o-check-circle class="mt-0.5 h-5 w-5 shrink-0 text-green-500" />
            @elseif($bothReadyToSubmit)
                <x-heroicon-o-information-circle class="mt-0.5 h-5 w-5 shrink-0 text-blue-500" />
            @else
                <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
            @endif

            <div class="flex-1">
                <p class="text-sm font-semibold {{ $bothSubmitted ? 'text-green-800 dark:text-green-300' : ($bothReadyToSubmit ? 'text-blue-800 dark:text-blue-300' : 'text-amber-800 dark:text-amber-300') }}">
                    @if($bothSubmitted)
                        Submission Sent ({{ ucfirst($planStatus) }})
                    @elseif($bothReadyToSubmit)
                        Ready for Submission
                    @else
                        Incomplete Submission
                    @endif
                </p>

                <div class="mt-2 flex flex-wrap gap-4 text-xs">
                    <span class="flex items-center gap-1 {{ $hasNote ? 'text-gray-700 dark:text-gray-300' : 'text-red-600 dark:text-red-400' }}">
                        @if($hasNote)
                            <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-green-500" />
                        @else
                            <x-heroicon-m-x-circle class="h-3.5 w-3.5 text-red-500" />
                        @endif
                        Lesson Note: <strong>{{ $hasNote ? ucfirst($noteStatus) : 'Not Created' }}</strong>
                    </span>

                    <span class="flex items-center gap-1 {{ $hasPlan ? 'text-gray-700 dark:text-gray-300' : 'text-red-600 dark:text-red-400' }}">
                        @if($hasPlan)
                            <x-heroicon-m-check-circle class="h-3.5 w-3.5 text-green-500" />
                        @else
                            <x-heroicon-m-x-circle class="h-3.5 w-3.5 text-red-500" />
                        @endif
                        Lesson Plan: <strong>{{ $hasPlan ? ucfirst($planStatus) : 'Not Created' }}</strong>
                    </span>
                </div>

                @if(!$hasNote)
                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                        A Lesson Note for Week {{ $record->week_number }} has not been created yet. Please create and save it before submitting for review.
                    </p>
                @elseif($bothReadyToSubmit)
                    <p class="mt-2 text-xs text-blue-700 dark:text-blue-400">
                        Both documents are saved. Click <strong>Submit for Review</strong> to send both to the admin for review.
                    </p>
                @elseif($bothSubmitted)
                    <p class="mt-2 text-xs text-green-700 dark:text-green-400">
                        Both documents are in review. The admin has been notified.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endif
