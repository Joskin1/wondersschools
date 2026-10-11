<x-filament-panels::page>
    @php
        $secondaryAdmins = $this->getSecondaryAdmins();
        $groupedPermissions = \App\Services\AdminPermissionRegistry::getPermissionsGrouped();
        $allKeys = \App\Services\AdminPermissionRegistry::getAllKeys();
        $presets = \App\Services\AdminPermissionRegistry::getPresets();
        $totalSelected = count($selectedPermissions);
        $totalAll = count($allKeys);
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
        .perm-item { display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.75rem; border-radius: 0.625rem; border: 1px solid rgba(148,163,184,0.15); background: #f8fafc; transition: all 0.15s; }
        .perm-item:hover { border-color: #a5b4fc; background: #f1f5f9; }
        .perm-item.active { border-color: #818cf8; background: rgba(238,242,255,0.7); }
        .dark .perm-item { background: #0b0f19; border-color: #1e293b; }
        .dark .perm-item:hover { border-color: #4338ca; }
        .dark .perm-item.active { border-color: #4f46e5; background: rgba(79,70,229,0.12); }

        /* Absolute icon sizing: prevents blown-up SVGs */
        svg, svg * {
            box-sizing: border-box;
        }
        .perm-card svg, .perm-item svg, .perm-btn svg, .perm-banner svg, svg.perm-icon {
            width: 16px !important;
            height: 16px !important;
            max-width: 16px !important;
            max-height: 16px !important;
            min-width: 16px !important;
            min-height: 16px !important;
            flex-shrink: 0 !important;
            display: inline-block !important;
        }
    </style>

    <div class="space-y-6">
        @if($isSudoUser)
            {{-- Sudo Only: Principal Privilege Assignment Card --}}
            <div class="perm-card border-indigo-300 bg-indigo-50/50 dark:border-indigo-800 dark:bg-indigo-950/20">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-indigo-100 pb-3 dark:border-indigo-900/60">
                    <div class="flex items-center gap-2">
                        <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white" style="width: 28px; height: 28px;">
                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-indigo-950 dark:text-indigo-200">Sudo Control: Designate School Principal</h3>
                            <p class="text-xs text-indigo-700/80 dark:text-indigo-400/80">Select an admin user to grant Principal status. Only the user you select here (and Sudo) will see and access this Admin Permissions menu.</p>
                        </div>
                    </div>
                    @if($currentPrincipal = $this->getCurrentPrincipal())
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                                Current Principal: {{ $currentPrincipal->name }} ({{ $currentPrincipal->email }})
                            </span>
                            <button type="button" wire:click="revokePrincipal({{ $currentPrincipal->id }})" class="perm-btn text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40" style="border: 1px solid rgba(225,29,72,0.3); padding: 4px 8px;">
                                Revoke Privilege
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
                        <label class="perm-label">Select Admin User Email to Assign as Principal</label>
                        <select wire:model="principalUserId" class="perm-select">
                            <option value="">-- Choose Admin user --</option>
                            @foreach($this->getAllAdmins() as $adminUser)
                                <option value="{{ $adminUser->id }}">
                                    {{ $adminUser->name }} ({{ $adminUser->email }}) {{ $adminUser->is_primary_admin ? '★ [Current Principal]' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="pt-5">
                        <button type="button" wire:click="assignPrincipal" class="perm-btn perm-btn-primary">
                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Grant Principal Privilege
                        </button>
                    </div>
                </div>
            </div>
        @else
            {{-- Principal Notice Banner --}}
            <div class="perm-banner rounded-xl border border-indigo-200 bg-indigo-50/70 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/30">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-600/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400" style="width: 32px; height: 32px; min-width: 32px;">
                        <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div class="flex-1 text-sm">
                        <p class="font-bold text-indigo-950 dark:text-indigo-200">
                            Principal Access Management
                        </p>
                        <p class="mt-0.5 text-xs text-indigo-800/80 dark:text-indigo-300/80">
                            Select secondary school administrators (e.g. Bursar, Exam Officer, Vice Principal) and check the exact pages and resources they can see and operate. Unauthorized pages will be hidden from their sidebar and blocked with HTTP 403.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if($secondaryAdmins->isEmpty())
            {{-- Empty State: No Secondary Admins --}}
            <div class="perm-card flex flex-col items-center justify-center p-12 text-center">
                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 p-3 dark:bg-slate-800" style="width: 56px; height: 56px;">
                    <svg width="24" height="24" style="width: 24px; height: 24px;" class="text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white">No Secondary Administrators to Configure</h3>
                <p class="mt-1 max-w-md text-xs text-slate-500 dark:text-slate-400">
                    To configure page permissions for a staff member (e.g. Bursar, Exam Officer, Vice Principal), first create an admin user account in User Management.
                </p>
                <div class="mt-4">
                    <a href="{{ \App\Filament\Resources\UserResource::getUrl('index') }}" class="perm-btn perm-btn-primary">
                        <svg width="14" height="14" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Admin User in User Management
                    </a>
                </div>
            </div>
        @else
            {{-- User Selector & Role Configuration Card --}}
            <div class="perm-card space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                    <div class="md:col-span-5">
                        <label class="perm-label">Select Administrator to Configure</label>
                        <select wire:model.live="selectedUserId" class="perm-select">
                            @foreach($secondaryAdmins as $admin)
                                <option value="{{ $admin->id }}">
                                    {{ $admin->name }} — {{ $admin->admin_designation ?: 'Admin' }} ({{ $admin->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label class="perm-label">Administrative Designation / Title</label>
                        <input type="text" wire:model="adminDesignation" placeholder="e.g. Bursar, Exam Officer, Vice Principal" class="perm-input" />
                    </div>

                    <div class="flex items-end justify-end md:col-span-3">
                        <button type="button" wire:click="save" class="perm-btn perm-btn-primary w-full md:w-auto">
                            <svg width="14" height="14" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Save Permissions
                        </button>
                    </div>
                </div>

                {{-- Quick Presets Toolbar --}}
                <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 dark:border-slate-800">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Quick Presets:</span>
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
                        $groupKeys = array_keys($group['items']);
                        $groupSelectedCount = count(array_intersect($groupKeys, $selectedPermissions));
                        $groupTotalCount = count($groupKeys);
                        $isGroupFullySelected = $groupSelectedCount === $groupTotalCount;
                    @endphp
                    <div class="perm-card flex flex-col justify-between">
                        <div>
                            {{-- Card Header --}}
                            <div class="mb-3 flex items-center justify-between border-b border-slate-100 pb-2.5 dark:border-slate-800">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400" style="width: 28px; height: 28px;">
                                        @if(str_contains($group['icon'], 'academic-cap'))
                                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                                        @elseif(str_contains($group['icon'], 'clipboard'))
                                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        @elseif(str_contains($group['icon'], 'book'))
                                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                                        @elseif(str_contains($group['icon'], 'users'))
                                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                                        @elseif(str_contains($group['icon'], 'globe'))
                                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                                        @else
                                            <svg width="16" height="16" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
                                        @endif
                                    </span>
                                    <div>
                                        <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $groupName }}</h4>
                                        <span class="text-[11px] font-semibold text-slate-400">
                                            {{ $groupSelectedCount }} of {{ $groupTotalCount }} enabled
                                        </span>
                                    </div>
                                </div>

                                <button type="button" wire:click="toggleGroup('{{ $groupName }}')" class="perm-btn-toggle">
                                    {{ $isGroupFullySelected ? 'Deselect All' : 'Select All' }}
                                </button>
                            </div>

                            {{-- Permission Items --}}
                            <div class="space-y-2">
                                @foreach($group['items'] as $itemKey => $item)
                                    @php
                                        $isChecked = in_array($itemKey, $selectedPermissions, true);
                                    @endphp
                                    <label class="perm-item cursor-pointer {{ $isChecked ? 'active' : '' }}">
                                        <input
                                            type="checkbox"
                                            wire:model.live="selectedPermissions"
                                            value="{{ $itemKey }}"
                                            style="margin-top: 2px; width: 16px; height: 16px; flex-shrink: 0;"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-900"
                                        />
                                        <div style="flex: 1; min-width: 0;">
                                            <span class="block text-xs font-bold text-slate-900 dark:text-slate-100" style="line-height: 1.35;">
                                                {{ $item['label'] }}
                                            </span>
                                            <span class="block text-[11px] text-slate-500 dark:text-slate-400" style="line-height: 1.35; margin-top: 2px;">
                                                {{ $item['description'] }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Sticky/Bottom Save Bar --}}
            <div class="perm-card flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-3 w-3 rounded-full {{ $totalSelected > 0 ? 'bg-emerald-500' : 'bg-slate-300 dark:bg-slate-700' }}" style="width: 12px; height: 12px;"></span>
                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">
                        <strong>{{ $totalSelected }}</strong> of {{ $totalAll }} total admin pages enabled for this user.
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" wire:click="save" class="perm-btn perm-btn-primary">
                        <svg width="14" height="14" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Permissions
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
