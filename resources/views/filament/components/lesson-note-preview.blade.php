@php
    $record = $getRecord();
    $latestVersion = $record?->latestVersion;
    $isWritten = $latestVersion?->isWritten() ?? false;
    $mimeType = $latestVersion?->mime_type ?? '';
    $fileName = $latestVersion?->file_name ?? 'No file';
    $fileSize = $latestVersion?->formatted_file_size ?? 'N/A';
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $isOfficeDocument = in_array($fileExtension, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)
        || in_array($mimeType, [
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ], true);
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

<style>
    .note-document { background: #fff; border: 1px solid #d1d5db; border-radius: 4px; box-shadow: 0 12px 30px rgba(15, 23, 42, .12); color: #1f2937; margin: 0 auto; max-width: 900px; padding: 56px 72px 72px; }
    .note-document__masthead { border-bottom: 2px solid #1f2937; margin-bottom: 32px; padding-bottom: 24px; text-align: center; }
    .note-document__eyebrow { color: #6b7280; font-size: 11px; font-weight: 700; letter-spacing: .16em; margin: 0 0 10px; text-transform: uppercase; }
    .note-document__title { color: #111827; font-size: 25px; font-weight: 800; line-height: 1.2; margin: 0; }
    .note-document__meta { color: #4b5563; display: flex; flex-wrap: wrap; font-size: 13px; gap: 8px 20px; justify-content: center; margin-top: 16px; }
    .note-document__content { color: #374151; font-size: 15px; line-height: 1.8; }
    .note-document__content p { margin: 0 0 14px; }
    .note-document__content h1, .note-document__content h2, .note-document__content h3, .note-document__content h4 { color: #111827; font-weight: 800; line-height: 1.35; margin: 22px 0 9px; }
    .note-document__content h1 { font-size: 21px; }
    .note-document__content h2 { font-size: 18px; }
    .note-document__content h3, .note-document__content h4 { font-size: 16px; }
    .note-document__content strong, .note-document__content b { color: #111827; font-weight: 800; }
    .note-document__content em, .note-document__content i { font-style: italic; }
    .note-document__content ul, .note-document__content ol { margin: 10px 0 16px; padding-left: 28px; }
    .note-document__content ul { list-style: disc; }
    .note-document__content ol { list-style: decimal; }
    .note-document__content li { margin: 4px 0; padding-left: 4px; }
    .note-document__content blockquote { border-left: 3px solid #9ca3af; color: #4b5563; font-style: italic; margin: 16px 0; padding-left: 16px; }
    .note-document__content table { border-collapse: collapse; margin: 18px 0; width: 100%; }
    .note-document__content th, .note-document__content td { border: 1px solid #d1d5db; padding: 8px 10px; text-align: left; }
    .note-document__content th { background: #f3f4f6; font-weight: 800; }
    @media (max-width: 700px) { .note-document { padding: 32px 22px 42px; } .note-document__title { font-size: 21px; } }
</style>

@if($latestVersion)
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-b border-gray-200 pb-4 text-sm dark:border-gray-700">
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $record?->subject?->name }}</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $record?->classroom?->name }}</span>
            <span class="text-gray-500 dark:text-gray-400">Week {{ $record?->week_number }}</span>
            <span class="text-gray-500 dark:text-gray-400">Teacher: {{ $record?->teacher?->name }}</span>
        </div>

        @if($isWritten)
            <article class="note-document">
                <header class="note-document__masthead">
                    <p class="note-document__eyebrow">Lesson Note</p>
                    <h2 class="note-document__title">{{ $latestVersion->title ?: ($record?->subject?->name . ' - Week ' . $record?->week_number) }}</h2>
                    <div class="note-document__meta">
                        <span><strong>Subject:</strong> {{ $record?->subject?->name }}</span>
                        <span><strong>Class:</strong> {{ $record?->classroom?->name }}</span>
                        <span><strong>Week:</strong> {{ $record?->week_number }}</span>
                        @if($latestVersion->created_at)<span><strong>Submitted:</strong> {{ $latestVersion->created_at->format('M d, Y') }}</span>@endif
                    </div>
                </header>
                <div class="note-document__content">{!! $latestVersion->content !!}</div>
            </article>

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
            @elseif($isOfficeDocument)
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
