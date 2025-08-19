<?php

namespace App\Http\Controllers;

use App\DataObjects\Requests\CreateGameUpdateRequestDataObject;
use App\Models\Game;
use App\Models\GameUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GameUpdateController extends Controller
{
    public function store(CreateGameUpdateRequestDataObject $request, Game $game): JsonResponse
    {
        // Editor must be authenticated and a participant in the game
        if (!Auth::check() || ! $game->isUserInGame(Auth::id())) {
            return response()->json(['message' => 'You are not allowed to edit this game.'], 403);
        }

        // Do not allow updates when the game is ended
        if ($game->is_ended) {
            return response()->json(['message' => 'Game has ended. Updates are disabled.'], 403);
        }

        // If a user_id is provided, it must reference a player in this game
        if ($request->user_id && ! $game->isUserInGame($request->user_id)) {
            return response()->json(['message' => 'User is not a participant in this game.'], 403);
        }

        $data = $request->toArray();
        $data['game_id'] = $game->id;

        $gameUpdate = GameUpdate::create($data);

        return response()->json($gameUpdate, 201);
    }

    public function deleteLatestByGame(Game $game): JsonResponse
    {
        $latest = $game->gameUpdates()->latest('id')->first();

        if (!$latest) {
            return response()->json(['message' => 'No game updates found for this game.'], 404);
        }

        $latest->delete();

        return response()->json(['message' => 'Latest game update deleted.']);
    }

    public function latestByGame(Game $game): JsonResponse
    {
        $latest = $game->gameUpdates()->latest('id')->first();

        if (!$latest) {
            return response()->json(['message' => 'No game updates found for this game.'], 404);
        }

        return response()->json($latest);
    }
}
