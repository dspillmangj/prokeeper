<?php

namespace App\Mail;

use App\Models\Game;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GameSharedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $sender,
        public Game $game,
        public string $permission,
        public ?string $note = null
    ) {
    }

    public function envelope(): Envelope
    {
        $senderName = $this->sender->name ?: 'A ProKeeper user';
        $matchup = "{$this->game->home_display_name} vs {$this->game->away_display_name}";

        return new Envelope(
            subject: "{$senderName} shared a game with you: {$matchup}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.game-shared',
        );
    }
}
