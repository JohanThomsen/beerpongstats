<?php

namespace App\Services;

use App\DataObjects\GameThrowStatisticsDataObject;
use App\DataObjects\MatchHistoryGameDataObject;
use App\DataObjects\TeamGameDataObject;
use App\DataObjects\UserGameDataObject;
use App\Enums\GameUpdateType;
use App\Models\Game;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GameService
{
    public function __construct()
    {
    }

    /**
     * @param int $userId
     * @param int $perPage
     * @param int $page
     * @return LengthAwarePaginator<Game>
     */
    public function getGamesPaginator(int $userId, int $perPage, int $page): LengthAwarePaginator
    {
        return Game::query()
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('users.id', $userId);
            })->orWhereHas('teams', function ($query) use ($userId) {
                $query->whereHas('users', function ($query) use ($userId) {
                    $query->where('users.id', $userId);
                });
            })->with([
                'users',
                'teams.users',
                'gameUpdates',
            ])->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param LengthAwarePaginator $gamesPaginator
     * @param User $user
     * @return Collection<array-key, MatchHistoryGameDataObject>
     */
    public function convertGamesPaginatorToMatchHistoryGameDataObjects(LengthAwarePaginator $gamesPaginator, User $user): Collection
    {
        return $gamesPaginator->getCollection()->map(function (Game $game) use ($user) {
            return $this->convertGameToMatchHistoryGameDataObject($game, $user);
        });
    }

    /**
     * @param $game
     * @param User $user
     * @return GameThrowStatisticsDataObject
     */
    public function getThrowStatistics($game, User $user): GameThrowStatisticsDataObject
    {
        $userThrowUpdates = $game->gameUpdates
            ->where('user_id', $user->id)
            ->whereIn('type', GameUpdateType::throwResults());

        $total = $userThrowUpdates->count();
        $hits = $userThrowUpdates->where('type', GameUpdateType::HIT)->count();
        $edgeHits = $userThrowUpdates->where('type', GameUpdateType::EDGE)->count();
        $misses = $userThrowUpdates->where('type', GameUpdateType::MISS)->count();

        $hitRate = $hits > 0 ? round(($hits / ($total)) * 100, 1) : 0;
        $edgeHitRate = $edgeHits > 0 ? round(($edgeHits / ($total)) * 100, 1) : 0;
        $missRate = $misses > 0 ? round(($misses / ($total)) * 100, 1) : 0;

        return GameThrowStatisticsDataObject::from([
            'total' => $total,
            'hits' => $hits,
            'edgeHits' => $edgeHits,
            'misses' => $misses,
            'hitRate' => $hitRate,
            'edgeHitRate' => $edgeHitRate,
            'missRate' => $missRate,
        ]);
    }

    private function convertGameToMatchHistoryGameDataObject(Game $game, User $user): MatchHistoryGameDataObject
    {
        $isSolo = $game->is_solo;

        $gameThrowStatistics = $this->getThrowStatistics($game, $user);

        $baseArray = [
            'id' => $game->id,
            'isSolo' => $isSolo,
            'type' => $game->type,
            'isEnded' => $game->is_ended,
            'totalThrows' => $gameThrowStatistics->total,
            'hits' => $gameThrowStatistics->hits,
            'hitRate' => $gameThrowStatistics->hitRate,
            'edgeHits' => $gameThrowStatistics->edgeHits,
            'edgeHitRate' => $gameThrowStatistics->edgeHitRate,
            'misses' => $gameThrowStatistics->misses,
            'missRate' => $gameThrowStatistics->missRate,
            'updatedAt' => $game->updated_at,
            'createdAt' => $game->created_at,
        ];

        if ($isSolo) {
            $primaryUser = $game->users->where('id', $user->id)->firstOrFail();
            $secondaryUser = $game->users->where('id', '!=', $user->id)->first();

            $participantData = [
                'primaryUser' => UserGameDataObject::from([
                    'id' => $primaryUser->id,
                    'name' => $primaryUser->name,
                    'result' => $primaryUser->pivot->result,
                    'cupsLeft' => $primaryUser->pivot->cups_left,
                ]),
                'secondaryUser' => $secondaryUser ? UserGameDataObject::from([
                    'id' => $secondaryUser->id,
                    'name' => $secondaryUser->name,
                    'result' => $secondaryUser->pivot->result,
                    'cupsLeft' => $secondaryUser->pivot->cups_left,
                ]) : null,
                'primaryTeam' => null,
                'secondaryTeam' => null,
            ];
        } else {
            $primaryTeam = $game->teams->first(function ($team) use ($user) {
                return $team->users->contains($user);
            });

            //TODO this seems like it never finds the secondary team, if it is the losing team
            $secondaryTeam = $game->teams->first(function ($team) use ($primaryTeam) {
                return $team->id !== $primaryTeam->id;
            });

            $participantData = [
                'primaryUser' => null,
                'secondaryUser' => null,
                'primaryTeam' => TeamGameDataObject::from([
                    'id' => $primaryTeam->id,
                    'name' => $primaryTeam->name,
                    'result' => $primaryTeam->pivot->result,
                    'cupsLeft' => $primaryTeam->pivot->cups_left,
                    'users' => $primaryTeam->users->map(function ($user) {
                        return UserGameDataObject::from([
                            'id' => $user->id,
                            'name' => $user->name,
                            'result' => null, // Individual users in team games don't have result
                            'cupsLeft' => null, // Individual users in team games don't have cups_left
                        ]);
                    })->toArray(),
                ]),
                'secondaryTeam' => $secondaryTeam ? TeamGameDataObject::from([
                    'id' => $secondaryTeam->id,
                    'name' => $secondaryTeam->name,
                    'result' => $secondaryTeam->pivot->result,
                    'cupsLeft' => $secondaryTeam->pivot->cups_left,
                    'users' => $secondaryTeam->users->map(function ($user) {
                        return UserGameDataObject::from([
                            'id' => $user->id,
                            'name' => $user->name,
                            'result' => null, // Individual users in team games don't have result
                            'cupsLeft' => null, // Individual users in team games don't have cups_left
                        ]);
                    })->toArray(),
                ]) : null,
            ];
        }

        return MatchHistoryGameDataObject::from(array_merge($baseArray, $participantData));
    }
}
