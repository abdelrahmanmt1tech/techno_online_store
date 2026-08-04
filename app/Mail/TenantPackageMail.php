<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantPackageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $tenantName,
        public string $packageName,
        public string $action,
        public ?string $price = null,
        public ?string $duration = null,
        public ?string $expiresAt = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __("dashboard.package_email_subject_{$this->action}"),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.tenant-package', [
                'tenantName' => $this->tenantName,
                'packageName' => $this->packageName,
                'action' => $this->action,
                'price' => $this->price,
                'duration' => $this->duration,
                'expiresAt' => $this->expiresAt,
            ])->render(),
        );
    }
}
