<?php

namespace Tests\Unit;

use App\DataObjects\GameThrowStatisticsDataObject;
use App\DataObjects\MatchHistoryGameDataObject;
use App\DataObjects\TeamGameDataObject;
use App\DataObjects\UserGameDataObject;
use App\Enums\GameResult;
use App\Enums\GameType;
use App\Enums\GameUpdateType;
use App\Models\Game;
use App\Models\GameUpdate;
use App\Models\Team;
use App\Models\User;
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class GameServiceTest extends TestCase
{
    use RefreshDatabase;

    private GameService $gameService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameService = new GameService();
    }

    public function test_get_games_paginator_returns_paginator_with_user_games()
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create games where user participates directly
        $soloGame = Game::factory()->create(['is_solo' => true]);
        $soloGame->users()->attach($user, ['result' => GameResult::WIN, 'cups_left' => 0]);
        $soloGame->users()->attach($otherUser, ['result' => GameResult::LOSS, 'cups_left' => 3]);

        // Create a game where user doesn't participate
        $nonUserGame = Game::factory()->create(['is_solo' => true]);
        $nonUserGame->users()->attach($otherUser, ['result' => GameResult::WIN, 'cups_left' => 0]);

        // Act
        $result = $this->gameService->getGamesPaginator($user->id, 10, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals($soloGame->id, $result->items()[0]->id);
    }

    public function test_get_games_paginator_includes_team_games()
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        $team1->users()->attach($user);
        $team2->users()->attach($otherUser);

        $teamGame = Game::factory()->create(['is_solo' => false]);
        $teamGame->teams()->attach($team1, ['result' => GameResult::WIN, 'cups_left' => 0]);
        $teamGame->teams()->attach($team2, ['result' => GameResult::LOSS, 'cups_left' => 2]);

        // Act
        $result = $this->gameService->getGamesPaginator($user->id, 10, 1);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals($teamGame->id, $result->items()[0]->id);
    }

    public function test_get_throw_statistics_calculates_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();

        // Create game updates for throws
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT
        ]);
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT
        ]);
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::EDGE
        ]);
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::MISS
        ]);
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::MISS
        ]);

        // Create non-throw update (should be ignored)
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::START
        ]);


        $game->load('gameUpdates');

        // Act
        $result = $this->gameService->getThrowStatistics($game, $user);

        // Assert
        $this->assertInstanceOf(GameThrowStatisticsDataObject::class, $result);
        $this->assertEquals(5, $result->total); // Only throw results
        $this->assertEquals(2, $result->hits);
        $this->assertEquals(1, $result->edgeHits);
        $this->assertEquals(2, $result->misses);
        $this->assertEquals(40.0, $result->hitRate); // 2/5 * 100
        $this->assertEquals(20.0, $result->edgeHitRate); // 1/5 * 100
        $this->assertEquals(40.0, $result->missRate); // 2/5 * 100
    }

    public function test_get_throw_statistics_handles_no_throws()
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create();
        $game->load('gameUpdates');

        // Act
        $result = $this->gameService->getThrowStatistics($game, $user);

        // Assert
        $this->assertEquals(0, $result->total);
        $this->assertEquals(0, $result->hits);
        $this->assertEquals(0, $result->edgeHits);
        $this->assertEquals(0, $result->misses);
        $this->assertEquals(0, $result->hitRate);
        $this->assertEquals(0, $result->edgeHitRate);
        $this->assertEquals(0, $result->missRate);
    }

    public function test_convert_games_paginator_to_match_history_for_solo_game()
    {
        // Arrange
        $user = User::factory()->create();
        $opponent = User::factory()->create();

        $game = Game::factory()->create(['is_solo' => true, 'is_ended' => true]);
        $game->users()->attach($user, ['result' => GameResult::WIN, 'cups_left' => 0]);
        $game->users()->attach($opponent, ['result' => GameResult::LOSS, 'cups_left' => 3]);

        // Create some game updates
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT
        ]);

        $paginator = new LengthAwarePaginator(
            collect([$game]),
            1,
            10,
            1
        );

        // Act
        $result = $this->gameService->convertGamesPaginatorToMatchHistoryGameDataObjects($paginator, $user);

        // Assert
        $this->assertCount(1, $result);
        $matchHistory = $result->first();
        $this->assertInstanceOf(MatchHistoryGameDataObject::class, $matchHistory);
        $this->assertEquals($game->id, $matchHistory->id);
        $this->assertTrue($matchHistory->isSolo);
        $this->assertNotNull($matchHistory->primaryUser);
        $this->assertNotNull($matchHistory->secondaryUser);
        $this->assertNull($matchHistory->primaryTeam);
        $this->assertNull($matchHistory->secondaryTeam);
        $this->assertEquals($user->id, $matchHistory->primaryUser->id);
        $this->assertEquals($opponent->id, $matchHistory->secondaryUser->id);
    }

    public function test_convert_games_paginator_to_match_history_for_team_game()
    {
        // Arrange
        $user = User::factory()->create();
        $teammate = User::factory()->create();
        $opponent1 = User::factory()->create();
        $opponent2 = User::factory()->create();

        $userTeam = Team::factory()->create();
        $opponentTeam = Team::factory()->create();

        $userTeam->users()->attach([$user, $teammate]);
        $opponentTeam->users()->attach([$opponent1, $opponent2]);

        $game = Game::factory()->create(['is_solo' => false, 'is_ended' => true]);
        $game->teams()->attach($userTeam, ['result' => GameResult::WIN, 'cups_left' => 0]);
        $game->teams()->attach($opponentTeam, ['result' => GameResult::LOSS, 'cups_left' => 2]);

        $game->load(['teams.users']);

        $paginator = new LengthAwarePaginator(
            collect([$game]),
            1,
            10,
            1
        );

        // Act
        $result = $this->gameService->convertGamesPaginatorToMatchHistoryGameDataObjects($paginator, $user);

        // Assert
        $this->assertCount(1, $result);
        $matchHistory = $result->first();
        $this->assertInstanceOf(MatchHistoryGameDataObject::class, $matchHistory);
        $this->assertEquals($game->id, $matchHistory->id);
        $this->assertFalse($matchHistory->isSolo);
        $this->assertNull($matchHistory->primaryUser);
        $this->assertNull($matchHistory->secondaryUser);
        $this->assertNotNull($matchHistory->primaryTeam);
        $this->assertNotNull($matchHistory->secondaryTeam);
        $this->assertEquals($userTeam->id, $matchHistory->primaryTeam->id);
        $this->assertEquals($opponentTeam->id, $matchHistory->secondaryTeam->id);
        $this->assertEquals(0, $matchHistory->primaryTeam->cupsLeft);
        $this->assertEquals(2, $matchHistory->secondaryTeam->cupsLeft);
        $this->assertCount(2, $matchHistory->primaryTeam->users);
        $this->assertCount(2, $matchHistory->secondaryTeam->users);
    }

    public function test_convert_games_paginator_to_match_history_for_team_game_when_user_team_loses()
    {
        // Arrange
        $user = User::factory()->create();
        $teammate = User::factory()->create();
        $opponent1 = User::factory()->create();
        $opponent2 = User::factory()->create();

        $userTeam = Team::factory()->create();
        $opponentTeam = Team::factory()->create();

        $userTeam->users()->attach([$user, $teammate]);
        $opponentTeam->users()->attach([$opponent1, $opponent2]);

        $game = Game::factory()->create(['is_solo' => false, 'is_ended' => true]);
        $game->teams()->attach($userTeam, ['result' => GameResult::LOSS, 'cups_left' => 2]);
        $game->teams()->attach($opponentTeam, ['result' => GameResult::WIN, 'cups_left' => 0]);

        $game->load(['teams.users']);

        $paginator = new LengthAwarePaginator(
            collect([$game]),
            1,
            10,
            1
        );

        // Act
        $result = $this->gameService->convertGamesPaginatorToMatchHistoryGameDataObjects($paginator, $user);

        // Assert
        $this->assertCount(1, $result);
        $matchHistory = $result->first();
        $this->assertInstanceOf(MatchHistoryGameDataObject::class, $matchHistory);
        $this->assertEquals($game->id, $matchHistory->id);
        $this->assertFalse($matchHistory->isSolo);
        $this->assertNull($matchHistory->primaryUser);
        $this->assertNull($matchHistory->secondaryUser);
        $this->assertNotNull($matchHistory->primaryTeam);
        $this->assertNotNull($matchHistory->secondaryTeam);
        $this->assertEquals($userTeam->id, $matchHistory->primaryTeam->id);
        $this->assertEquals($opponentTeam->id, $matchHistory->secondaryTeam->id);
        $this->assertCount(2, $matchHistory->primaryTeam->users);
        $this->assertCount(2, $matchHistory->secondaryTeam->users);
    }

    public function test_convert_games_paginator_handles_solo_game_without_opponent()
    {
        // Arrange
        $user = User::factory()->create();

        $game = Game::factory()->create(['is_solo' => true]);
        $game->users()->attach($user, ['result' => GameResult::WIN, 'cups_left' => 0]);

        $paginator = new LengthAwarePaginator(
            collect([$game]),
            1,
            10,
            1
        );

        // Act
        $result = $this->gameService->convertGamesPaginatorToMatchHistoryGameDataObjects($paginator, $user);

        // Assert
        $this->assertCount(1, $result);
        $matchHistory = $result->first();
        $this->assertNotNull($matchHistory->primaryUser);
        $this->assertNull($matchHistory->secondaryUser);
    }

    public function test_convert_games_paginator_includes_throw_statistics()
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create(['is_solo' => true]);
        $game->users()->attach($user, ['result' => GameResult::WIN, 'cups_left' => 0]);

        // Create game updates
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT
        ]);
        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::MISS
        ]);

        $game->load('gameUpdates');

        $paginator = new LengthAwarePaginator(
            collect([$game]),
            1,
            10,
            1
        );

        // Act
        $result = $this->gameService->convertGamesPaginatorToMatchHistoryGameDataObjects($paginator, $user);

        // Assert
        $matchHistory = $result->first();
        $this->assertEquals(2, $matchHistory->totalThrows);
        $this->assertEquals(1, $matchHistory->hits);
        $this->assertEquals(1, $matchHistory->misses);
        $this->assertEquals(50.0, $matchHistory->hitRate);
        $this->assertEquals(50.0, $matchHistory->missRate);
    }

    public function test_get_games_paginator_respects_pagination_parameters()
    {
        // Arrange
        $user = User::factory()->create();

        // Create multiple games
        for ($i = 0; $i < 15; $i++) {
            $game = Game::factory()->create(['is_solo' => true]);
            $game->users()->attach($user, ['result' => GameResult::WIN, 'cups_left' => 0]);
        }

        // Act
        $result = $this->gameService->getGamesPaginator($user->id, 10, 1);

        // Assert
        $this->assertEquals(15, $result->total());
        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(1, $result->currentPage());
        $this->assertCount(10, $result->items());
    }

    public function test_get_games_paginator_loads_required_relationships()
    {
        // Arrange
        $user = User::factory()->create();
        $game = Game::factory()->create(['is_solo' => true]);
        $game->users()->attach($user, ['result' => GameResult::WIN, 'cups_left' => 0]);

        GameUpdate::factory()->create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'type' => GameUpdateType::HIT
        ]);

        // Act
        $result = $this->gameService->getGamesPaginator($user->id, 10, 1);

        // Assert
        $gameFromPaginator = $result->items()[0];
        $this->assertTrue($gameFromPaginator->relationLoaded('users'));
        $this->assertTrue($gameFromPaginator->relationLoaded('teams'));
        $this->assertTrue($gameFromPaginator->relationLoaded('gameUpdates'));
    }
}
