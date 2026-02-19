<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Registration URL:</p>
        <div class="bg-white dark:bg-gray-900 p-3 rounded border border-gray-200 dark:border-gray-700">
            <code id="registration-url-{{ $studentId }}" class="text-sm text-gray-900 dark:text-gray-100 break-all select-all">{{ $url }}</code>
        </div>
    </div>

    <button 
        type="button" 
        onclick="
            const url = '{{ $url }}';
            navigator.clipboard.writeText(url).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Failed to copy link. Please copy it manually.');
            });
        "
        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-all"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
        </svg>
        Copy Link
    </button>

    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
        <p class="text-sm text-yellow-800 dark:text-yellow-200">
            <strong>⚠️ Note:</strong> {{ $note }}
        </p>
    </div>

    <div class="text-sm text-gray-600 dark:text-gray-400">
        <p><strong>Expires:</strong> {{ $expiresAt }}</p>
    </div>
</div>
