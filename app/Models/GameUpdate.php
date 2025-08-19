<?php

namespace App\Models;

use App\Enums\GameUpdateType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $game_id
 * @property int|null $user_id
 * @property GameUpdateType $type
 * @property array|null $self_cup_positions
 * @property array|null $opponent_cup_positions
 * @property int $self_cups_left
 * @property int $opponent_cups_left
 * @property int $affected_cup
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Game $game
 * @property-read \App\Models\User|null $user
 * @method static \Database\Factories\GameUpdateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereSelfCupPositions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereOpponentCupPositions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereSelfCupsLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereOpponentCupsLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereAffectedCup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GameUpdate extends Model
{
    protected static $unguarded = true;
    /** @use HasFactory<\Database\Factories\GameUpdateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => GameUpdateType::class,
            'self_cup_positions' => 'array',
            'opponent_cup_positions' => 'array',
            'self_cups_left' => 'integer',
            'opponent_cups_left' => 'integer',
            'affected_cup' => 'integer',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
