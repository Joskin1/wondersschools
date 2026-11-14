<x-filament-panels::page>
    <style>
        .sln-container { display: flex; flex-direction: column; gap: 1.5rem; }
        .sln-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .sln-card { background: #131b2e; border-color: rgba(148,163,184,0.12); }
        .sln-subject-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.15s ease; border: 1px solid transparent; cursor: pointer; text-decoration: none; }
        .sln-subject-active { background: #4f46e5; color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); }
        .sln-subject-inactive { background: rgba(148,163,184,0.1); color: #475569; }
        .sln-subject-inactive:hover { background: rgba(148,163,184,0.2); color: #0f172a; }
        .dark .sln-subject-inactive { background: rgba(255,255,255,0.05); color: #94a3b8; }
        .dark .sln-subject-inactive:hover { background: rgba(255,255,255,0.1); color: #f1f5f9; }
        .sln-badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 800; border-radius: 9999px; line-height: 1; }
        .sln-badge-active { background: rgba(255,255,255,0.25); color: white; }
        .sln-badge-inactive { background: rgba(100,116,139,0.15); color: #475569; }
        .dark .sln-badge-inactive { background: rgba(255,255,255,0.15); color: #cbd5e1; }
        .sln-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 0.625rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s ease; text-decoration: none; border: none; }
        .sln-btn-primary { background: #4f46e5; color: white; }
        .sln-btn-primary:hover { background: #4338ca; }
        .sln-btn-outline { background: white; color: #475569; border: 1px solid #cbd5e1; }
        .sln-btn-outline:hover { background: #f8fafc; color: #0f172a; }
        .dark .sln-btn-outline { background: #1e293b; color: #cbd5e1; border-color: rgba(148,163,184,0.2); }
        .dark .sln-btn-outline:hover { background: #334155; color: white; }

        /* Master-Detail Layout */
        .sln-workspace { display: grid; grid-template-columns: 1fr; gap: 1.5rem; align-items: start; }
        @media (min-width: 1024px) {
            .sln-workspace { grid-template-columns: 280px minmax(0, 1fr); }
        }

        /* Weeks Sidebar */
        .sln-week-nav { display: flex; flex-direction: column; gap: 0.5rem; }
        .sln-week-item { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.875rem 1rem; border-radius: 0.75rem; border: 1px solid rgba(148,163,184,0.2); background: white; text-align: left; cursor: pointer; transition: all 0.15s ease; width: 100%; }
        .sln-week-item:hover { border-color: #6366f1; background: #f8fafc; }
        .dark .sln-week-item { background: #131b2e; border-color: rgba(148,163,184,0.12); }
        .dark .sln-week-item:hover { background: #1e293b; border-color: #6366f1; }
        .sln-week-item-active { border-color: #4f46e5 !important; background: #eef2ff !important; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12); }
        .dark .sln-week-item-active { background: #1e1b4b !important; border-color: #6366f1 !important; }

        /* Full Reader Document */
        .sln-doc-sheet { background: white; border-radius: 1.25rem; border: 1px solid rgba(148,163,184,0.2); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.05); padding: 2rem 2.5rem; }
        @media (max-width: 640px) {
            .sln-doc-sheet { padding: 1.25rem 1rem; }
        }
        .dark .sln-doc-sheet { background: #131b2e; border-color: rgba(148,163,184,0.12); color: #f8fafc; }

        /* Document Typography */
        .sln-doc-body { font-size: 0.9375rem; line-height: 1.85; color: #2d3748; }
        .dark .sln-doc-body { color: #cbd5e1; }
        .sln-doc-body p { margin-bottom: 1.25rem; }
        .sln-doc-body div { margin-bottom: 0.875rem; }
        .sln-doc-body h1, .sln-doc-body h2, .sln-doc-body h3, .sln-doc-body h4 { font-weight: 800; color: #0f172a; line-height: 1.35; margin-top: 1.75rem; margin-bottom: 0.75rem; }
        .dark .sln-doc-body h1, .dark .sln-doc-body h2, .dark .sln-doc-body h3, .dark .sln-doc-body h4 { color: #f8fafc; }
        .sln-doc-body h1 { font-size: 1.375rem; }
        .sln-doc-body h2 { font-size: 1.2rem; }
        .sln-doc-body h3 { font-size: 1.05rem; }
        .sln-doc-body ul, .sln-doc-body ol { margin-left: 1.75rem; margin-bottom: 1.25rem; }
        .sln-doc-body ul { list-style-type: disc; }
        .sln-doc-body ol { list-style-type: decimal; }
        .sln-doc-body li { margin-bottom: 0.5rem; padding-left: 0.25rem; }
        .sln-doc-body table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; font-size: 0.875rem; }
        .sln-doc-body th, .sln-doc-body td { border: 1px solid #cbd5e1; padding: 0.625rem 0.875rem; text-align: left; }
        .dark .sln-doc-body th, .dark .sln-doc-body td { border-color: #334155; }
        .sln-doc-body th { background: #f8fafc; font-weight: 700; color: #0f172a; }
        .dark .sln-doc-body th { background: #1e293b; color: #f8fafc; }
        .sln-doc-body blockquote { border-left: 4px solid #6366f1; padding-left: 1rem; margin: 1.25rem 0; font-style: italic; color: #475569; }
        .dark .sln-doc-body blockquote { color: #94a3b8; }

        /* Icon Constraints */
        .sln-icon-3, .sln-icon-4, .sln-icon-5, .sln-icon-6, .sln-icon-10 { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .sln-icon-3, .sln-icon-3 svg { width: 0.75rem; height: 0.75rem; }
        .sln-icon-4, .sln-icon-4 svg { width: 1rem; height: 1rem; }
        .sln-icon-5, .sln-icon-5 svg { width: 1.25rem; height: 1.25rem; }
        .sln-icon-6, .sln-icon-6 svg { width: 1.5rem; height: 1.5rem; }
        .sln-icon-10, .sln-icon-10 svg { width: 2.5rem; height: 2.5rem; }
        .sln-page-wrap svg { max-width: 1.5rem; max-height: 1.5rem; }
        .sln-page-wrap .sln-icon-10 svg { max-width: 2.5rem; max-height: 2.5rem; }
    </style>

    @php
        $subjects = $this->getSubjects();
        $weeklyNotes = $this->getWeeklyNotes();
        $selectedSubject = $this->getSelectedSubject();
        $activeNote = $this->getSelectedNote();
    @endphp

    <div class="sln-page-wrap sln-container">
        <!-- ── Top Header Banner ───────────────────────────────────────────── -->
        <div class="sln-card">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <span class="sln-icon-6"><x-heroicon-o-book-open /></span>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Class Lesson Notes</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Select a subject below to study weekly lesson notes approved by the administration.
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

        <!-- ── Main Workspace: Available Weeks (Left) + Document Reader (Right) ─ -->
        @if($weeklyNotes->isNotEmpty())
            <div class="sln-workspace">
                <!-- ── LEFT: Available Weeks Sidebar ────────────────────────── -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between pb-1 border-b border-gray-200 dark:border-gray-800">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            Available Weeks
                        </span>
                        <span class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full">
                            {{ $weeklyNotes->count() }} Approved
                        </span>
                    </div>

                    <div class="sln-week-nav">
                        @foreach($weeklyNotes as $note)
                            @php
                                $v = $note->latestVersion;
                                $isNoteActive = $activeNote && $activeNote->id === $note->id;
                                $itemTitle = $v?->isWritten() 
                                    ? ($v->title ?: 'Written Lesson Note')
                                    : ($v->file_name ?: 'Lesson Note Document');
                            @endphp
                            <button
                                type="button"
                                wire:click="selectNote({{ $note->id }})"
                                class="sln-week-item {{ $isNoteActive ? 'sln-week-item-active' : '' }}"
                            >
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 font-extrabold flex flex-col items-center justify-center shrink-0 border border-indigo-200 dark:border-indigo-800/80">
                                    <span class="text-[9px] uppercase tracking-wider leading-none">Wk</span>
                                    <span class="text-sm font-black leading-tight">{{ $note->week_number }}</span>
                                </div>
                                <div class="overflow-hidden flex-1">
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate">
                                        {{ $itemTitle }}
                                    </h4>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 truncate">
                                        {{ $note->teacher?->full_name ?? '—' }}
                                    </p>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- ── RIGHT: Full Academic Document Reader ─────────────────── -->
                <div class="space-y-4">
                    @if($activeNote)
                        @php
                            $v = $activeNote->latestVersion;
                            $isWritten = $v?->isWritten();
                            $noteTitle = $isWritten 
                                ? ($v->title ?: 'Written Lesson Note')
                                : ($v->file_name ?: 'Lesson Note Document');
                            $downloadUrl = $v?->isFile() 
                                ? route('tenant.lesson-note.file', ['version' => $v->id, 'download' => 1]) 
                                : route('student.lesson-note.pdf', $activeNote);
                        @endphp

                        <div class="sln-doc-sheet space-y-6">
                            <!-- Document Top Action & Context Bar -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-5 border-b border-gray-200 dark:border-gray-800">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-indigo-600 text-white tracking-wider">
                                        WEEK {{ $activeNote->week_number }}
                                    </span>
                                    <span class="text-xs font-extrabold uppercase tracking-wide text-gray-700 dark:text-gray-300">
                                        {{ $activeNote->subject?->name }}
                                    </span>
                                    <span class="text-xs text-gray-400">•</span>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                        {{ $activeNote->classroom?->name }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a
                                        href="{{ $downloadUrl }}"
                                        target="_blank"
                                        class="sln-btn sln-btn-primary shadow-sm text-xs"
                                        title="Download Full Lesson Note PDF or File"
                                    >
                                        <span class="sln-icon-4"><x-heroicon-m-arrow-down-tray /></span>
                                        <span>Download {{ $v?->isFile() ? 'File' : 'PDF' }}</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Document Masthead / Title -->
                            <div class="space-y-2">
                                <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight leading-snug">
                                    {{ $noteTitle }}
                                </h1>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                                    <span>Prepared by: <strong class="text-gray-700 dark:text-gray-200">{{ $activeNote->teacher?->full_name ?? '—' }}</strong></span>
                                    <span>•</span>
                                    <span>Status: <span class="text-emerald-600 dark:text-emerald-400 font-semibold">Approved by Administration</span></span>
                                    <span>•</span>
                                    <span>Published: <strong class="text-gray-700 dark:text-gray-200">{{ $v?->reviewed_at ? $v->reviewed_at->format('M d, Y') : ($activeNote->updated_at ? $activeNote->updated_at->format('M d, Y') : '—') }}</strong></span>
                                </div>
                            </div>

                            <!-- Learning Objectives Callout Box -->
                            @if(!empty($activeNote->learning_objectives) && is_array($activeNote->learning_objectives))
                                <div class="p-4 sm:p-5 rounded-2xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/60 space-y-2">
                                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-indigo-900 dark:text-indigo-300 flex items-center gap-1.5">
                                        <span>🎯</span> Learning Objectives
                                    </h4>
                                    <p class="text-xs text-indigo-700 dark:text-indigo-400">
                                        At the end of this lesson, you should be able to:
                                    </p>
                                    <ul class="list-disc pl-5 space-y-1 text-xs text-gray-800 dark:text-gray-200">
                                        @foreach($activeNote->learning_objectives as $obj)
                                            <li>{{ is_array($obj) ? ($obj['objective'] ?? '') : $obj }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Divider -->
                            <div class="border-t border-gray-100 dark:border-gray-800"></div>

                            <!-- Full Lesson Note Body (100% Unclipped Content) -->
                            @if($isWritten)
                                <div class="sln-doc-body">
                                    {!! $v->content !!}
                                </div>
                            @else
                                <div class="p-8 rounded-2xl border border-gray-200 dark:border-gray-800 text-center space-y-4 bg-gray-50/50 dark:bg-slate-800/40">
                                    <span class="sln-icon-10" style="margin: 0 auto; color: #4f46e5;"><x-heroicon-o-document-arrow-down /></span>
                                    <div class="space-y-1">
                                        <h4 class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ $v->file_name ?: 'Lesson Note Document' }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $v->formatted_file_size ?? '' }} • Attached Document File
                                        </p>
                                    </div>
                                    <div class="pt-2">
                                        <a
                                            href="{{ $downloadUrl }}"
                                            target="_blank"
                                            class="sln-btn sln-btn-primary px-5 py-2.5 text-xs shadow-sm"
                                        >
                                            <span class="sln-icon-4"><x-heroicon-m-arrow-down-tray /></span>
                                            Open & Download Lesson Note Document
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <!-- Attached Figures / Illustrations -->
                            @php($imageUrls = $v ? $v->getImageUrls() : [])
                            @if(!empty($imageUrls))
                                <div class="pt-6 border-t border-gray-200 dark:border-gray-800 space-y-3">
                                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                                        Attached Diagrams & Illustrations
                                    </h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        @foreach($imageUrls as $idx => $img)
                                            <div class="p-2 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-slate-900 text-center space-y-1">
                                                <img src="{{ $img }}" alt="Figure {{ $idx + 1 }}" class="max-h-56 mx-auto rounded-lg object-contain">
                                                <span class="text-[11px] text-gray-500">Figure {{ $idx + 1 }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Bottom Navigation & Download Bar -->
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-gray-200 dark:border-gray-800">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        wire:click="goToPreviousWeek"
                                        class="sln-btn sln-btn-outline text-xs"
                                    >
                                        <span class="sln-icon-4"><x-heroicon-m-chevron-left /></span>
                                        Previous Week
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="goToNextWeek"
                                        class="sln-btn sln-btn-outline text-xs"
                                    >
                                        Next Week
                                        <span class="sln-icon-4"><x-heroicon-m-chevron-right /></span>
                                    </button>
                                </div>

                                <a
                                    href="{{ $downloadUrl }}"
                                    target="_blank"
                                    class="sln-btn sln-btn-primary text-xs w-full sm:w-auto"
                                >
                                    <span class="sln-icon-4"><x-heroicon-m-arrow-down-tray /></span>
                                    Download Complete Note ({{ $v?->isFile() ? 'File' : 'PDF' }})
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="sln-card text-center py-16">
                            <span class="sln-icon-10" style="margin: 0 auto 0.75rem; color: #94a3b8;"><x-heroicon-o-document-text /></span>
                            <h3 class="text-base font-bold text-gray-800 dark:text-gray-200">
                                Select a week from the left to read its lesson note
                            </h3>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <!-- Clean Empty State when this subject has no approved notes -->
            <div class="sln-card text-center py-16">
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
</x-filament-panels::page>
