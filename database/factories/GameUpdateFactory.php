<?php

namespace Database\Factories;

use App\Enums\GameUpdateType;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GameUpdate>
 */
class GameUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            // user_id is nullable in migration, but we default to a valid user for most factories
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(GameUpdateType::cases()),
            'self_cup_positions' => $this->faker->optional()->randomElements(range(1, 10), $this->faker->numberBetween(0, 6)),
            'opponent_cup_positions' => $this->faker->optional()->randomElements(range(1, 10), $this->faker->numberBetween(0, 6)),
            'self_cups_left' => $this->faker->numberBetween(0, 10),
            'opponent_cups_left' => $this->faker->numberBetween(0, 10),
            'affected_cup' => $this->faker->optional()->numberBetween(1, 10),
        ];
    }
}
