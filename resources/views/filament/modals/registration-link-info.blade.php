<div class="space-y-4">
    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Registration URL:</p>
        <div class="bg-white dark:bg-gray-900 p-3 rounded border border-gray-200 dark:border-gray-700">
            <code class="text-sm text-gray-900 dark:text-gray-100 break-all">{{ $url }}</code>
        </div>
    </div>

    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg border border-yellow-200 dark:border-yellow-800">
        <p class="text-sm text-yellow-800 dark:text-yellow-200">
            <strong>⚠️ Note:</strong> {{ $note }}
        </p>
    </div>

    <div class="text-sm text-gray-600 dark:text-gray-400">
        <p><strong>Expires:</strong> {{ $expiresAt }}</p>
    </div>
</div>
