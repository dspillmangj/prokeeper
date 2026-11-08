<?php

namespace App\Mail;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RosterSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $sender,
        public Team $team,
        public string $permission,
        public ?string $note = null
    ) {
    }

    public function envelope(): Envelope
    {
        $senderName = $this->sender->name ?: 'A ProKeeper user';

        return new Envelope(
            subject: "{$senderName} shared a roster with you: {$this->team->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.roster-shared',
        );
    }
}

