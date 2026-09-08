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
        $noteReady = !$status['missing_note'];
        $planReady = !$status['missing_plan'];
        $bothReady = $noteReady && $planReady;
    @endphp

    <div class="rounded-xl border p-4 {{ $bothReady ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' }}">
        <div class="flex items-start gap-3">
            @if($bothReady)
                <x-heroicon-o-check-circle class="mt-0.5 h-5 w-5 shrink-0 text-green-500" />
            @else
                <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" />
            @endif

            <div class="flex-1">
                <p class="text-sm font-semibold {{ $bothReady ? 'text-green-800 dark:text-green-300' : 'text-amber-800 dark:text-amber-300' }}">
                    {{ $bothReady ? 'Submission Complete' : 'Submission Incomplete' }}
                </p>

                <div class="mt-2 flex flex-wrap gap-4 text-xs">
                    <span class="flex items-center gap-1 {{ $noteReady ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        @if($noteReady)
                            <x-heroicon-m-check-circle class="h-3.5 w-3.5" />
                        @else
                            <x-heroicon-m-x-circle class="h-3.5 w-3.5" />
                        @endif
                        Lesson Note: <strong>{{ $noteReady ? ucfirst($status['note_status']) : 'Not Ready' }}</strong>
                    </span>

                    <span class="flex items-center gap-1 {{ $planReady ? 'text-green-700 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        @if($planReady)
                            <x-heroicon-m-check-circle class="h-3.5 w-3.5" />
                        @else
                            <x-heroicon-m-x-circle class="h-3.5 w-3.5" />
                        @endif
                        Lesson Plan: <strong>{{ $planReady ? ucfirst($status['plan_status']) : 'Not Ready' }}</strong>
                    </span>
                </div>

                @if(!$bothReady)
                    <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">
                        Your lesson submission is incomplete. Please complete and submit both the
                        Lesson Note and Lesson Plan before the admin can review them.
                    </p>
                @else
                    <p class="mt-2 text-xs text-green-700 dark:text-green-400">
                        Both documents are ready. The admin has been notified to review your submission.
                    </p>
                @endif
            </div>
        </div>
    </div>
@endif
