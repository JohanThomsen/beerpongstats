<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory(10)->create();

        Team::factory(5)->create()->each(function (Team $team) use ($users) {
            $team->users()->attach(
                $users->random(2)->pluck('id')->toArray()
            );
        });
    }
}
