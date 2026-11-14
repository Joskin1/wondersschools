<x-filament-panels::page>
    <style>
        .sln-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .sln-card { background: #131b2e; border-color: rgba(148,163,184,0.12); }
        .sln-subject-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.15s ease; border: 1px solid transparent; cursor: pointer; }
        .sln-subject-active { background: #4f46e5; color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); }
        .sln-subject-inactive { background: rgba(148,163,184,0.1); color: #475569; }
        .sln-subject-inactive:hover { background: rgba(148,163,184,0.2); color: #0f172a; }
        .dark .sln-subject-inactive { background: rgba(255,255,255,0.05); color: #94a3b8; }
        .dark .sln-subject-inactive:hover { background: rgba(255,255,255,0.1); color: #f1f5f9; }
        .sln-badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 800; border-radius: 9999px; line-height: 1; }
        .sln-badge-active { background: rgba(255,255,255,0.25); color: white; }
        .sln-badge-inactive { background: rgba(100,116,139,0.15); color: #475569; }
        .dark .sln-badge-inactive { background: rgba(255,255,255,0.15); color: #cbd5e1; }
        .sln-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.625rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s ease; text-decoration: none; }
        .sln-btn-primary { background: #4f46e5; color: white; }
        .sln-btn-primary:hover { background: #4338ca; }
        .sln-btn-outline { background: white; color: #475569; border: 1px solid #cbd5e1; }
        .sln-btn-outline:hover { background: #f8fafc; color: #0f172a; }
        .dark .sln-btn-outline { background: #1e293b; color: #cbd5e1; border-color: rgba(148,163,184,0.2); }
        .dark .sln-btn-outline:hover { background: #334155; color: white; }
        .sln-week-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.25rem; transition: all 0.2s ease; display: flex; flex-col sm:flex-row items-start sm:items-center justify-between gap-4; }
        .sln-week-card:hover { border-color: #6366f1; box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.08); }
        .dark .sln-week-card { background: #131b2e; border-color: rgba(148,163,184,0.12); }
        .dark .sln-week-card:hover { border-color: #6366f1; }
        .sln-modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 1rem; }
        .sln-modal-box { background: white; border-radius: 1.25rem; max-width: 52rem; width: 100%; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; }
        .dark .sln-modal-box { background: #0f172a; border: 1px solid rgba(148,163,184,0.15); color: #f8fafc; }
        .sln-icon-3, .sln-icon-4, .sln-icon-5, .sln-icon-6, .sln-icon-10 { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sln-icon-3, .sln-icon-3 svg { width: 0.75rem; height: 0.75rem; }
        .sln-icon-4, .sln-icon-4 svg { width: 1rem; height: 1rem; }
        .sln-icon-5, .sln-icon-5 svg { width: 1.25rem; height: 1.25rem; }
        .sln-icon-6, .sln-icon-6 svg { width: 1.5rem; height: 1.5rem; }
        .sln-icon-10, .sln-icon-10 svg { width: 2.5rem; height: 2.5rem; }
        .sln-page-wrap svg { max-width: 1.5rem; max-height: 1.5rem; }
        .sln-page-wrap .sln-icon-10 svg { max-width: 2.5rem; max-height: 2.5rem; }
        .sln-note-body { color: #374151; font-size: 0.9375rem; line-height: 1.8; }
        .dark .sln-note-body { color: #cbd5e1; }
        .sln-note-body p { margin-bottom: 0.875rem; }
        .sln-note-body h1, .sln-note-body h2, .sln-note-body h3 { font-weight: 800; margin-top: 1.25rem; margin-bottom: 0.5rem; color: #111827; }
        .dark .sln-note-body h1, .dark .sln-note-body h2, .dark .sln-note-body h3 { color: #f8fafc; }
        .sln-note-body ul, .sln-note-body ol { margin-left: 1.5rem; margin-bottom: 1rem; }
        .sln-note-body ul { list-style-type: disc; }
        .sln-note-body ol { list-style-type: decimal; }
        .sln-note-body table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.875rem; }
        .sln-note-body th, .sln-note-body td { border: 1px solid #cbd5e1; padding: 0.5rem 0.75rem; }
        .dark .sln-note-body th, .dark .sln-note-body td { border-color: #334155; }
        .sln-note-body th { background: #f1f5f9; font-weight: 700; }
        .dark .sln-note-body th { background: #1e293b; }
    </style>

    @php
        $subjects = $this->getSubjects();
        $weeklyNotes = $this->getWeeklyNotes();
        $selectedSubject = $this->getSelectedSubject();
        $viewingNote = $this->getViewingNote();
    @endphp

    <div class="sln-page-wrap space-y-6">
        <!-- ── Description Header Banner ───────────────────────────────────── -->
        <div class="sln-card">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <span class="sln-icon-6"><x-heroicon-o-book-open /></span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Class Lesson Notes</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Select a subject below to view and study available weekly lesson notes approved by the administration.
                        </p>
                    </div>
                </div>

                <!-- Dropdown selector on smaller screens -->
                <div class="sm:hidden w-full">
                    <select
                        wire:change="selectSubject($event.target.value)"
                        class="w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-slate-900 text-sm font-semibold text-gray-800 dark:text-gray-200"
                    >
                        @foreach($subjects as $subj)
                            <option value="{{ $subj->id }}" {{ $selectedSubjectId === $subj->id ? 'selected' : '' }}>
                                {{ $subj->name }} ({{ $subj->approved_notes_count }} {{ \Illuminate\Support\Str::plural('week', $subj->approved_notes_count) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- ── Subject Selection Pills ──────────────────────────────────────── -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                    Choose Subject:
                </span>
                <span class="text-xs text-gray-400">
                    {{ $subjects->count() }} subjects offered
                </span>
            </div>

            <div class="flex flex-wrap gap-2">
                @forelse($subjects as $subj)
                    <button
                        type="button"
                        wire:click="selectSubject({{ $subj->id }})"
                        class="sln-subject-btn {{ $selectedSubjectId === $subj->id ? 'sln-subject-active' : 'sln-subject-inactive' }}"
                    >
                        <span>{{ $subj->name }}</span>
                        <span class="sln-badge {{ $selectedSubjectId === $subj->id ? 'sln-badge-active' : 'sln-badge-inactive' }}">
                            {{ $subj->approved_notes_count }}
                        </span>
                    </button>
                @empty
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-slate-800/50 text-xs text-gray-500 text-center w-full">
                        No subjects found for your class.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- ── Weekly Lesson Notes List for Selected Subject ────────────────── -->
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-extrabold text-gray-900 dark:text-white">
                        {{ $selectedSubject?->name ?? 'Select a Subject' }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        • {{ $weeklyNotes->count() }} {{ \Illuminate\Support\Str::plural('note', $weeklyNotes->count()) }} available
                    </span>
                </div>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800/60">
                    ✓ Approved Notes Only
                </span>
            </div>

            @if($weeklyNotes->isNotEmpty())
                <div class="grid grid-cols-1 gap-3">
                    @foreach($weeklyNotes as $note)
                        @php
                            $version = $note->latestVersion;
                            $isWritten = $version?->isWritten();
                            $topicTitle = $isWritten 
                                ? ($version->title ?: 'Written Lesson Note')
                                : ($version->file_name ?: 'Lesson Note Document');
                        @endphp
                        <div class="sln-week-card">
                            <div class="flex items-start gap-4">
                                <!-- Week Badge -->
                                <div class="shrink-0 text-center">
                                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800/80 flex flex-col items-center justify-center text-indigo-700 dark:text-indigo-300">
                                        <span class="text-[10px] font-extrabold uppercase tracking-wider text-indigo-500 dark:text-indigo-400">Week</span>
                                        <span class="text-xl font-black leading-none">{{ $note->week_number }}</span>
                                    </div>
                                </div>

                                <!-- Note Information -->
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white leading-snug">
                                            {{ $topicTitle }}
                                        </h3>
                                        @if($isWritten)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 px-2 py-0.5 rounded-md border border-violet-200 dark:border-violet-800/50">
                                                📝 Written Note
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 px-2 py-0.5 rounded-md border border-blue-200 dark:border-blue-800/50">
                                                📄 Document
                                            </span>
                                        @endif
                                    </div>

                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                        <span>Teacher: <strong class="text-gray-700 dark:text-gray-300">{{ $note->teacher?->full_name ?? '—' }}</strong></span>
                                        <span>•</span>
                                        <span>Published: <strong class="text-gray-700 dark:text-gray-300">{{ $version?->reviewed_at ? $version->reviewed_at->format('M d, Y') : ($note->updated_at ? $note->updated_at->format('M d, Y') : '—') }}</strong></span>
                                        @if(!empty($note->learning_objectives) && is_array($note->learning_objectives))
                                            <span>•</span>
                                            <span>{{ count($note->learning_objectives) }} {{ \Illuminate\Support\Str::plural('objective', count($note->learning_objectives)) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-end pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 dark:border-gray-800">
                                <button
                                    type="button"
                                    wire:click="viewNote({{ $note->id }})"
                                    class="sln-btn sln-btn-primary"
                                >
                                    <span class="sln-icon-4"><x-heroicon-m-book-open /></span>
                                    Read Note
                                </button>

                                <a
                                    href="{{ $version?->isFile() ? route('tenant.lesson-note.file', ['version' => $version->id, 'download' => 1]) : route('student.lesson-note.pdf', $note) }}"
                                    target="_blank"
                                    class="sln-btn sln-btn-outline"
                                    title="Download Lesson Note"
                                >
                                    <span class="sln-icon-4"><x-heroicon-m-arrow-down-tray /></span>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Clean Empty State when this subject has no approved notes -->
                <div class="sln-card text-center py-12">
                    <span class="sln-icon-10" style="margin: 0 auto 0.75rem; color: #94a3b8;"><x-heroicon-o-document-text /></span>
                    <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">
                        No approved lesson notes for {{ $selectedSubject?->name ?? 'this subject' }} yet
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 max-w-md mx-auto mt-1">
                        Only weeks with notes approved by the administration appear here. Once your teacher submits notes and they are approved, they will be listed week-by-week.
                    </p>
                </div>
            @endif
        </div>
    </div>

    <!-- ── NOTE READER MODAL ────────────────────────────────────────────────── -->
    @if($showViewModal && $viewingNote)
        @php
            $v = $viewingNote->latestVersion;
            $isWrittenV = $v?->isWritten();
            $downloadUrl = $v?->isFile() 
                ? route('tenant.lesson-note.file', ['version' => $v->id, 'download' => 1]) 
                : route('student.lesson-note.pdf', $viewingNote);
        @endphp
        <div class="sln-modal-overlay">
            <div class="sln-modal-box">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-slate-900/50">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded-md text-xs font-black bg-indigo-600 text-white">
                                WEEK {{ $viewingNote->week_number }}
                            </span>
                            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                                {{ $viewingNote->subject?->name }} • {{ $viewingNote->classroom?->name }}
                            </span>
                        </div>
                        <h2 class="text-lg font-extrabold text-gray-900 dark:text-white mt-1">
                            {{ $isWrittenV ? ($v->title ?: 'Written Lesson Note') : ($v->file_name ?: 'Lesson Note Document') }}
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Teacher: {{ $viewingNote->teacher?->full_name ?? '—' }} • Approved by Administration
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <a
                            href="{{ $downloadUrl }}"
                            target="_blank"
                            class="sln-btn sln-btn-primary text-xs py-1.5 px-3"
                        >
                            <span class="sln-icon-4"><x-heroicon-m-arrow-down-tray /></span>
                            Download {{ $v?->isFile() ? 'File' : 'PDF' }}
                        </a>

                        <button
                            type="button"
                            wire:click="closeViewModal"
                            class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800"
                        >
                            <span class="sln-icon-5"><x-heroicon-m-x-mark /></span>
                        </button>
                    </div>
                </div>

                <!-- Modal Scrollable Content -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1">
                    <!-- Learning Objectives -->
                    @if(!empty($viewingNote->learning_objectives) && is_array($viewingNote->learning_objectives))
                        <div class="p-4 rounded-xl bg-indigo-50/60 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-indigo-900 dark:text-indigo-300 mb-2">
                                🎯 Learning Objectives
                            </h4>
                            <p class="text-xs text-indigo-700 dark:text-indigo-400 mb-2">
                                At the end of this lesson, you should be able to:
                            </p>
                            <ul class="list-disc pl-5 space-y-1 text-xs text-gray-800 dark:text-gray-200">
                                @foreach($viewingNote->learning_objectives as $obj)
                                    <li>{{ is_array($obj) ? ($obj['objective'] ?? '') : $obj }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Note Content -->
                    @if($isWrittenV)
                        <div class="sln-note-body">
                            {!! $v->content !!}
                        </div>
                    @else
                        <!-- File Uploaded Document Preview -->
                        <div class="p-6 rounded-xl border border-gray-200 dark:border-gray-800 text-center space-y-3 bg-gray-50 dark:bg-slate-800/40">
                            <span class="sln-icon-10" style="margin: 0 auto; color: #6366f1;"><x-heroicon-o-document-arrow-down /></span>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $v->file_name ?: 'Lesson Note Document' }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $v->formatted_file_size ?? '' }}
                                </p>
                            </div>
                            <div class="pt-2">
                                <a
                                    href="{{ $downloadUrl }}"
                                    target="_blank"
                                    class="sln-btn sln-btn-primary text-xs py-2 px-4"
                                >
                                    <span class="sln-icon-4"><x-heroicon-m-arrow-down-tray /></span>
                                    Open / Download Attached Document
                                </a>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="flex items-center justify-between px-6 py-3 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-slate-900/50">
                    <span class="text-xs text-gray-400">
                        Lesson Note for Student Study
                    </span>
                    <button
                        type="button"
                        wire:click="closeViewModal"
                        class="sln-btn sln-btn-outline text-xs"
                    >
                        Close Note
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
