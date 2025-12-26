<?php

namespace App\Mail\Invitation;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The invitation instance.
     *
     * @var \App\Models\Invitation
     */
    public Invitation $invitation;

    /**
     * Create a new message instance.
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You have been invited to join ' . $this->invitation->organization->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invitations.organization',
            with: [
                'invitation' => $this->invitation,
                'acceptUrl' => url('/invitations/accept?token=' . $this->invitation->token),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}