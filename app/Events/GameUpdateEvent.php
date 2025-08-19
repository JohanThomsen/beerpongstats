<?php

namespace App\Events;

use App\Models\GameUpdate;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GameUpdateEvent implements ShouldBroadcastNow
{
    public GameUpdate $gameUpdate;
    private bool $created;

    public function __construct(GameUpdate $gameUpdate, bool $created)
    {
        $this->gameUpdate = $gameUpdate;
        $this->created = $created;
    }

    public function broadcastOn(): array
    {
        return [new Channel(sprintf('game-updates.%s', $this->gameUpdate->game_id))];
    }

    public function broadcastAs(): string
    {
        return 'game-update';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->gameUpdate->user_id,
            'game_id' => $this->gameUpdate->game_id,
            'type' => $this->gameUpdate->type->value,
            'self_cup_positions' => $this->gameUpdate->self_cup_positions,
            'opponent_cup_positions' => $this->gameUpdate->opponent_cup_positions,
            'affected_cup' => $this->gameUpdate->affected_cup,
            'self_cups_left' => $this->gameUpdate->self_cups_left,
            'opponent_cups_left' => $this->gameUpdate->opponent_cups_left,
            'created_at' => $this->gameUpdate->created_at,
            'created' => $this->created,
        ];
    }
}

