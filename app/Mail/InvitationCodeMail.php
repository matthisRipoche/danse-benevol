<?php

namespace App\Mail;

use App\Models\InvitationCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationCodeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public InvitationCode $invitationCode)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ton code d'invitation — Salon de la Danse",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitation-code',
            with: [
                'code' => $this->invitationCode->code,
                'registerUrl' => route('register', ['code' => $this->invitationCode->code]),
                'expiresAt' => $this->invitationCode->expires_at,
            ],
        );
    }
}
