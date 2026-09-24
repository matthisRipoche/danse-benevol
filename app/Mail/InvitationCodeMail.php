<?php

namespace App\Mail;

use App\Models\InvitationCode;
use App\Models\User;
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
        $code = $this->invitationCode->code;
        $isReturningVolunteer = User::where('email', $this->invitationCode->email)->exists();

        return new Content(
            markdown: 'emails.invitation-code',
            with: [
                'code' => $code,
                'isReturningVolunteer' => $isReturningVolunteer,
                'actionUrl' => $isReturningVolunteer ? route('edition.join', ['code' => $code]) : route('register', ['code' => $code]),
                'editionName' => $this->invitationCode->edition->name,
                'expiresAt' => $this->invitationCode->expires_at,
            ],
        );
    }
}
