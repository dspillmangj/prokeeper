<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameEventLogged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $gameId;

    public string $mutationType;

    public ?string $clientUuid;

    public ?array $eventData;

    public array $gameState;

    public function __construct(
        int $gameId,
        string $mutationType,
        array $gameState,
        ?array $eventData = null,
        ?string $clientUuid = null
    ) {
        $this->gameId = $gameId;
        $this->mutationType = $mutationType;
        $this->gameState = $gameState;
        $this->eventData = $eventData;
        $this->clientUuid = $clientUuid;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('game.'.$this->gameId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.event';
    }

    public function broadcastWith(): array
    {
        return [
            'gameId' => $this->gameId,
            'mutationType' => $this->mutationType,
            'clientUuid' => $this->clientUuid,
            'eventData' => $this->eventData,
            'gameState' => $this->gameState,
        ];
    }
}
