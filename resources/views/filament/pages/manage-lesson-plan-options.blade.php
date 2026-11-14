<x-filament-panels::page>
    <style>
        .lpo-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.25rem 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
        .dark .lpo-card { background: #131b2e; border-color: rgba(148,163,184,0.12); }
        .lpo-tab-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.125rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; transition: all 0.15s ease; border: 1px solid transparent; cursor: pointer; }
        .lpo-tab-active { background: #4f46e5; color: white; box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25); }
        .lpo-tab-inactive { background: rgba(148,163,184,0.1); color: #475569; }
        .lpo-tab-inactive:hover { background: rgba(148,163,184,0.2); color: #0f172a; }
        .dark .lpo-tab-inactive { background: rgba(255,255,255,0.05); color: #94a3b8; }
        .dark .lpo-tab-inactive:hover { background: rgba(255,255,255,0.1); color: #f1f5f9; }
        .lpo-badge { display: inline-flex; align-items: center; justify-content: center; padding: 0.15rem 0.5rem; font-size: 0.7rem; font-weight: 800; border-radius: 9999px; line-height: 1; }
        .lpo-badge-count { background: rgba(255,255,255,0.25); color: white; }
        .lpo-badge-count-inactive { background: rgba(100,116,139,0.15); color: #475569; }
        .dark .lpo-badge-count-inactive { background: rgba(255,255,255,0.15); color: #cbd5e1; }
        .lpo-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .lpo-table th { padding: 0.875rem 1rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; border-bottom: 1px solid #e2e8f0; text-align: left; }
        .dark .lpo-table th { border-bottom-color: #1e293b; color: #94a3b8; }
        .lpo-table td { padding: 0.875rem 1rem; border-bottom: 1px solid rgba(148,163,184,0.1); vertical-align: middle; }
        .dark .lpo-table td { border-bottom-color: rgba(148,163,184,0.06); }
        .lpo-input { width: 100%; border-radius: 0.75rem; border: 1px solid #cbd5e1; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; }
        .lpo-input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .dark .lpo-input { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .lpo-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.625rem 1.125rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 700; cursor: pointer; transition: all 0.15s ease; border: none; }
        .lpo-btn-primary { background: #4f46e5; color: white; }
        .lpo-btn-primary:hover { background: #4338ca; }
        .lpo-btn-danger { background: #ef4444; color: white; }
        .lpo-btn-danger:hover { background: #dc2626; }
        .lpo-btn-gray { background: #e2e8f0; color: #334155; }
        .lpo-btn-gray:hover { background: #cbd5e1; }
        .dark .lpo-btn-gray { background: #1e293b; color: #cbd5e1; }
        .dark .lpo-btn-gray:hover { background: #334155; }
        .lpo-icon-btn { display: inline-flex; align-items: center; justify-content: center; width: 2rem; height: 2rem; border-radius: 0.5rem; cursor: pointer; transition: all 0.15s ease; border: none; }
        .lpo-modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 50; padding: 1rem; }
        .lpo-modal-box { background: white; border-radius: 1rem; max-width: 32rem; width: 100%; padding: 1.75rem; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); }
        .dark .lpo-modal-box { background: #0f172a; border: 1px solid rgba(148,163,184,0.15); color: #f8fafc; }
    </style>

    <div class="space-y-6">
        <!-- ── Description Header Banner ───────────────────────────────────── -->
        <div class="lpo-card">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 shrink-0">
                        <x-heroicon-o-swatch class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white">Saved Dropdown Options Management</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Clean up inaccurate entries, fix typos, delete redundant duplicates, or add standardized options for teacher lesson plans.
                        </p>
                    </div>
                </div>
                <div class="shrink-0">
                    <button type="button" wire:click="openCreateModal" class="lpo-btn lpo-btn-primary shadow-sm w-full sm:w-auto">
                        <x-heroicon-m-plus class="w-4 h-4" />
                        Add {{ $this->getTabSingular() }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Navigation Tabs ─────────────────────────────────────────────── -->
        <div class="flex flex-wrap gap-2">
            <button
                type="button"
                wire:click="setTab('reference_materials')"
                class="lpo-tab-btn {{ $activeTab === 'reference_materials' ? 'lpo-tab-active' : 'lpo-tab-inactive' }}"
            >
                <x-heroicon-o-book-open class="w-4 h-4" />
                <span>Reference Materials</span>
                <span class="lpo-badge {{ $activeTab === 'reference_materials' ? 'lpo-badge-count' : 'lpo-badge-count-inactive' }}">
                    {{ $this->counts['reference_materials'] }}
                </span>
            </button>

            <button
                type="button"
                wire:click="setTab('instructional_materials')"
                class="lpo-tab-btn {{ $activeTab === 'instructional_materials' ? 'lpo-tab-active' : 'lpo-tab-inactive' }}"
            >
                <x-heroicon-o-cube class="w-4 h-4" />
                <span>Instructional Materials</span>
                <span class="lpo-badge {{ $activeTab === 'instructional_materials' ? 'lpo-badge-count' : 'lpo-badge-count-inactive' }}">
                    {{ $this->counts['instructional_materials'] }}
                </span>
            </button>

            <button
                type="button"
                wire:click="setTab('teaching_methods')"
                class="lpo-tab-btn {{ $activeTab === 'teaching_methods' ? 'lpo-tab-active' : 'lpo-tab-inactive' }}"
            >
                <x-heroicon-o-light-bulb class="w-4 h-4" />
                <span>Teaching Methods</span>
                <span class="lpo-badge {{ $activeTab === 'teaching_methods' ? 'lpo-badge-count' : 'lpo-badge-count-inactive' }}">
                    {{ $this->counts['teaching_methods'] }}
                </span>
            </button>
        </div>

        <!-- ── Table Container Card ────────────────────────────────────────── -->
        <div class="lpo-card space-y-4">
            <!-- Filter Bar & Bulk Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="relative max-w-md w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <x-heroicon-m-magnifying-glass class="w-4 h-4" />
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        class="lpo-input pl-9"
                        placeholder="Search {{ strtolower($this->getTabLabel()) }}…"
                    />
                    @if(!empty($search))
                        <button
                            type="button"
                            wire:click="$set('search', '')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <x-heroicon-m-x-mark class="w-4 h-4" />
                        </button>
                    @endif
                </div>

                @if(count($selectedIds) > 0)
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">
                            {{ count($selectedIds) }} selected
                        </span>
                        <button
                            type="button"
                            wire:click="confirmBulkDelete"
                            class="lpo-btn lpo-btn-danger text-xs py-1.5 px-3"
                        >
                            <x-heroicon-m-trash class="w-3.5 h-3.5" />
                            Delete Selected
                        </button>
                    </div>
                @endif
            </div>

            <!-- Table of Options -->
            <div class="overflow-x-auto">
                <table class="lpo-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectAll"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </th>
                            <th>Name</th>
                            <th style="width: 180px;">Usage in Plans</th>
                            <th style="width: 150px;">Created Date</th>
                            <th style="width: 110px; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->options as $option)
                            <tr wire:key="option-{{ $activeTab }}-{{ $option->id }}" class="hover:bg-gray-50/70 dark:hover:bg-slate-800/40 transition">
                                <td>
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedIds"
                                        value="{{ $option->id }}"
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                </td>
                                <td>
                                    <span class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $option->name }}
                                    </span>
                                </td>
                                <td>
                                    @if($option->lesson_plans_count > 0)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/80 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60">
                                            <x-heroicon-m-document-text class="w-3 h-3" />
                                            {{ $option->lesson_plans_count }} {{ \Illuminate\Support\Str::plural('plan', $option->lesson_plans_count) }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                            Unused
                                        </span>
                                    @endif
                                </td>
                                <td class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $option->created_at ? $option->created_at->format('M d, Y') : '—' }}
                                </td>
                                <td style="text-align: right;">
                                    <div class="inline-flex items-center gap-1">
                                        <button
                                            type="button"
                                            wire:click="openEditModal({{ $option->id }})"
                                            title="Rename Option"
                                            class="lpo-icon-btn text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-indigo-950/50"
                                        >
                                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $option->id }})"
                                            title="Delete Option"
                                            class="lpo-icon-btn text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/50"
                                        >
                                            <x-heroicon-m-trash class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-10 text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-folder-open class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                    <p class="font-medium text-sm">No {{ strtolower($this->getTabLabel()) }} found.</p>
                                    @if(!empty($search))
                                        <p class="text-xs mt-1">Try clearing your search term or add a new entry.</p>
                                    @else
                                        <p class="text-xs mt-1">Click the button above to add the first standardized option.</p>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($this->options->hasPages())
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                    {{ $this->options->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- ── CREATE MODAL ────────────────────────────────────────────────────── -->
    @if($showCreateModal)
        <div class="lpo-modal-overlay">
            <div class="lpo-modal-box space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Add New {{ $this->getTabSingular() }}
                    </h3>
                    <button type="button" wire:click="closeCreateModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Name / Title
                    </label>
                    <input
                        type="text"
                        wire:model="newOptionName"
                        wire:keydown.enter="createOption"
                        class="lpo-input"
                        placeholder="e.g. {{ $activeTab === 'reference_materials' ? 'Essential Mathematics for JSS 2' : ($activeTab === 'instructional_materials' ? 'Whiteboard & Markers' : 'Discussion & Questioning') }}"
                        autofocus
                    />
                    <p class="text-xs text-gray-400 mt-1">This will be immediately available in teacher lesson plan dropdowns.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeCreateModal" class="lpo-btn lpo-btn-gray">
                        Cancel
                    </button>
                    <button type="button" wire:click="createOption" class="lpo-btn lpo-btn-primary">
                        Save Option
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── EDIT MODAL ──────────────────────────────────────────────────────── -->
    @if($showEditModal)
        <div class="lpo-modal-overlay">
            <div class="lpo-modal-box space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">
                        Edit {{ $this->getTabSingular() }}
                    </h3>
                    <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                        Name / Title
                    </label>
                    <input
                        type="text"
                        wire:model="editingOptionName"
                        wire:keydown.enter="updateOption"
                        class="lpo-input"
                        autofocus
                    />
                    <p class="text-xs text-gray-400 mt-1">Renaming updates this option everywhere across all existing lesson plans.</p>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeEditModal" class="lpo-btn lpo-btn-gray">
                        Cancel
                    </button>
                    <button type="button" wire:click="updateOption" class="lpo-btn lpo-btn-primary">
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── DELETE CONFIRMATION MODAL ───────────────────────────────────────── -->
    @if($showDeleteModal)
        <div class="lpo-modal-overlay">
            <div class="lpo-modal-box space-y-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-full bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 shrink-0">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Delete Option?
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Permanent removal from {{ $this->getTabLabel() }}.
                        </p>
                    </div>
                </div>

                <div class="p-3 rounded-lg bg-gray-50 dark:bg-slate-800 text-sm text-gray-700 dark:text-gray-200">
                    <p class="font-semibold text-gray-900 dark:text-white mb-1">"{{ $deletingOptionName }}"</p>
                    @if($deletingUsageCount > 0)
                        <p class="text-xs text-amber-700 dark:text-amber-400 font-medium">
                            ⚠️ This item is currently referenced in {{ $deletingUsageCount }} lesson {{ \Illuminate\Support\Str::plural('plan', $deletingUsageCount) }}. Deleting it will safely remove it from those plans without deleting the plans themselves.
                        </p>
                    @else
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            This option is not currently used in any lesson plans.
                        </p>
                    @endif
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeDeleteModal" class="lpo-btn lpo-btn-gray">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteOption" class="lpo-btn lpo-btn-danger">
                        Yes, Delete Option
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ── BULK DELETE MODAL ───────────────────────────────────────────────── -->
    @if($showBulkDeleteModal)
        <div class="lpo-modal-overlay">
            <div class="lpo-modal-box space-y-4">
                <div class="flex items-center gap-3">
                    <div class="p-2 rounded-full bg-rose-100 dark:bg-rose-950/70 text-rose-600 dark:text-rose-400 shrink-0">
                        <x-heroicon-o-trash class="w-6 h-6" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                            Delete {{ count($selectedIds) }} Selected Options?
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Are you sure you want to permanently remove these options from {{ $this->getTabLabel() }}?
                        </p>
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    This action will remove the selected entries from reusable dropdowns. Any linked lesson plans will safely unbind them without data loss.
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeBulkDeleteModal" class="lpo-btn lpo-btn-gray">
                        Cancel
                    </button>
                    <button type="button" wire:click="deleteSelected" class="lpo-btn lpo-btn-danger">
                        Yes, Delete {{ count($selectedIds) }} Options
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
