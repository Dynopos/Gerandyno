<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Base64-encoded attachment bytes. Queued jobs are JSON-encoded for
     * storage (database/redis drivers), and raw binary (a PDF/XLSX) is not
     * valid UTF-8, so it must never be held in a public property as-is —
     * only the base64 form, decoded back in attachments() when the job
     * actually runs.
     */
    private readonly string $attachmentContentBase64;

    public function __construct(
        public readonly string $reportTitle,
        public readonly string $periodLabel,
        public readonly string $companyName,
        public readonly string $recipientName,
        string $attachmentContent,
        public readonly string $attachmentFilename,
        public readonly string $attachmentMime,
    ) {
        $this->attachmentContentBase64 = base64_encode($attachmentContent);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->reportTitle} - {$this->companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.report',
            with: [
                'reportTitle' => $this->reportTitle,
                'periodLabel' => $this->periodLabel,
                'companyName' => $this->companyName,
                'recipientName' => $this->recipientName,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->attachmentContent(), $this->attachmentFilename)
                ->withMime($this->attachmentMime),
        ];
    }

    public function attachmentContent(): string
    {
        return base64_decode($this->attachmentContentBase64);
    }
}
