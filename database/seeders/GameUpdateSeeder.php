<?php

namespace Database\Seeders;

use App\Enums\GameUpdateType;
use App\Models\Game;
use App\Models\GameUpdate;
use Illuminate\Database\Seeder;

class GameUpdateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $games = Game::with(['users', 'teams.users'])->get();

        foreach ($games as $game) {
            // Build side assignments: map user_id => side ('A' or 'B') and an ordered throwers array
            [$userSide, $throwers] = $this->buildSides($game);
            if (empty($throwers)) {
                // Nothing to seed if we can't determine throwers
                continue;
            }

            // Initial state: both sides have cups 1..10
            $sidePositions = [
                'A' => range(1, 10),
                'B' => range(1, 10),
            ];

            // START update (neutral)
            GameUpdate::create([
                'game_id' => $game->id,
                'user_id' => null,
                'type' => GameUpdateType::START,
                'self_cup_positions' => $sidePositions['A'],
                'opponent_cup_positions' => $sidePositions['B'],
                'self_cups_left' => 10,
                'opponent_cups_left' => 10,
                'affected_cup' => null,
            ]);

            $i = 0;
            $throwResults = GameUpdateType::throwResults(); // [MISS, EDGE, HIT]

            while (count($sidePositions['A']) > 0 && count($sidePositions['B']) > 0) {
                $userId = $throwers[$i % count($throwers)];
                $selfSide = $userSide[$userId] ?? 'A';
                $oppSide = $selfSide === 'A' ? 'B' : 'A';

                // choose a result; keep some randomness but honor game ending after 10 hits
                $type = $throwResults[array_rand($throwResults)];

                $affectedCup = null;

                if ($type === GameUpdateType::EDGE || $type === GameUpdateType::HIT) {
                    $cupIndex = array_rand($sidePositions[$oppSide]);
                    $affectedCup = $sidePositions[$oppSide][$cupIndex];
                }

                GameUpdate::create([
                    'game_id' => $game->id,
                    'user_id' => $userId,
                    'type' => $type,
                    'self_cup_positions' => array_values($sidePositions[$selfSide]),
                    'opponent_cup_positions' => array_values($sidePositions[$oppSide]),
                    'self_cups_left' => count($sidePositions[$selfSide]),
                    'opponent_cups_left' => count($sidePositions[$oppSide]),
                    'affected_cup' => $affectedCup,
                ]);

                if ($type === GameUpdateType::HIT && !empty($sidePositions[$oppSide])) {
                    // remove a random opponent cup
                    unset($sidePositions[$oppSide][$cupIndex]);
                    // reindex to keep arrays compact
                    $sidePositions[$oppSide] = array_values($sidePositions[$oppSide]);
                }

                // If a side reached 0 cups due to hits on only one side, we still end when total hits == 10 per requirements
                $i++;
            }

            // END update from a consistent perspective (side A)
            GameUpdate::create([
                'game_id' => $game->id,
                'user_id' => null,
                'type' => GameUpdateType::END,
                'self_cup_positions' => array_values($sidePositions['A']),
                'opponent_cup_positions' => array_values($sidePositions['B']),
                'self_cups_left' => count($sidePositions['A']),
                'opponent_cups_left' => count($sidePositions['B']),
                'affected_cup' => null,
            ]);
        }
    }

    /**
     * Build user-to-side mapping and an ordered list of throwers for the game.
     *
     * @return array{array<int,string>, array<int,int>} [userSideMap, throwers]
     */
    private function buildSides(Game $game): array
    {
        $userSide = [];
        $throwers = [];

        if ($game->is_solo) {
            $userIds = $game->users->pluck('id')->values()->all();
            if (count($userIds) === 0) {
                return [$userSide, $throwers];
            }
            // Assign alternating users to sides A/B
            foreach ($userIds as $idx => $uid) {
                $side = ($idx % 2 === 0) ? 'A' : 'B';
                $userSide[$uid] = $side;
                $throwers[] = $uid;
            }
        } else {
            $teams = $game->teams; // already eager loaded with users
            if ($teams->count() === 0) {
                // fall back to users if teams missing
                $userIds = $game->users->pluck('id')->values()->all();
                foreach ($userIds as $idx => $uid) {
                    $side = ($idx % 2 === 0) ? 'A' : 'B';
                    $userSide[$uid] = $side;
                    $throwers[] = $uid;
                }
            } else {
                // First team => A, second => B, others alternate
                foreach ($teams as $tIdx => $team) {
                    $side = ($tIdx % 2 === 0) ? 'A' : 'B';
                    foreach ($team->users as $user) {
                        $userSide[$user->id] = $side;
                        $throwers[] = $user->id;
                    }
                }
            }
        }

        // Ensure throwers are unique and preserve order
        $throwers = array_values(array_unique($throwers));

        return [$userSide, $throwers];
    }
}
