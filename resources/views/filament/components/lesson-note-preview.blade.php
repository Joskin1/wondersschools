@php
    $record = $getRecord();
    $latestVersion = $record?->latestVersion;
    $isWritten = $latestVersion?->isWritten() ?? false;
    $mimeType = $latestVersion?->mime_type ?? '';
    $fileName = $latestVersion?->file_name ?? 'No file';
    $fileSize = $latestVersion?->formatted_file_size ?? 'N/A';
    $imageUrls = $latestVersion ? $latestVersion->getImageUrls() : [];

    $downloadUrl = null;
    if ($latestVersion && !$isWritten && $latestVersion->file_path) {
        try {
            $downloadUrl = $latestVersion->getDownloadUrl();
        } catch (\Throwable $e) {
            // Storage may not be configured
        }
    }
@endphp

@if($latestVersion)
    <div class="space-y-6">
        @if($isWritten)
            {{-- Written Lesson Note View --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-700">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                📝 Written Lesson Note
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-md bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                Week {{ $record?->week_number }}
                            </span>
                        </div>
                        <h2 class="mt-2 text-xl font-bold tracking-tight text-gray-900 dark:text-gray-100">
                            {{ $latestVersion->title ?: ($record?->subject?->name . ' - Week ' . $record?->week_number) }}
                        </h2>
                    </div>

                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                        @if($latestVersion->created_at)
                            Submitted {{ $latestVersion->created_at->format('M d, Y · h:i A') }}
                        @endif
                    </div>
                </div>

                {{-- Written Content Body --}}
                <div class="prose max-w-none text-gray-800 dark:prose-invert dark:text-gray-200 leading-relaxed space-y-4">
                    {!! $latestVersion->content !!}
                </div>
            </div>

            {{-- Optional Attached Diagrams & Images --}}
            @if(!empty($imageUrls))
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="mb-4 flex items-center gap-2 border-b border-gray-100 pb-3 dark:border-gray-700">
                        <x-heroicon-o-photo class="h-5 w-5 text-indigo-500" />
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                            Attached Diagrams & Illustrations ({{ count($imageUrls) }})
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach($imageUrls as $index => $imageUrl)
                            <div class="group relative overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                                <a href="{{ $imageUrl }}" target="_blank" rel="noopener noreferrer" class="block aspect-video w-full overflow-hidden">
                                    <img
                                        src="{{ $imageUrl }}"
                                        alt="Lesson Diagram #{{ $index + 1 }}"
                                        class="h-full w-full object-cover transition duration-200 group-hover:scale-105"
                                        loading="lazy"
                                    />
                                </a>
                                <div class="flex items-center justify-between p-2.5 text-xs text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Image #{{ $index + 1 }}</span>
                                    <a
                                        href="{{ $imageUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                    >
                                        <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                        Full Size
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        @elseif($downloadUrl)
            {{-- Uploaded File Bar --}}
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
        @else
            <div class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-8 dark:border-gray-700 dark:bg-gray-800">
                <x-heroicon-o-document class="mb-3 h-12 w-12 text-gray-400" />
                <p class="text-sm text-gray-600 dark:text-gray-400">Lesson note file is being processed.</p>
            </div>
        @endif
    </div>
@else
    <div class="flex flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-8 dark:border-gray-700 dark:bg-gray-800">
        <x-heroicon-o-document class="mb-3 h-12 w-12 text-gray-400" />
        <p class="text-sm text-gray-600 dark:text-gray-400">No lesson note content or file has been submitted yet.</p>
    </div>
@endif
