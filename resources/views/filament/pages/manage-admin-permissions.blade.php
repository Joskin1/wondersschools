<x-filament-panels::page>
    @php
        $manageableStaff = $this->getManageableStaff();
        $groupedPermissions = \App\Services\AdminPermissionRegistry::getPermissionsGrouped();
        $presets = \App\Services\AdminPermissionRegistry::getPresets();
        $totalSelected = count($selectedPermissions);
        $isSudoUser = auth()->user()?->isSudo();
    @endphp

    <style>
        .perm-card { border-radius: 1rem; border: 1px solid rgba(148,163,184,0.2); background: white; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .dark .perm-card { background: #131b2e; border-color: rgba(148,163,184,0.1); }
        .perm-label { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; margin-bottom: 6px; }
        .perm-select, .perm-input { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: white; color: #1e293b; padding: 0.625rem 0.875rem; font-size: 0.875rem; transition: border-color 0.15s; }
        .perm-select:focus, .perm-input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .dark .perm-select, .dark .perm-input { background: #0b0f19; border-color: #1e293b; color: #f1f5f9; }
        .perm-btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem; padding: 0.5rem 0.875rem; border-radius: 0.625rem; font-size: 0.8125rem; font-weight: 700; border: 1px solid transparent; cursor: pointer; transition: all 0.15s; }
        .perm-btn-preset { background: #f8fafc; color: #334155; border-color: #e2e8f0; }
        .perm-btn-preset:hover { background: #f1f5f9; border-color: #cbd5e1; }
        .dark .perm-btn-preset { background: #1e293b; color: #cbd5e1; border-color: #334155; }
        .dark .perm-btn-preset:hover { background: #334155; border-color: #475569; }
        .perm-btn-primary { background: #4f46e5; color: white; box-shadow: 0 1px 3px rgba(79,70,229,0.3); }
        .perm-btn-primary:hover { background: #4338ca; }
        .perm-btn-toggle { background: transparent; color: #6366f1; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.5rem; border-radius: 0.375rem; border: none; cursor: pointer; }
        .perm-btn-toggle:hover { background: rgba(99,102,241,0.08); }
        .perm-res-box { padding: 0.875rem; border-radius: 0.75rem; border: 1px solid rgba(148,163,184,0.18); background: #f8fafc; margin-bottom: 0.75rem; }
        .dark .perm-res-box { background: #0b0f19; border-color: #1e293b; }
        .action-chip { display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.25rem 0.5rem; border-radius: 0.375rem; font-size: 11px; font-weight: 700; cursor: pointer; border: 1px solid #cbd5e1; background: white; color: #475569; transition: all 0.12s; }
        .action-chip:hover { border-color: #818cf8; color: #4338ca; }
        .action-chip.active { background: #4f46e5; border-color: #4f46e5; color: white; }
        .dark .action-chip { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        .dark .action-chip.active { background: #6366f1; border-color: #6366f1; color: white; }

        svg, svg * { box-sizing: border-box; }
        .perm-card svg, .perm-btn svg, .perm-banner svg, svg.perm-icon {
            width: 16px !important; height: 16px !important; max-width: 16px !important; max-height: 16px !important; min-width: 16px !important; min-height: 16px !important; flex-shrink: 0 !important; display: inline-block !important;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 0.85s linear infinite;
        }
    </style>

    <div class="space-y-6">
        @if($isSudoUser)
            {{-- Sudo Only: Principal Privilege Assignment Card --}}
            <div class="perm-card border-indigo-300 bg-indigo-50/50 dark:border-indigo-800 dark:bg-indigo-950/20">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-indigo-100 pb-3 dark:border-indigo-900/60">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white" style="width: 28px; height: 28px;">
                            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-indigo-950 dark:text-indigo-200">Sudo Authority: Designate School Principal</h3>
                            <p class="text-xs text-indigo-700/80 dark:text-indigo-400/80">Only Sudo can designate or replace the Principal. The designated Principal automatically receives unrestricted administrative access and can manage permissions for ordinary staff.</p>
                        </div>
                    </div>
                    @if($currentPrincipal = $this->getCurrentPrincipal())
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Current Principal: {{ $currentPrincipal->name }} [{{ ucfirst($currentPrincipal->role) }}] ({{ $currentPrincipal->email }})
                            </span>
                            <button type="button" wire:click="revokePrincipal({{ $currentPrincipal->id }})" wire:loading.attr="disabled" wire:target="revokePrincipal({{ $currentPrincipal->id }})" class="perm-btn text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40" style="border: 1px solid rgba(225,29,72,0.3); padding: 4px 8px;">
                                <svg wire:loading wire:target="revokePrincipal({{ $currentPrincipal->id }})" class="animate-spin" width="12" height="12" fill="none" viewBox="0 0 24 24">
                                    <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span wire:loading.remove wire:target="revokePrincipal({{ $currentPrincipal->id }})">Revoke Privilege</span>
                                <span wire:loading wire:target="revokePrincipal({{ $currentPrincipal->id }})">Revoking...</span>
                            </button>
                        </div>
                    @else
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            No Principal Designated Yet
                        </span>
                    @endif
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <div class="min-w-[280px] flex-1">
                        <label class="perm-label">Select Staff Member to Designate as Principal</label>
                        <select wire:model.live="principalUserId" class="perm-select">
                            <option value="">-- Choose staff member --</option>
                            @foreach($this->getAllPotentialPrincipals() as $staffUser)
                                <option value="{{ $staffUser->id }}">
                                    {{ $staffUser->name }} [{{ ucfirst($staffUser->role) }}] ({{ $staffUser->email }}) {{ $staffUser->is_primary_admin ? '★ [Current Principal]' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pt-5">
                        <button type="button" wire:click="assignPrincipal" wire:loading.attr="disabled" wire:target="assignPrincipal" class="perm-btn perm-btn-primary">
                            <svg wire:loading.remove wire:target="assignPrincipal" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <svg wire:loading wire:target="assignPrincipal" class="animate-spin" width="16" height="16" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="assignPrincipal">Designate as Principal</span>
                            <span wire:loading wire:target="assignPrincipal">Designating...</span>
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- Principal Notice Banner --}}
            <div class="perm-banner rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/30">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400" style="width: 32px; height: 32px; min-width: 32px;">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="flex-1 text-sm">
                        <p class="font-bold text-indigo-950 dark:text-indigo-200">
                            Principal Access Management
                        </p>
                        <p class="mt-0.5 text-xs text-indigo-800/80 dark:text-indigo-300/80">
                            You have unrestricted access across all school resources. Select secondary staff or teachers below to grant specific granular permissions (e.g. Bursar view-only access, Exam Officer assessment access). All unauthorized actions and direct URL attempts are strictly blocked server-side with HTTP 403.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if($manageableStaff->isEmpty())
            {{-- Empty State --}}
            <div class="perm-card flex flex-col items-center justify-center p-12 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 p-3 dark:bg-slate-800" style="width: 56px; height: 56px;">
                    <svg width="24" height="24" class="text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Secondary Staff to Configure</h3>
                <p class="mt-1 max-w-md text-xs text-slate-500 dark:text-slate-400">
                    To configure permissions for secondary staff (e.g. Bursar, Exam Officer, Vice Principal, or Teachers), create staff user accounts in User Management.
                </p>
            </div>
        @else
            {{-- User Selector & Role Configuration Card --}}
            <div class="perm-card space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                    <div class="md:col-span-5">
                        <label class="perm-label">Select Staff Member to Configure</label>
                        <select wire:model.live="selectedUserId" class="perm-select">
                            @foreach($manageableStaff as $staff)
                                <option value="{{ $staff->id }}">
                                    {{ $staff->name }} — [{{ ucfirst($staff->role) }}] {{ $staff->admin_designation ? "({$staff->admin_designation})" : '' }} ({{ $staff->email }})
                                </option>
                            @endforeach
                        </select>
                        <div wire:loading wire:target="selectedUserId" style="margin-top: 0.25rem; font-size: 0.75rem; color: #6366f1; font-weight: 700; display: flex; align-items: center; gap: 0.375rem;">
                            <svg class="animate-spin" width="12" height="12" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            Loading user permissions...
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <label class="perm-label">Administrative Designation / Title</label>
                        <input type="text" wire:model="adminDesignation" placeholder="e.g. Bursar, Exam Officer, Vice Principal" class="perm-input" />
                    </div>

                    <div class="flex items-end justify-end md:col-span-3">
                        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="perm-btn perm-btn-primary w-full md:w-auto">
                            <svg wire:loading.remove wire:target="save" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg wire:loading wire:target="save" class="animate-spin" width="14" height="14" fill="none" viewBox="0 0 24 24">
                                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">Save Permissions</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>
                    </div>
                </div>

                {{-- Quick Presets Toolbar (Optional Templates) --}}
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Optional Presets:</span>
                        @foreach($presets as $presetKey => $preset)
                            <button type="button" wire:click="applyPreset('{{ $presetKey }}')" class="perm-btn perm-btn-preset" title="{{ $preset['description'] }}">
                                {{ $preset['name'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="selectAll" class="perm-btn perm-btn-preset text-xs">
                            Select All
                        </button>
                        <button type="button" wire:click="deselectAll" class="perm-btn perm-btn-preset text-xs">
                            Deselect All
                        </button>
                    </div>
                </div>
            </div>

            {{-- Permissions Matrix Cards Grid --}}
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                @foreach($groupedPermissions as $groupName => $group)
                    @php
                        $groupItems = $group['items'];
                        $groupTotalCount = count($groupItems);
                    @endphp
                    <div class="perm-card flex flex-col justify-between">
                        <div>
                            {{-- Card Header --}}
                            <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2.5 dark:border-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400" style="width: 28px; height: 28px;">
                                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $groupName }}</h4>
                                        <span class="text-[11px] font-semibold text-slate-400">
                                            {{ $groupTotalCount }} resources/pages
                                        </span>
                                    </div>
                                </div>

                                <button type="button" wire:click="toggleGroup('{{ $groupName }}')" class="perm-btn-toggle">
                                    Toggle Group
                                </button>
                            </div>

                            {{-- Resource & Granular Action Items --}}
                            <div class="space-y-3">
                                @foreach($groupItems as $itemKey => $item)
                                    @php
                                        $isPageOnly = \App\Services\AdminPermissionRegistry::isPageOnly($itemKey);
                                        $actions = \App\Services\AdminPermissionRegistry::getActionsForResource($itemKey);
                                        $isFullySelected = $this->isResourceSelected($itemKey);
                                    @endphp
                                    <div class="perm-res-box">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <span class="block text-xs font-bold text-slate-900 dark:text-slate-100">
                                                    {{ $item['label'] }}
                                                </span>
                                                <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                                    {{ $item['description'] }}
                                                </span>
                                            </div>

                                            <button
                                                type="button"
                                                wire:click="toggleResource('{{ $itemKey }}')"
                                                class="action-chip {{ $isFullySelected ? 'active' : '' }}"
                                                title="Grant or revoke entire resource access"
                                            >
                                                {{ $isPageOnly ? ($isFullySelected ? 'Enabled' : 'Disabled') : ($isFullySelected ? 'All ✓' : 'All') }}
                                            </button>
                                        </div>

                                        {{-- Granular Action Chips for CRUD Resources --}}
                                        @if(! $isPageOnly && !empty($actions))
                                            <div class="mt-2.5 pt-2 border-t border-slate-200/60 dark:border-slate-800 flex flex-wrap items-center gap-1.5">
                                                @foreach($actions as $actionKey => $actionLabel)
                                                    @php
                                                        $actionActive = $this->isActionSelected($itemKey, $actionKey);
                                                    @endphp
                                                    <button
                                                        type="button"
                                                        wire:click="toggleAction('{{ $itemKey }}', '{{ $actionKey }}')"
                                                        class="action-chip {{ $actionActive ? 'active' : '' }}"
                                                    >
                                                        @if($actionKey === 'view') 👁️ @elseif($actionKey === 'create') ➕ @elseif($actionKey === 'update') ✏️ @elseif($actionKey === 'delete') 🗑️ @endif
                                                        {{ $actionLabel }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sticky Bottom Save Bar --}}
            <div class="perm-card flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-3 w-3 rounded-full {{ $totalSelected > 0 ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700' }}" style="width: 12px; height: 12px;"></span>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        <strong>{{ $totalSelected }}</strong> active permission rule(s) configured for this user.
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="perm-btn perm-btn-primary">
                        <svg wire:loading.remove wire:target="save" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg wire:loading wire:target="save" class="animate-spin" width="14" height="14" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="save">Save Permissions</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
