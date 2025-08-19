<?php

namespace App\Http\Controllers;

use App\DataObjects\Requests\CreateGameUpdateRequestDataObject;
use App\Models\Game;
use App\Models\GameUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class GameUpdateController extends Controller
{
    public function store(CreateGameUpdateRequestDataObject $request, Game $game): RedirectResponse
    {
        // Editor must be authenticated and a participant in the game
        if (!Auth::check() || ! $game->isUserInGame(Auth::id())) {
            return back()->withErrors(['message' => 'You are not allowed to edit this game.']);
        }

        // Do not allow updates when the game is ended
        if ($game->is_ended) {
            return back()->withErrors(['message' => 'Game has ended. Updates are disabled.']);
        }

        // If a user_id is provided, it must reference a player in this game
        if ($request->user_id && ! $game->isUserInGame($request->user_id)) {
            return back()->withErrors(['message' => 'User is not a participant in this game.']);
        }

        $data = $request->toArray();
        $data['game_id'] = $game->id;

        try {
            $gameUpdate = GameUpdate::create($data);

            // Return back to the same page (live game page)
            return back()->with('success', 'Game update created successfully.');
        } catch (\Exception $e) {
            \Log::error('GameUpdate creation failed', [
                'game_id' => $game->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            return back()->withErrors(['message' => 'Failed to create game update.']);
        }
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
