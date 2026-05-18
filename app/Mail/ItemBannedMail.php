<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ItemBannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $authorEmail;
    public string $banReason;
    public string $submissionContent;

    /**
     * Create a new message instance.
     */
    public function __construct(string $authorEmail, string $banReason, string $submissionContent)
    {
        $this->authorEmail = $authorEmail;
        $this->banReason = $banReason;
        $this->submissionContent = $submissionContent;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Account Suspension & Permanent Ban Notice - ReviewQueue',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.item_banned',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
