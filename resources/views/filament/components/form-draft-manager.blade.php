@php
    $record = $getRecord();
    $teacherId = auth()->id() ?? 'guest';
    $resourceType = $draftType ?? 'form';
    $actionType = $record ? 'edit_' . $record->id : 'create';
    $storageKey = "wonders_draft_{$resourceType}_{$teacherId}_{$actionType}";
    $label = $resourceName ?? 'Form';
@endphp

<div
    x-data="formDraftManager({
        storageKey: @js($storageKey),
        resourceName: @js($label)
    })"
    class="w-full mb-4"
>
    <!-- Auto-Restored Notification Banner -->
    <template x-if="hasDraft && !bannerDismissed">
        <div class="rounded-xl border border-emerald-300 bg-emerald-50/90 dark:border-emerald-700 dark:bg-emerald-950/40 p-4 shadow-sm transition-all duration-200 mb-2">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-start gap-3">
                    <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600 dark:text-emerald-400 shrink-0">
                        <x-heroicon-o-shield-check class="w-5 h-5" style="width: 20px; height: 20px; min-width: 20px; max-width: 20px; display: inline-block;" />
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-emerald-900 dark:text-emerald-200">
                            Unsaved <span x-text="resourceName"></span> Draft Auto-Restored
                        </h4>
                        <p class="text-xs text-emerald-700 dark:text-emerald-300 mt-0.5">
                            We recovered your unsaved work from <span class="font-medium" x-text="draftSavedAt"></span>. You can continue writing, or discard to start clean.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                    <button
                        type="button"
                        @click="dismissBanner()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-600 rounded-lg shadow-sm transition"
                    >
                        <x-heroicon-m-check class="w-4 h-4" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px; display: inline-block;" />
                        Keep & Continue
                    </button>
                    <button
                        type="button"
                        @click="discardDraft()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-700 dark:text-red-300 bg-red-100/70 hover:bg-red-200/80 dark:bg-red-950/50 dark:hover:bg-red-900/60 rounded-lg transition"
                    >
                        <x-heroicon-m-trash class="w-4 h-4 text-red-600 dark:text-red-400" style="width: 16px; height: 16px; min-width: 16px; max-width: 16px; display: inline-block;" />
                        Discard Draft
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Real-Time Auto-Save Status Bar -->
    <div class="flex flex-wrap items-center justify-between text-xs text-gray-500 dark:text-gray-400 px-1 py-1 gap-2">
        <div class="flex items-center gap-2">
            <!-- Offline state -->
            <template x-if="isOffline">
                <span class="inline-flex items-center gap-1.5 text-amber-600 dark:text-amber-400 font-medium bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-md border border-amber-200 dark:border-amber-800/50">
                    <x-heroicon-o-signal-slash class="w-3.5 h-3.5" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; display: inline-block;" />
                    <span>Offline mode active — work safely saved to this device</span>
                </span>
            </template>

            <!-- Online / Saving state -->
            <template x-if="!isOffline && showSavedIndicator">
                <span class="inline-flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400 font-medium">
                    <span class="relative flex h-2 w-2">
                        <span x-show="isSaving" class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    <span x-text="isSaving ? 'Saving draft to device...' : ('Draft saved locally at ' + lastSavedTime)"></span>
                </span>
            </template>

            <!-- Default idle protection status -->
            <template x-if="!isOffline && !showSavedIndicator">
                <span class="inline-flex items-center gap-1.5 text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-shield-check class="w-3.5 h-3.5" style="width: 14px; height: 14px; min-width: 14px; max-width: 14px; display: inline-block;" />
                    <span>Auto-draft protection active</span>
                </span>
            </template>
        </div>

        <template x-if="hasUnsavedChanges">
            <span class="text-[11px] text-amber-600 dark:text-amber-400 font-normal">
                Cached securely in browser (0 database load)
            </span>
        </template>
    </div>
</div>

