<div class="space-y-4">
    <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 pb-2 border-b border-gray-200 dark:border-gray-700">
        <div>
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $batch->session->name ?? 'Session' }}</span>
            &bull;
            <span>{{ $batch->term->name ?? 'Term' }}</span>
        </div>
        <div>
            Total: <span class="font-bold text-gray-900 dark:text-white">{{ $pins->count() }}</span> PINs
        </div>
    </div>

    <div class="max-h-96 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left text-xs divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                <tr>
                    <th class="px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">Serial No.</th>
                    <th class="px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">PIN Code</th>
                    <th class="px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">Status</th>
                    <th class="px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">Redeemed By</th>
                    <th class="px-3 py-2 font-semibold text-gray-600 dark:text-gray-300">Used At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                @foreach ($pins as $pin)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                        <td class="px-3 py-2 font-mono text-gray-500 dark:text-gray-400">{{ $pin->serial_number }}</td>
                        <td class="px-3 py-2 font-mono font-bold text-gray-900 dark:text-white tracking-wider">{{ $pin->pin }}</td>
                        <td class="px-3 py-2">
                            @if ($pin->is_used)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400">
                                    Used
                                </span>
                            @else
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                    Unused
                                </span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                            {{ $pin->student->full_name ?? ($pin->is_used ? 'Student #' . $pin->student_id : '—') }}
                        </td>
                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">
                            {{ $pin->used_at?->format('M d, Y h:i A') ?? '—' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
