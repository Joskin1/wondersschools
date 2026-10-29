<div class="space-y-4">
    <div class="flex items-center gap-4 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700">
        <div class="relative">
            @if ($student->profile_picture && \Illuminate\Support\Facades\Storage::disk('public')->exists($student->profile_picture))
                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($student->profile_picture) }}"
                     alt="{{ $student->full_name }}"
                     class="w-16 h-16 rounded-full object-cover border-2 border-primary-500 shadow-sm" />
            @else
                <div class="w-16 h-16 rounded-full bg-primary-100 dark:bg-primary-900/50 flex items-center justify-center text-primary-600 dark:text-primary-300 font-bold text-xl border-2 border-primary-500">
                    {{ strtoupper(substr($student->first_name ?: $student->full_name, 0, 1)) }}
                </div>
            @endif
        </div>
        <div class="flex-1">
            <h4 class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $student->full_name }}</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                <span class="font-mono font-medium">{{ $student->admission_number ?? 'No Admission Number' }}</span>
                @if ($student->currentEnrollment()?->classroom)
                    &bull; {{ $student->currentEnrollment()->classroom->name }}
                @endif
            </p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Primary Fingerprint</span>
            <div class="mt-1 flex items-center gap-2">
                @if ($student->primary_biometric_template)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Captured
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        Missing
                    </span>
                @endif
            </div>
        </div>

        <div class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Backup Fingerprint</span>
            <div class="mt-1 flex items-center gap-2">
                @if ($student->backup_biometric_template)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Captured
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                        Optional / None
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="p-3 rounded-lg bg-blue-50/60 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900 text-xs text-blue-900 dark:text-blue-200 leading-relaxed">
        <strong>Central Identity Scope:</strong> This biometric fingerprint template is stored as part of the student's personal identity profile. It can be used across school services including attendance, campus gate checkpoints, computer-based testing (CBT) / examination hall clearance, and library authentication.
    </div>

    @if ($student->biometric_enrolled_at)
        <p class="text-xs text-gray-500 dark:text-gray-400 text-right">
            Enrolled on {{ $student->biometric_enrolled_at->format('M d, Y \a\t h:i A') }}
        </p>
    @endif
</div>
