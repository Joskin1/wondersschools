@php
    $record = $getRecord();
    $teacherId = auth()->id() ?? 'guest';
    $resourceType = $draftType ?? 'form';
    $actionType = $record ? 'edit_' . $record->id : 'create';
    $storageKey = "wonders_draft_{$resourceType}_{$teacherId}_{$actionType}";
    $label = $resourceName ?? 'Form';
@endphp

<div
    x-data="{
        storageKey: @js($storageKey),
        resourceName: @js($label),
        hasDraft: false,
        draftSavedAt: '',
        draftData: null,
        isSaving: false,
        lastSavedTime: '',
        hasUnsavedChanges: false,
        showSavedIndicator: false,
        isSubmitting: false,
        saveTimeout: null,

        init() {
            this.checkForExistingDraft();
            this.setupAutoSave();
            this.setupBeforeUnload();
            this.setupSubmitListener();

            window.addEventListener('form-submitted-clear-draft', (e) => {
                if (!e.detail?.key || e.detail.key === this.storageKey) {
                    this.clearDraftOnSubmit();
                }
            });
        },

        checkForExistingDraft() {
            try {
                const raw = localStorage.getItem(this.storageKey);
                if (!raw) return;
                const parsed = JSON.parse(raw);
                if (!parsed || !parsed.data) return;

                // Check if draft has any non-empty values
                const keysWithContent = Object.keys(parsed.data).filter(k => {
                    const v = parsed.data[k];
                    if (Array.isArray(v)) return v.length > 0;
                    return v !== null && v !== undefined && String(v).trim() !== '';
                });

                if (keysWithContent.length > 0) {
                    this.hasDraft = true;
                    this.draftData = parsed.data;
                    if (parsed.savedAt) {
                        const date = new Date(parsed.savedAt);
                        this.draftSavedAt = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', month: 'short', day: 'numeric' });
                    } else {
                        this.draftSavedAt = 'a previous session';
                    }
                }
            } catch (e) {
                console.warn('Could not read form draft from localStorage', e);
            }
        },

        restoreDraft() {
            if (!this.draftData) return;
            try {
                if (typeof $wire !== 'undefined') {
                    for (const [key, value] of Object.entries(this.draftData)) {
                        if (value === null || typeof value === 'undefined') continue;
                        $wire.set('data.' + key, value);
                    }
                }
                this.hasDraft = false;
                this.lastSavedTime = this.draftSavedAt;
                this.showSavedIndicator = true;
                this.hasUnsavedChanges = true;

                // Dispatch notification event for Filament
                if (typeof FilamentNotification !== 'undefined') {
                    new FilamentNotification()
                        .title('Draft Restored')
                        .body('Your unsaved ' + this.resourceName + ' draft has been restored.')
                        .success()
                        .send();
                }

                window.dispatchEvent(new CustomEvent('draft-restored', { detail: this.draftData }));
            } catch (e) {
                console.error('Error restoring draft:', e);
            }
        },

        discardDraft() {
            try {
                localStorage.removeItem(this.storageKey);
            } catch (e) {}
            this.hasDraft = false;
            this.draftData = null;
            this.hasUnsavedChanges = false;
            this.showSavedIndicator = false;
        },

        setupAutoSave() {
            const formElement = this.$el.closest('form') || this.$el.parentElement;
            if (!formElement) return;

            const handleInput = () => {
                this.hasUnsavedChanges = true;
                clearTimeout(this.saveTimeout);
                this.saveTimeout = setTimeout(() => {
                    this.saveDraftToStorage();
                }, 750);
            };

            formElement.addEventListener('input', handleInput);
            formElement.addEventListener('change', handleInput);
            formElement.addEventListener('trix-change', handleInput);

            if (typeof $wire !== 'undefined') {
                this.$watch('$wire.data', () => {
                    handleInput();
                });
            }
        },

        saveDraftToStorage() {
            if (this.isSubmitting) return;

            try {
                let currentData = {};
                if (typeof $wire !== 'undefined') {
                    const rawData = $wire.get('data') || $wire.data || {};
                    for (const [k, v] of Object.entries(rawData)) {
                        if (v instanceof File || v instanceof FileList || (typeof v === 'object' && v !== null && v.temporaryUrl)) {
                            continue;
                        }
                        currentData[k] = v;
                    }
                }

                const hasContent = Object.values(currentData).some(v => {
                    if (Array.isArray(v)) return v.length > 0;
                    return v !== null && v !== undefined && String(v).trim() !== '';
                });

                if (hasContent) {
                    this.isSaving = true;
                    const payload = {
                        savedAt: new Date().toISOString(),
                        data: currentData
                    };
                    localStorage.setItem(this.storageKey, JSON.stringify(payload));
                    const now = new Date();
                    this.lastSavedTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    this.showSavedIndicator = true;
                    setTimeout(() => {
                        this.isSaving = false;
                    }, 400);
                }
            } catch (e) {
                console.warn('Could not save draft to localStorage', e);
            }
        },

        setupBeforeUnload() {
            window.addEventListener('beforeunload', (event) => {
                if (this.hasUnsavedChanges && !this.isSubmitting) {
                    event.preventDefault();
                    event.returnValue = 'You have unsaved changes in your ' + this.resourceName + '. Are you sure you want to leave?';
                    return event.returnValue;
                }
            });
        },

        setupSubmitListener() {
            const formElement = this.$el.closest('form') || this.$el.parentElement;
            if (formElement) {
                formElement.addEventListener('submit', () => {
                    this.clearDraftOnSubmit();
                });
            }

            // Target Filament action buttons (submit, save, etc.)
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('button[type="submit"], button[wire\\:click*="create"], button[wire\\:click*="save"]');
                if (btn) {
                    this.clearDraftOnSubmit();
                }
            });
        },

        clearDraftOnSubmit() {
            this.hasUnsavedChanges = false;
            this.isSubmitting = true;
            try {
                localStorage.removeItem(this.storageKey);
            } catch (e) {}
        }
    }"
    class="w-full mb-4"
