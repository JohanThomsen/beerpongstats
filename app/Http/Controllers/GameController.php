<?php

namespace App\Http\Controllers;

use App\DataObjects\Requests\CreateGameRequestDataObject;
use App\DataObjects\Requests\PaginationRequestDataObject;
use App\Enums\GameType;
use App\Enums\GameUpdateType;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use App\Services\GameService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class GameController extends Controller
{
    public function UserMatchHistory(User $user, PaginationRequestDataObject $pagination, GameService $gameService)
    {
        $gamesPaginator = $gameService->getGamesPaginator($user->id, $pagination->perPage, $pagination->page);

        //TODO add filter for solo games
        //TODO add filter for team games

        $games = $gameService->convertGamesPaginatorToMatchHistoryGameDataObjects($gamesPaginator, $user);

        return Inertia::render('MatchHistory', [
            'user' => $user,
            'games' => $games,
            'pagination' => [
                'currentPage' => $gamesPaginator->currentPage(),
                'lastPage' => $gamesPaginator->lastPage(),
                'perPage' => $gamesPaginator->perPage(),
                'total' => $gamesPaginator->total(),
                'from' => $gamesPaginator->firstItem(),
                'to' => $gamesPaginator->lastItem(),
                'hasMorePages' => $gamesPaginator->hasMorePages(),
                'prevPageUrl' => $gamesPaginator->previousPageUrl(),
                'nextPageUrl' => $gamesPaginator->nextPageUrl(),
            ]
        ]);
    }

    public function create()
    {
        if (!Auth::check()) {
            abort(403, 'You must be logged in to create a game.');
        }

        $users = User::all();
        $currentUser = User::find(Auth::id());

        // Get teams that include the current user
        $userTeams = Team::whereHas('users', function ($query) {
            $query->where('users.id', Auth::id());
        })->with('users')->get();

        // Get teams that don't include the current user
        $opponentTeams = Team::whereDoesntHave('users', function ($query) {
            $query->where('users.id', Auth::id());
        })->with('users')->get();

        return Inertia::render('Games/Create', [
            'users' => $users,
            'currentUser' => $currentUser,
            'userTeams' => $userTeams,
            'opponentTeams' => $opponentTeams,
        ]);
    }

    public function store(CreateGameRequestDataObject $request)
    {
        if ($request->is_solo) {
            // Check if the authenticated user is included in the solo game
            if (!in_array(Auth::id(), $request->user_ids)) {
                abort(403, 'You can only create games that you are a participant of.');
            }
        } else {
            // Check if the authenticated user is in one of the selected teams
            $userInSelectedTeams = Team::whereIn('id', $request->team_ids)
                ->whereHas('users', function ($query) {
                    $query->where('users.id', Auth::id());
                })->exists();

            if (!$userInSelectedTeams) {
                abort(403, 'You can only create games with teams you are a member of.');
            }
        }

        $game = Game::create([
            'type' => $request->type,
            'is_solo' => $request->is_solo,
            'is_ended' => false,
        ]);

        $cupsLeft = $game->type == GameType::TEN_CUP ? 10 : 6; // Default starting cups for each player/team

        if ($request->is_solo) {
            // Attach users for solo game
            $userPivotData = [];
            foreach ($request->user_ids as $userId) {
                $userPivotData[$userId] = [
                    'result' => null,
                    'cups_left' => $cupsLeft, // Default starting cups
                ];
            }
            $game->users()->attach($userPivotData);
        } else {
            // Attach teams for team game
            $teamPivotData = [];
            foreach ($request->team_ids as $teamId) {
                $teamPivotData[$teamId] = [
                    'result' => null,
                    'cups_left' => $cupsLeft, // Default starting cups
                ];
            }
            $game->teams()->attach($teamPivotData);
        }

        return Redirect::route('user.match-history', ['user' => Auth::id()])->with('success', 'Game created successfully.');
    }

    public function live(Game $game)
    {
        $latest = $game->gameUpdates()->latest('id')->first();

        $isParticipant = Auth::check() ? $game->isUserInGame(Auth::id()) : false;

        $participants = [];
        if ($game->is_solo) {
            $participants = [
                'solo' => $game->users()->select('users.id', 'users.name')->get()->values(),
            ];
        } else {
            $participants = [
                'teams' => $game->teams()->with(['users:id,name'])->get(['teams.id', 'teams.name'])->values(),
            ];
        }

        // Build per-user throw stats for this game
        $userIds = collect();
        if ($game->is_solo) {
            $userIds = $game->users()->pluck('users.id');
        } else {
            $userIds = $game->teams()->with('users:id')->get()
                ->pluck('users')->flatten(1)->pluck('id')->unique()->values();
        }

        $stats = [];
        foreach ($userIds as $uid) {
            $stats[(int)$uid] = [
                'total' => 0,
                'hits' => 0,
                'edges' => 0,
                'misses' => 0,
                'hitRate' => 0.0,
                'edgeRate' => 0.0,
                'missRate' => 0.0,
            ];
        }

        $updates = $game->gameUpdates()
            ->whereIn('type', [GameUpdateType::HIT, GameUpdateType::EDGE, GameUpdateType::MISS])
            ->whereNotNull('user_id')
            ->get(['user_id', 'type']);

        foreach ($updates as $u) {
            $uid = (int)$u->user_id;
            if (!isset($stats[$uid])) {
                $stats[$uid] = [
                    'total' => 0,
                    'hits' => 0,
                    'edges' => 0,
                    'misses' => 0,
                    'hitRate' => 0.0,
                    'edgeRate' => 0.0,
                    'missRate' => 0.0,
                ];
            }
            $stats[$uid]['total']++;
            if ($u->type === GameUpdateType::HIT) $stats[$uid]['hits']++;
            elseif ($u->type === GameUpdateType::EDGE) $stats[$uid]['edges']++;
            elseif ($u->type === GameUpdateType::MISS) $stats[$uid]['misses']++;
        }

        foreach ($stats as $uid => &$s) {
            $total = max(0, (int)$s['total']);
            $s['hitRate'] = $total > 0 ? round(($s['hits'] / $total) * 100, 1) : 0.0;
            $s['edgeRate'] = $total > 0 ? round(($s['edges'] / $total) * 100, 1) : 0.0;
            $s['missRate'] = $total > 0 ? round(($s['misses'] / $total) * 100, 1) : 0.0;
        }
        unset($s);

        return Inertia::render('Games/Live', [
            'game' => [
                'id' => $game->id,
                'type' => $game->type,
                'is_solo' => $game->is_solo,
                'is_ended' => $game->is_ended,
            ],
            'participants' => $participants,
            'latestGameUpdate' => $latest,
            'isParticipant' => $isParticipant,
            'authUserId' => Auth::id(),
            'throwStats' => $stats,
        ]);
    }
}
