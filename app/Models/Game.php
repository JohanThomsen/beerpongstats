<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $type
 * @property bool $is_ended
 * @property bool $is_solo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\GameUpdate> $gameUpdates
 * @property-read int|null $game_updates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $teams
 * @property-read int|null $teams_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\GameFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereIsEnded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereIsSolo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Game whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Game extends Model
{
    protected static $unguarded = true;
    /** @use HasFactory<\Database\Factories\GameFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_ended' => 'boolean',
            'is_solo' => 'boolean',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot([
            'result',
            'cups_left',
        ]);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot([
            'result',
            'cups_left',
        ]);
    }

    public function gameUpdates(): HasMany
    {
        return $this->hasMany(GameUpdate::class);
    }

    public function isUserInGame(int $userId): bool
    {
        return $this->users()->where('users.id', $userId)->exists() ||
            $this->teams()->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })->exists();
    }
}
