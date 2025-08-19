<?php

namespace Database\Seeders;

use App\Enums\GameResult;
use App\Enums\GameType;
use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class GameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = Team::all();
        $users = User::all();
        Game::factory(5)->create(
            [
                'is_solo' => false,
                'is_ended' => true,
                'type' => GameType::TEN_CUP
            ]
        )->each(function (Game $game) use ($teams) {
            // Get 2 different random teams that does not share players

            $randomTeams = $teams->random(2);

            $game->teams()->attach(
                $randomTeams->last()->id,
                [
                    'result' => GameResult::WIN,
                    'cups_left' => 0,
                ]
            );

            $game->teams()->attach(
                $randomTeams->first()->id,
                [
                    'result' => GameResult::LOSS,
                    'cups_left' => rand(1, 10),
                ]
            );
        });

        Game::factory(5)->create(
            [
                'is_solo' => true,
                'is_ended' => true,
                'type' => GameType::TEN_CUP
            ]
        )->each(function (Game $game) use ($users) {
            // Get 2 different random users
            $randomUsers = $users->random(2);

            $game->users()->attach(
                $randomUsers->first()->id,
                [
                    'result' => GameResult::WIN,
                    'cups_left' => 0,
                ]
            );
            $game->users()->attach(
                $randomUsers->last()->id,
                [
                    'result' => GameResult::LOSS,
                    'cups_left' => rand(0, 10),
                ]
            );
        });
    }
}
