<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
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
 */
	class Game extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $game_id
 * @property int $team_id
 * @property \App\Enums\GameResult|null $result
 * @property int|null $cups_left
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereCupsLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameTeam whereTeamId($value)
 */
	class GameTeam extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $game_id
 * @property \App\Enums\GameUpdateType $type
 * @property array<array-key, mixed> $cup_positions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Game $game
 * @method static \Database\Factories\GameUpdateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereCupPositions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUpdate whereUpdatedAt($value)
 */
	class GameUpdate extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $game_id
 * @property int $user_id
 * @property \App\Enums\GameResult|null $result
 * @property int|null $cups_left
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereCupsLeft($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereGameId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereResult($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GameUser whereUserId($value)
 */
	class GameUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Game> $games
 * @property-read int|null $games_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\TeamFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 */
	class Team extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $user_id
 * @property int $team_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TeamUser whereUserId($value)
 */
	class TeamUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Game> $games
 * @property-read int|null $games_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Team> $teams
 * @property-read int|null $teams_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 */
	class User extends \Eloquent {}
}

