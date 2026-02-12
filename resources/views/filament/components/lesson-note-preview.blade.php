@php
    $record = $getRecord();
    $latestVersion = $record?->latestVersion;
    $mimeType = $latestVersion?->mime_type ?? '';
    $fileName = $latestVersion?->file_name ?? 'No file';
    $fileSize = $latestVersion?->formatted_file_size ?? 'N/A';

    $downloadUrl = null;
    if ($latestVersion) {
        try {
            $downloadUrl = $latestVersion->getDownloadUrl();
        } catch (\Throwable $e) {
            // Storage may not be configured
        }
    }
@endphp

@if($latestVersion && $downloadUrl)
    <div class="space-y-4">
        {{-- File info bar --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-3">
                @if(str_starts_with($mimeType, 'image/'))
                    <x-heroicon-o-photo class="h-8 w-8 text-blue-500" />
                @elseif($mimeType === 'application/pdf')
                    <x-heroicon-o-document-text class="h-8 w-8 text-red-500" />
                @else
                    <x-heroicon-o-document class="h-8 w-8 text-gray-500" />
                @endif
                <div>
                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $fileName }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $fileSize }} &middot; {{ strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)) }}</p>
                </div>
            </div>
            <a
                href="{{ $downloadUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-primary bg-primary-600 text-white hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400 px-4 py-2 text-sm inline-flex gap-1.5"
            >
                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                Download
            </a>
        </div>

        {{-- File preview --}}
        @if(str_starts_with($mimeType, 'image/'))
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                <img
                    src="{{ $downloadUrl }}"
                    alt="{{ $fileName }}"
                    class="mx-auto max-h-[700px] w-auto"
                    loading="lazy"
                />
            </div>
        @elseif($mimeType === 'application/pdf')
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" style="height: 700px;">
                <iframe
                    src="{{ $downloadUrl }}#toolbar=1&navpanes=0"
                    width="100%"
                    height="100%"
                    style="border: none;"
                    title="Lesson Note Preview"
                ></iframe>
            </div>
        @elseif(in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ]))
            <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" style="height: 700px;">
                <iframe
                    src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($downloadUrl) }}"
                    width="100%"
                    height="100%"
                    style="border: none;"
                    title="Lesson Note Preview"
                ></iframe>
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-8 dark:border-gray-700 dark:bg-gray-800">
                <x-heroicon-o-document class="mb-3 h-12 w-12 text-gray-400" />
                <p class="text-sm text-gray-600 dark:text-gray-400">Preview is not available for this file type.</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-500">Use the download button above to view the file.</p>
            </div>
        @endif
    </div>
@else
    <div class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-8 dark:border-gray-700 dark:bg-gray-800">
        <x-heroicon-o-document class="mb-3 h-12 w-12 text-gray-400" />
        <p class="text-sm text-gray-600 dark:text-gray-400">No file has been uploaded yet.</p>
    </div>
@endif