>
    <!-- Restore Draft Notification Banner -->
    <template x-if="hasDraft">
        <div class="rounded-xl border border-amber-300 bg-amber-50/90 dark:border-amber-700 dark:bg-amber-950/30 p-4 shadow-sm transition-all duration-200">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 shrink-0">
                        <x-heroicon-o-arrow-uturn-left class="w-5 h-5" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                            Unsaved <span x-text="resourceName"></span> Draft Found
                        </h4>
                        <p class="text-xs text-amber-700 dark:text-amber-300 mt-0.5">
                            You have an unsaved draft from <span class="font-medium" x-text="draftSavedAt"></span>. Would you like to restore your work?
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                    <button
                        type="button"
                        @click="restoreDraft()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 rounded-lg shadow-sm transition"
                    >
                        <x-heroicon-m-arrow-path class="w-4 h-4" />
                        Restore Draft
                    </button>
                    <button
                        type="button"
                        @click="discardDraft()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-amber-800 dark:text-amber-200 bg-amber-200/60 hover:bg-amber-200 dark:bg-amber-900/40 dark:hover:bg-amber-900/60 rounded-lg transition"
                    >
                        <x-heroicon-m-trash class="w-4 h-4 text-amber-600 dark:text-amber-400" />
                        Discard
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Subtle Live Auto-save Status Indicator -->
    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 px-1 py-1">
        <div class="flex items-center gap-2">
            <template x-if="showSavedIndicator">
                <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium">
                    <span class="relative flex h-2 w-2">
                        <span x-show="isSaving" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span x-text="isSaving ? 'Saving draft locally...' : ('Draft saved locally at ' + lastSavedTime)"></span>
                </span>
            </template>
            <template x-if="!showSavedIndicator">
                <span class="inline-flex items-center gap-1.5 text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-shield-check class="w-3.5 h-3.5" />
                    <span>Auto-draft protection active</span>
                </span>
            </template>
        </div>

        <template x-if="hasUnsavedChanges && !hasDraft">
            <span class="text-[11px] text-amber-600 dark:text-amber-400 font-normal">
                Unsaved changes (cached in browser)
            </span>
        </template>
    </div>
</div>
