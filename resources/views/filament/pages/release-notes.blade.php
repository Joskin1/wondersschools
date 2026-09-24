<x-filament-panels::page>
    <style>
        .rn-container { display: flex; flex-direction: column; gap: 1.5rem; max-width: 100%; }
        .rn-hero { display: flex; flex-direction: column; gap: 1rem; padding: 1.5rem 1.75rem; border-radius: 1rem; border: 1px solid rgba(245,158,11,0.2); background: linear-gradient(135deg, rgba(245,158,11,0.06) 0%, rgba(59,130,246,0.03) 100%); }
        @media (min-width: 768px) {
            .rn-hero { flex-direction: row; align-items: center; justify-content: space-between; }
        }
        .rn-hero-title { display: flex; align-items: center; gap: 0.75rem; font-size: 1.25rem; font-weight: 800; letter-spacing: -0.02em; color: #1e293b; }
        .dark .rn-hero-title { color: #f8fafc; }
        .rn-hero-subtitle { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .dark .rn-hero-subtitle { color: #94a3b8; }
        
        .rn-toolbar { display: flex; flex-direction: column; gap: 1rem; }
        @media (min-width: 640px) {
            .rn-toolbar { flex-direction: row; align-items: center; justify-content: space-between; }
        }
        .rn-filters { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .rn-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.45rem 0.875rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 600; cursor: pointer; border: 1px solid transparent; transition: all 0.15s ease; background: #ffffff; color: #475569; border-color: #e2e8f0; }
        .dark .rn-btn { background: #18181b; color: #cbd5e1; border-color: #27272a; }
        .rn-btn:hover { background: #f8fafc; border-color: #cbd5e1; }
        .dark .rn-btn:hover { background: #27272a; border-color: #3f3f46; }
        .rn-btn.active { background: #ea580c; color: #ffffff; border-color: #ea580c; box-shadow: 0 1px 3px rgba(234,88,12,0.25); }
        .dark .rn-btn.active { background: #ea580c; color: #ffffff; border-color: #ea580c; }
        
        .rn-search-box { position: relative; width: 100%; }
        @media (min-width: 640px) {
            .rn-search-box { width: 18rem; }
        }
        .rn-search-input { width: 100%; border-radius: 0.75rem; border: 1px solid #e2e8f0; background: #ffffff; color: #1e293b; padding: 0.45rem 0.875rem 0.45rem 2.25rem; font-size: 0.8125rem; outline: none; }
        .dark .rn-search-input { background: #18181b; border-color: #27272a; color: #f1f5f9; }
        .rn-search-input:focus { border-color: #ea580c; box-shadow: 0 0 0 2px rgba(234,88,12,0.15); }
        .rn-search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); pointer-events: none; color: #94a3b8; width: 1rem; height: 1rem; }

        .rn-timeline { display: flex; flex-direction: column; gap: 1.25rem; }
        .rn-card { position: relative; border-radius: 1rem; border: 1px solid #e2e8f0; background: #ffffff; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; padding: 1.5rem 1.75rem 1.5rem 2rem; transition: box-shadow 0.2s ease; }
        .dark .rn-card { background: #18181b; border-color: #27272a; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .rn-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
        .dark .rn-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        
        .rn-accent-bar { position: absolute; left: 0; top: 0; bottom: 0; width: 5px; }
        .rn-accent-workflow { background: #f59e0b; }
        .rn-accent-feature { background: #10b981; }
        .rn-accent-improvement { background: #3b82f6; }
        .rn-accent-bugfix { background: #f43f5e; }

        .rn-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.875rem; margin-bottom: 1rem; }
        .dark .rn-header { border-bottom-color: #27272a; }
        .rn-meta-left { display: flex; flex-wrap: wrap; align-items: center; gap: 0.625rem; }
        
        .rn-version-pill { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.75rem; font-weight: 800; padding: 0.25rem 0.625rem; border-radius: 0.5rem; background: #0f172a; color: #ffffff; }
        .dark .rn-version-pill { background: #f8fafc; color: #0f172a; }
        
        .rn-cat-badge { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.625rem; border-radius: 0.5rem; border: 1px solid; }
        .rn-cat-workflow { background: rgba(245,158,11,0.1); color: #d97706; border-color: rgba(245,158,11,0.25); }
        .dark .rn-cat-workflow { background: rgba(245,158,11,0.15); color: #fbbf24; border-color: rgba(245,158,11,0.3); }
        .rn-cat-feature { background: rgba(16,185,129,0.1); color: #059669; border-color: rgba(16,185,129,0.25); }
        .dark .rn-cat-feature { background: rgba(16,185,129,0.15); color: #34d399; border-color: rgba(16,185,129,0.3); }
        .rn-cat-improvement { background: rgba(59,130,246,0.1); color: #2563eb; border-color: rgba(59,130,246,0.25); }
        .dark .rn-cat-improvement { background: rgba(59,130,246,0.15); color: #60a5fa; border-color: rgba(59,130,246,0.3); }
        .rn-cat-bugfix { background: rgba(244,63,94,0.1); color: #e11d48; border-color: rgba(244,63,94,0.25); }
        .dark .rn-cat-bugfix { background: rgba(244,63,94,0.15); color: #fb7185; border-color: rgba(244,63,94,0.3); }
        
        .rn-date { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 0.75rem; color: #64748b; }
        .dark .rn-date { color: #94a3b8; }
        
        .rn-title { font-size: 1.125rem; font-weight: 800; color: #0f172a; letter-spacing: -0.01em; margin-bottom: 0.375rem; }
        .dark .rn-title { color: #f8fafc; }
        .rn-summary { font-size: 0.875rem; color: #475569; line-height: 1.6; }
        .dark .rn-summary { color: #cbd5e1; }

        .rn-callout { margin-top: 1rem; padding: 1rem 1.25rem; border-radius: 0.75rem; background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.25); }
        .dark .rn-callout { background: rgba(245,158,11,0.12); border-color: rgba(245,158,11,0.3); }
        .rn-callout-header { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #b45309; margin-bottom: 0.5rem; }
        .dark .rn-callout-header { color: #fbbf24; }
        .rn-callout-body { font-size: 0.8125rem; line-height: 1.6; color: #78350f; white-space: pre-line; font-weight: 500; }
        .dark .rn-callout-body { color: #fde68a; }

        .rn-changes-section { margin-top: 1rem; }
        .rn-changes-title { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em; color: #475569; margin-bottom: 0.5rem; }
        .dark .rn-changes-title { color: #94a3b8; }
        .rn-change-item { display: flex; align-items: flex-start; gap: 0.625rem; font-size: 0.8125rem; color: #475569; line-height: 1.5; margin-bottom: 0.375rem; }
        .dark .rn-change-item { color: #cbd5e1; }
        .rn-check-icon { width: 1rem; height: 1rem; flex-shrink: 0; margin-top: 0.125rem; color: #ea580c; }
    </style>

    <div class="rn-container">
        <!-- Header Banner / Subtitle -->
        <div class="rn-hero">
            <div>
                <div class="rn-hero-title">
                    <span style="font-size: 1.5rem;">✨</span>
                    <span>System Updates & Release Notes</span>
                </div>
                <div class="rn-hero-subtitle">
                    Discover new features, workflow enhancements, and operational guidelines across the portal.
                </div>
            </div>

            <div>
                <span class="rn-btn" style="cursor: default; font-weight: 700;">
                    <span>🕒</span>
                    <span>Latest: {{ $this->releaseNotes->first()?->version ?? 'v1.0.0' }}</span>
                </span>
            </div>
        </div>

        <!-- Filter & Search Controls -->
        <div class="rn-toolbar">
            <!-- Category Filter Pills -->
            <div class="rn-filters">
                @php
                    $categories = [
                        'all' => ['label' => 'All Releases', 'icon' => '📂'],
                        'workflow_change' => ['label' => 'Workflow Changes', 'icon' => '⚡'],
                        'feature' => ['label' => 'New Features', 'icon' => '🚀'],
                        'improvement' => ['label' => 'Improvements', 'icon' => '📈'],
                        'bugfix' => ['label' => 'Fixes', 'icon' => '🔧'],
                    ];
                @endphp

                @foreach ($categories as $catKey => $catData)
                    <button
                        wire:click="setCategory('{{ $catKey }}')"
                        type="button"
                        class="rn-btn {{ $selectedCategory === $catKey ? 'active' : '' }}"
                    >
                        <span>{{ $catData['icon'] }}</span>
                        <span>{{ $catData['label'] }}</span>
                    </button>
                @endforeach
            </div>

            <!-- Search Input -->
            <div class="rn-search-box">
                <span class="rn-search-icon">🔍</span>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search updates, features..."
                    class="rn-search-input"
                />
            </div>
        </div>

        <!-- Release Notes Timeline List -->
        <div class="rn-timeline">
            @forelse ($this->releaseNotes as $release)
                @php
                    $accentClass = match($release->category) {
                        'workflow_change' => 'rn-accent-workflow',
                        'feature' => 'rn-accent-feature',
                        'improvement' => 'rn-accent-improvement',
                        'bugfix' => 'rn-accent-bugfix',
                        default => '',
                    };
                    $catClass = match($release->category) {
                        'workflow_change' => 'rn-cat-workflow',
                        'feature' => 'rn-cat-feature',
                        'improvement' => 'rn-cat-improvement',
                        'bugfix' => 'rn-cat-bugfix',
                        default => '',
                    };
                    $catIcon = match($release->category) {
                        'workflow_change' => '⚠️',
                        'feature' => '🚀',
                        'improvement' => '📈',
                        'bugfix' => '🔧',
                        default => '🏷️',
                    };
                @endphp

                <div class="rn-card">
                    <!-- Left Accent Bar -->
                    <div class="rn-accent-bar {{ $accentClass }}"></div>

                    <!-- Header Row -->
                    <div class="rn-header">
                        <div class="rn-meta-left">
                            <!-- Version Badge -->
                            <span class="rn-version-pill">{{ $release->version }}</span>

                            <!-- Category Badge -->
                            <span class="rn-cat-badge {{ $catClass }}">
                                <span>{{ $catIcon }}</span>
                                <span>{{ $release->category_label }}</span>
                            </span>
                        </div>

                        <!-- Date -->
                        <div class="rn-date">
                            <span>📅</span>
                            <span>{{ $release->published_at?->format('M d, Y') ?? 'N/A' }}</span>
                            @if ($release->published_at)
                                <span>•</span>
                                <span>{{ $release->published_at->diffForHumans() }}</span>
                            @endif
                        </div>
                    </div>

                    <!-- Title & Summary -->
                    <div class="rn-title">{{ $release->title }}</div>
                    <div class="rn-summary">{{ $release->summary }}</div>

                    <!-- Procedure Guide (Action Required) -->
                    @if (!empty($release->procedure_guide))
                        <div class="rn-callout">
                            <div class="rn-callout-header">
                                <span>⚠️</span>
                                <span>Procedure &amp; Workflow Guide (Action Required)</span>
                            </div>
                            <div class="rn-callout-body">{{ $release->procedure_guide }}</div>
                        </div>
                    @endif

                    <!-- Key Changes List -->
                    @if (!empty($release->changes) && is_array($release->changes))
                        <div class="rn-changes-section">
                            <div class="rn-changes-title">What Changed:</div>
                            <div>
                                @foreach ($release->changes as $change)
                                    <div class="rn-change-item">
                                        <span style="color: #10b981; font-weight: 800;">✓</span>
                                        <span>{{ $change }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rn-card" style="text-align: center; padding: 3rem 1.5rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">📄</div>
                    <div class="rn-title" style="font-size: 1rem;">No release notes found</div>
                    <div class="rn-summary" style="font-size: 0.8125rem;">Try adjusting your search terms or category filter.</div>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
