<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DatabaseBackupMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<int, array{path: string, name: string, mime: string, size?: int}> $filesToAttach
     * @param array<string, int> $tableSummary
     */
    public function __construct(
        public array $filesToAttach,
        public string $tenantName,
        public string $databaseName,
        public string $driver,
        public string $generatedAt,
        public string $summarySize,
        public array $tableSummary = []
    ) {}

    public function envelope(): Envelope
    {
        $date = now()->format('M j, Y');
        return new Envelope(
            subject: "[Wonders Backup] {$this->tenantName} Database Snapshot - {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.database-backup',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->filesToAttach as $file) {
            if (!empty($file['path']) && file_exists($file['path'])) {
                $attachments[] = Attachment::fromPath($file['path'])
                    ->as($file['name'])
                    ->withMime($file['mime'] ?? 'application/gzip');
            }
        }

        return $attachments;
    }
}
