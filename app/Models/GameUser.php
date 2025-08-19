<?php

namespace App\Models;

use App\Enums\GameResult;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $id
 * @property int $game_id
 * @property int $user_id
 * @property GameResult|null $result
 * @property int|null $cups_left
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereCupsLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereUserId($value)
 * @mixin \Eloquent
 */
class GameUser extends Pivot
{
    protected function casts(): array
    {
        return [
            'result' => GameResult::class,
        ];
    }
}