<script>
    (function() {
        const registerFormDraftManager = () => {
            if (window.Alpine && !window.Alpine._formDraftManagerRegistered) {
                window.Alpine._formDraftManagerRegistered = true;

                window.Alpine.data('formDraftManager', ({ storageKey, resourceName }) => ({
                    storageKey,
                    resourceName,
                    hasDraft: false,
                    isAutoRestored: false,
                    draftSavedAt: '',
                    draftData: null,
                    isSaving: false,
                    lastSavedTime: '',
                    hasUnsavedChanges: false,
                    showSavedIndicator: false,
                    isSubmitting: false,
                    isOffline: !navigator.onLine,
                    saveTimeout: null,
                    periodicInterval: null,
                    bannerDismissed: false,

                    init() {
                        this.checkForExistingDraft();
                        this.setupAutoSave();
                        this.setupNetworkListeners();
                        this.setupBeforeUnload();
                        this.setupSubmitListener();

                        window.addEventListener('form-submitted-clear-draft', (e) => {
                            if (!e.detail?.key || e.detail.key === this.storageKey) {
                                this.clearDraftOnSubmit();
                            }
                        });
                    },

                    destroy() {
                        if (this.periodicInterval) {
                            clearInterval(this.periodicInterval);
                        }
                    },

                    setupNetworkListeners() {
                        window.addEventListener('online', () => {
                            this.isOffline = false;
                        });
                        window.addEventListener('offline', () => {
                            this.isOffline = true;
                        });
                    },

                    checkForExistingDraft() {
                        try {
                            const raw = localStorage.getItem(this.storageKey);
                            if (!raw) return;
                            const parsed = JSON.parse(raw);
                            if (!parsed || !parsed.data) return;

                            const keysWithContent = Object.keys(parsed.data).filter(k => {
                                if (k === 'draft_manager' || k === 'submission_status') return false;
                                const v = parsed.data[k];
                                if (Array.isArray(v)) return v.length > 0;
                                if (typeof v === 'object' && v !== null) return Object.keys(v).length > 0;
                                return v !== null && v !== undefined && String(v).trim() !== '' && String(v) !== '<p></p>';
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

                                this.$nextTick(() => {
                                    setTimeout(() => {
                                        this.autoRestoreDraft();
                                    }, 250);
                                });
                            }
                        } catch (e) {
                            console.warn('Could not read form draft from localStorage', e);
                        }
                    },

                    async autoRestoreDraft() {
                        if (!this.draftData) return;

                        try {
                            if (typeof this.$wire !== 'undefined') {
                                // 1. Prepare clean data payload to batch set in Livewire atomically
                                const currentLivewireData = this.$wire.get('data') || this.$wire.data || {};
                                const mergedPayload = Object.assign({}, currentLivewireData);

                                for (const [key, value] of Object.entries(this.draftData)) {
                                    if (value === null || typeof value === 'undefined' || key === 'draft_manager' || key === 'submission_status') continue;
                                    mergedPayload[key] = value;
                                }

                                // 2. Atomic single Livewire set call to prevent request drops/cancellations
                                try {
                                    await this.$wire.set('data', mergedPayload);
                                } catch (e) {
                                    // Fallback: set individual keys if root set fails
                                    for (const [key, value] of Object.entries(this.draftData)) {
                                        if (value === null || typeof value === 'undefined' || key === 'draft_manager' || key === 'submission_status') continue;
                                        try {
                                            this.$wire.set('data.' + key, value);
                                        } catch (err) {}
                                    }
                                }
                            }

                            // 3. Multi-stage TipTap rich editor hydration (with retries to ensure delayed components load)
                            const hydrateTimes = [150, 400, 800, 1500];
                            hydrateTimes.forEach(delay => {
                                setTimeout(() => {
                                    this.hydrateRichEditors(this.draftData);
                                }, delay);
                            });

                            this.isAutoRestored = true;
                            this.lastSavedTime = this.draftSavedAt;
                            this.showSavedIndicator = true;
                            this.hasUnsavedChanges = true;

                            window.dispatchEvent(new CustomEvent('draft-restored', { detail: this.draftData }));
                        } catch (e) {
                            console.error('Error auto-restoring draft:', e);
                        }
                    },

                    hydrateRichEditors(data) {
                        const formElement = this.$el.closest('form') || document.querySelector('form.fi-form') || document.querySelector('form');
                        if (!formElement || !data) return;

                        // Find all rich editor containers and their Alpine components
                        const richEditorElements = formElement.querySelectorAll('.fi-fo-rich-editor, [x-data*="richEditorFormComponent"], [x-load-src*="rich-editor"]');

                        richEditorElements.forEach(el => {
                            try {
                                // Traverse element and children to locate richEditorFormComponent Alpine data
                                let alpineData = null;
                                if (window.Alpine) {
                                    if (typeof Alpine.$data === 'function') {
                                        alpineData = Alpine.$data(el);
                                        if (!alpineData?.statePath) {
                                            const childWithData = el.querySelector('[x-data*="richEditorFormComponent"], [x-load-src*="rich-editor"]');
                                            if (childWithData) {
                                                alpineData = Alpine.$data(childWithData);
                                            }
                                        }
                                    }
                                }

                                let statePath = alpineData?.statePath;
                                if (!statePath) {
                                    const modelAttr = el.getAttribute('wire:model') || el.querySelector('[wire\\:model]')?.getAttribute('wire:model');
                                    if (modelAttr) statePath = modelAttr;
                                }

                                const cleanKey = statePath ? statePath.replace(/^data\./, '') : null;

                                if (cleanKey && data[cleanKey] !== undefined && data[cleanKey] !== null) {
                                    const targetContent = data[cleanKey];

                                    if (alpineData) {
                                        if (typeof alpineData.getEditor === 'function') {
                                            const editor = alpineData.getEditor();
                                            if (editor && typeof editor.commands?.setContent === 'function') {
                                                const currentHTML = typeof editor.getHTML === 'function' ? editor.getHTML() : '';
                                                if (!currentHTML || currentHTML === '<p></p>' || currentHTML !== targetContent) {
                                                    editor.commands.setContent(targetContent, false);
                                                }
                                            }
                                        }
                                        if (alpineData.state !== undefined) {
                                            alpineData.state = targetContent;
                                        }
                                    }

                                    // Direct ProseMirror DOM fallback
                                    const pm = el.querySelector('.tiptap.ProseMirror, .ProseMirror');
                                    if (pm && (!pm.innerHTML || pm.innerHTML === '<p></p>')) {
                                        pm.innerHTML = targetContent;
                                    }
                                }
                            } catch (err) {
                                console.warn('Error hydrating rich editor', err);
                            }
                        });
                    },

                    restoreDraft() {
                        this.autoRestoreDraft();
                        this.bannerDismissed = true;
                    },

                    dismissBanner() {
                        this.bannerDismissed = true;
                    },

                    discardDraft() {
                        try {
                            localStorage.removeItem(this.storageKey);
                        } catch (e) {}
                        this.hasDraft = false;
                        this.isAutoRestored = false;
                        this.draftData = null;
                        this.hasUnsavedChanges = false;
                        this.showSavedIndicator = false;
                        this.bannerDismissed = true;

                        const formElement = this.$el.closest('form') || document.querySelector('form.fi-form') || document.querySelector('form');
                        if (formElement) {
                            formElement.querySelectorAll('.fi-fo-rich-editor, [x-data*="richEditorFormComponent"]').forEach(editorWrapper => {
                                try {
                                    const alpineData = window.Alpine ? Alpine.$data(editorWrapper) : null;
                                    if (alpineData && typeof alpineData.getEditor === 'function') {
                                        const editor = alpineData.getEditor();
                                        if (editor && typeof editor.commands?.setContent === 'function') {
                                            editor.commands.setContent('');
                                        }
                                    }
                                } catch (err) {}
                            });
                        }

                        window.location.reload();
                    },

                    collectLiveFormData() {
                        let currentData = {};

                        // 1. Base from Livewire
                        if (typeof this.$wire !== 'undefined') {
                            try {
                                const rawData = this.$wire.get('data') || this.$wire.data || {};
                                for (const [k, v] of Object.entries(rawData)) {
                                    if (v instanceof File || v instanceof FileList || (typeof v === 'object' && v !== null && v.temporaryUrl)) {
                                        continue;
                                    }
                                    // Deep clone objects/arrays to avoid live reference mutations
                                    currentData[k] = JSON.parse(JSON.stringify(v));
                                }
                            } catch (e) {}
                        }

                        const formElement = this.$el.closest('form') || document.querySelector('form.fi-form') || document.querySelector('form');
                        if (formElement) {
                            // 2. Read standard DOM inputs, selects, textareas
                            const inputs = formElement.querySelectorAll('input[name], select[name], textarea[name]');
                            inputs.forEach(input => {
                                const name = input.getAttribute('name');
                                if (!name) return;
                                if (input.type === 'file' || (input.type === 'hidden' && name.includes('_token'))) return;

                                const cleanKey = name.replace(/^data\./, '').replace(/\[\]$/, '');

                                if (input.type === 'checkbox') {
                                    currentData[cleanKey] = input.checked;
                                } else if (input.type === 'radio') {
                                    if (input.checked) {
                                        currentData[cleanKey] = input.value;
                                    }
                                } else if (input.value !== undefined && input.value !== null && input.value !== '') {
                                    if (!currentData[cleanKey] || typeof currentData[cleanKey] === 'string') {
                                        currentData[cleanKey] = input.value;
                                    }
                                }
                            });

                            // 3. Read all TipTap RichEditors
                            const richEditorWrappers = formElement.querySelectorAll('.fi-fo-rich-editor, [x-data*="richEditorFormComponent"], [x-load-src*="rich-editor"]');
                            richEditorWrappers.forEach(el => {
                                try {
                                    let alpineData = null;
                                    if (window.Alpine && typeof Alpine.$data === 'function') {
                                        alpineData = Alpine.$data(el);
                                        if (!alpineData?.statePath) {
                                            const childWithData = el.querySelector('[x-data*="richEditorFormComponent"], [x-load-src*="rich-editor"]');
                                            if (childWithData) {
                                                alpineData = Alpine.$data(childWithData);
                                            }
                                        }
                                    }

                                    let statePath = alpineData?.statePath;
                                    if (!statePath) {
                                        const modelAttr = el.getAttribute('wire:model') || el.querySelector('[wire\\:model]')?.getAttribute('wire:model');
                                        if (modelAttr) statePath = modelAttr;
                                    }

                                    let contentHtml = null;

                                    if (alpineData) {
                                        if (typeof alpineData.getEditor === 'function') {
                                            const editor = alpineData.getEditor();
                                            if (editor && typeof editor.getHTML === 'function') {
                                                contentHtml = editor.getHTML();
                                            }
                                        }
                                        if (!contentHtml && alpineData.state) {
                                            contentHtml = typeof alpineData.state === 'string' ? alpineData.state : JSON.stringify(alpineData.state);
                                        }
                                    }

                                    if (!contentHtml) {
                                        const pm = el.querySelector('.tiptap.ProseMirror, .ProseMirror');
                                        if (pm && pm.innerHTML && pm.innerHTML !== '<p></p>') {
                                            contentHtml = pm.innerHTML;
                                        }
                                    }

                                    if (statePath && contentHtml) {
                                        const cleanKey = statePath.replace(/^data\./, '');
                                        if (contentHtml !== '<p></p>' && contentHtml !== '') {
                                            currentData[cleanKey] = contentHtml;
                                        }
                                    }
                                } catch (err) {}
                            });
                        }

                        return currentData;
                    },

                    setupAutoSave() {
                        const formElement = this.$el.closest('form') || this.$el.parentElement || document.querySelector('form.fi-form');
                        if (!formElement) return;

                        const handleInput = () => {
                            this.hasUnsavedChanges = true;
                            clearTimeout(this.saveTimeout);
                            this.saveTimeout = setTimeout(() => {
                                this.saveDraftToStorage();
                            }, 400);
                        };

                        // Listen on all user interaction types across the entire form
                        formElement.addEventListener('input', handleInput, true);
                        formElement.addEventListener('change', handleInput, true);
                        formElement.addEventListener('keyup', handleInput, true);
                        formElement.addEventListener('paste', handleInput, true);
                        formElement.addEventListener('blur', handleInput, true);
                        formElement.addEventListener('focusout', handleInput, true);
                        formElement.addEventListener('trix-change', handleInput);
                        window.addEventListener('filament-rich-editor-update', handleInput);

                        // Periodic backup autosave every 5 seconds if changes exist
                        this.periodicInterval = setInterval(() => {
                            if (this.hasUnsavedChanges && !this.isSubmitting) {
                                this.saveDraftToStorage();
                            }
                        }, 5000);

                        if (typeof this.$wire !== 'undefined') {
                            this.$watch('$wire.data', () => {
                                handleInput();
                            });
                        }
                    },

                    saveDraftToStorage() {
                        if (this.isSubmitting) return;

                        try {
                            const currentData = this.collectLiveFormData();

                            const hasContent = Object.entries(currentData).some(([k, v]) => {
                                if (k === 'draft_manager' || k === 'submission_status') return false;
                                if (Array.isArray(v)) return v.length > 0;
                                if (typeof v === 'object' && v !== null) return Object.keys(v).length > 0;
                                return v !== null && v !== undefined && String(v).trim() !== '' && String(v) !== '<p></p>';
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
                                this.hasUnsavedChanges = true;
                                setTimeout(() => {
                                    this.isSaving = false;
                                }, 300);
                            }
                        } catch (e) {
                            console.warn('Could not save draft to localStorage', e);
                        }
                    },

                    setupBeforeUnload() {
                        window.addEventListener('beforeunload', (event) => {
                            if (this.hasUnsavedChanges && !this.isSubmitting) {
                                // Final sync attempt on tab close/navigation
                                this.saveDraftToStorage();
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
                }));
            }
        };

        if (window.Alpine) {
            registerFormDraftManager();
        } else {
            document.addEventListener('alpine:init', registerFormDraftManager);
        }
    })();
</script>
