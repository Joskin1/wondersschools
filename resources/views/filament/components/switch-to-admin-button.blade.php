@if(auth()->user()?->isAdmin() || auth()->user()?->isSudo() || auth()->user()?->hasAnyAdminPermission())
    <div class="flex items-center me-2">
        <a href="{{ url('/admin') }}"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/60 dark:text-amber-300 dark:hover:bg-amber-900/60 border border-amber-300 dark:border-amber-700 transition shadow-sm"
           title="Switch to Admin Panel">
            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
            <span class="hidden sm:inline font-medium">Admin Panel</span>
        </a>
    </div>
@endif
