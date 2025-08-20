<?php

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $user1 = User::create([
            'name' => 'placeholder1',
            'email' => 'placeholder1@placeholder.com',
            'password' => bcrypt(random_int(12, 50)),
        ]);

        $user2 = User::create([
            'name' => 'placeholder2',
            'email' => 'placeholder2@placeholder.com',
            'password' => bcrypt(random_int(12, 50)),
        ]);

        //create a team with the two users
        $team = Team::create([
            'name' => 'placeholder team',
        ]);
        $team->users()->attach($user1->id);
        $team->users()->attach($user2->id);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_user');
    }
};
