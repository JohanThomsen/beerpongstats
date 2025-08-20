<?php

namespace App\Observers;

use App\Enums\GameResult;
use App\Events\GameUpdateEvent;
use App\Models\Game;
use App\Models\GameTeam;
use App\Models\GameUpdate;
use App\Models\GameUser;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class GameUpdateObserver
{
    /**
     * Handle the GameUpdate "created" event.
     * @throws \Exception
     */
    public function created(GameUpdate $gameUpdate): void
    {
        $gameEnded = $gameUpdate->self_cups_left === 0 || $gameUpdate->opponent_cups_left === 0;
        $game = $gameUpdate->game;
        $pivots = [];
        if ($game->is_solo) {
            foreach ($game->users as $user) {
                if ($user->id === $gameUpdate->user_id) {
                    $user->pivot->cups_left = $gameUpdate->self_cups_left;
                } else {
                    $user->pivot->cups_left = $gameUpdate->opponent_cups_left;
                }

                $pivots[] = $user->pivot;
            }
        } else {
            foreach ($game->teams as $team) {
                if ($gameUpdate->user->teams->contains(fn (Team $t) => $t->id === $team->id)) {
                    $team->pivot->cups_left = $gameUpdate->self_cups_left;
                } else {
                    $team->pivot->cups_left = $gameUpdate->opponent_cups_left;
                }
                $pivots[] = $team->pivot;
            }
        }

        if ($gameEnded) {
            $pivots = $this->endGame($pivots, $game);
        }

        foreach ($pivots as $pivot) {
            $pivot->save();
        }

        broadcast(new GameUpdateEvent($gameUpdate, true));
    }

    /**
     * @param array<array-key, GameTeam|GameUser> $pivots
     * @param Game $game
     * @return array<array-key, GameTeam|GameUser>
     * @throws \Exception
     */
    private function endGame(array $pivots, Game $game): array
    {
        foreach ($pivots as $pivot) {
            if ($pivot->cups_left == 0) {
                $gameResult = GameResult::LOSS;
            } elseif ($pivot->cups_left > 0) {
                $gameResult = GameResult::WIN;
            } else {
                Throw new \Exception('Invalid cups left value: ' . $pivot->cups_left);
            }

            $pivot->result = $gameResult;
        }

        $game->is_ended = true;
        $game->save();

        return $pivots;
    }

    /**
     * Handle the GameUpdate "deleted" event.
     */
    public function deleted(GameUpdate $gameUpdate): void
    {
        broadcast(new GameUpdateEvent($gameUpdate, false));
    }
}
