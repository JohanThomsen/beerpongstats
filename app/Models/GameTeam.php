<?php

namespace App\Models;

use App\Enums\GameResult;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $game_id
 * @property int $team_id
 * @property GameResult|null $result
 * @property int|null $cups_left
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereCupsLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereTeamId($value)
 * @mixin \Eloquent
 */
class GameTeam extends Pivot
{
    protected static $unguarded = true;

    protected function casts(): array
    {
        return [
            'result' => GameResult::class,
        ];
    }
}
