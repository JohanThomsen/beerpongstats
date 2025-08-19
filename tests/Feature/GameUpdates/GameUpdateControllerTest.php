<?php

namespace Tests\Feature\GameUpdates;

use App\Enums\GameUpdateType;
use App\Events\GameUpdateEvent;
use App\Models\Game;
use App\Models\GameUpdate;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GameUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_game_update_for_user_in_game(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $game = Game::factory()->create();

        // Add user to the game
        $game->users()->attach($user->id, ['result' => null, 'cups_left' => 10]);

        $payload = [
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT->value,
            'self_cups_left' => 5,
            'opponent_cups_left' => 6,
            'self_cup_positions' => [1, 2, 3],
            'opponent_cup_positions' => [4, 5],
            'affected_cup' => 7,
        ];

        $response = $this->actingAs($user)->postJson(route('games.updates.store', ['game' => $game->id]), $payload);

        $response->assertCreated();
        $response->assertJsonFragment([
            'game_id' => $game->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('game_updates', [
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT->value,
            'self_cups_left' => 5,
            'opponent_cups_left' => 6,
            'affected_cup' => 7,
        ]);
    }

    public function test_store_rejects_user_not_in_game(): void
    {
        $user = User::factory()->create();
        $nonParticipant = User::factory()->create();
        $game = Game::factory()->create();

        // Only add the first user to the game
        $game->users()->attach($user->id, ['result' => null, 'cups_left' => 10]);

        $payload = [
            'user_id' => $nonParticipant->id,
            'type' => GameUpdateType::HIT->value,
            'self_cups_left' => 5,
            'opponent_cups_left' => 6,
        ];

        $response = $this->actingAs($user)->postJson(route('games.updates.store', ['game' => $game->id]), $payload);

        $response->assertStatus(403)
            ->assertJson(['message' => 'User is not a participant in this game.']);
    }

    public function test_delete_latest_by_game_deletes_most_recent_update(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $game = Game::factory()->create();

        $older = GameUpdate::factory()->for($game)->for($user)->create(['type' => GameUpdateType::MISS]);
        $latest = GameUpdate::factory()->for($game)->for($user)->create(['type' => GameUpdateType::HIT]);

        $this->actingAs($user)->deleteJson(route('games.updates.delete-latest', ['game' => $game->id]))
            ->assertOk()
            ->assertJson(['message' => 'Latest game update deleted.']);

        $this->assertDatabaseMissing('game_updates', ['id' => $latest->id]);
        $this->assertDatabaseHas('game_updates', ['id' => $older->id]);
    }

    public function test_delete_latest_by_game_returns_404_when_none(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('games.updates.delete-latest', ['game' => $game->id]))
            ->assertStatus(404);
    }

    public function test_store_broadcasts_game_update_event_when_created(): void
    {
        Bus::fake(); // Prevent actual broadcasting but allow event dispatch

        $user = User::factory()->create();
        $game = Game::factory()->create();

        // Add user to the game
        $game->users()->attach($user->id, ['result' => null, 'cups_left' => 10]);

        $payload = [
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT->value,
            'self_cups_left' => 5,
            'opponent_cups_left' => 6,
            'self_cup_positions' => [1, 2, 3],
            'opponent_cup_positions' => [4, 5],
            'affected_cup' => 7,
        ];

        $this->actingAs($user)->postJson(route('games.updates.store', ['game' => $game->id]), $payload);

        // Verify that a broadcast job was queued
        Bus::assertDispatchedTimes(BroadcastEvent::class, 1);
    }

    public function test_delete_latest_broadcasts_game_update_event_when_deleted(): void
    {
        Bus::fake(); // Prevent actual broadcasting but allow event dispatch

        $user = User::factory()->create();
        $game = Game::factory()->create();

        $gameUpdate = GameUpdate::factory()->for($game)->for($user)->create(['type' => GameUpdateType::HIT]);

        $this->actingAs($user)->deleteJson(route('games.updates.delete-latest', ['game' => $game->id]));

        // Verify that a broadcast job was queued
        Bus::assertDispatchedTimes(BroadcastEvent::class, 2); // One for the delete event, one for the create event
    }

    public function test_observer_dispatches_event_on_created(): void
    {
        Event::fake([GameUpdateEvent::class]);
        Bus::fake();

        $user = User::factory()->create();
        $game = Game::factory()->create();

        // Directly create a GameUpdate to trigger the observer
        $gameUpdate = GameUpdate::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT,
            'self_cups_left' => 5,
            'opponent_cups_left' => 6,
            'self_cup_positions' => [1, 2, 3],
            'opponent_cup_positions' => [4, 5],
            'affected_cup' => 7,
        ]);

        Event::assertDispatched(GameUpdateEvent::class, function ($event) use ($gameUpdate) {
            return $event->gameUpdate->id === $gameUpdate->id &&
                   $event->broadcastWith()['created'] === true;
        });
    }

    public function test_observer_dispatches_event_on_deleted(): void
    {
        Event::fake([GameUpdateEvent::class]);
        Bus::fake();

        $user = User::factory()->create();
        $game = Game::factory()->create();
        $gameUpdate = GameUpdate::factory()->for($game)->for($user)->create(['type' => GameUpdateType::HIT]);

        // Delete the GameUpdate to trigger the observer
        $gameUpdate->delete();

        Event::assertDispatched(GameUpdateEvent::class, function ($event) use ($gameUpdate) {
            return $event->gameUpdate->id === $gameUpdate->id &&
                   $event->broadcastWith()['created'] === false;
        });
    }
}
