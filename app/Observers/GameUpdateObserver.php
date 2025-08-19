<?php

namespace App\Observers;

use App\Events\GameUpdateEvent;
use App\Models\GameUpdate;

class GameUpdateObserver
{
    /**
     * Handle the GameUpdate "created" event.
     */
    public function created(GameUpdate $gameUpdate): void
    {
        broadcast(new GameUpdateEvent($gameUpdate, true));
    }

    /**
     * Handle the GameUpdate "deleted" event.
     */
    public function deleted(GameUpdate $gameUpdate): void
    {
        broadcast(new GameUpdateEvent($gameUpdate, false));
    }
}
