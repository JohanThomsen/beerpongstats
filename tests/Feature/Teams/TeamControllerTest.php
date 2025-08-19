<?php

namespace Tests\Feature\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected User $thirdUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->thirdUser = User::factory()->create();
    }

    // INDEX ENDPOINT TESTS
    public function test_index_displays_all_teams(): void
    {
        $team1 = Team::factory()->create();
        $team1->users()->attach([$this->user->id, $this->otherUser->id]);

        $team2 = Team::factory()->create();
        $team2->users()->attach([$this->otherUser->id, $this->thirdUser->id]);

        $response = $this->get(route('teams.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Teams/Index')
                ->has('teams', 2)
                ->where('teams.0.name', $team1->name)
                ->where('teams.1.name', $team2->name)
        );
    }

    public function test_index_includes_team_users(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->get(route('teams.index'));

        $response->assertInertia(fn($page) =>
            $page->has('teams.0.users', 2)
                ->where('teams.0.users.0.id', $this->user->id)
                ->where('teams.0.users.1.id', $this->otherUser->id)
        );
    }

    // CREATE ENDPOINT TESTS
    public function test_create_requires_authentication(): void
    {
        $response = $this->get(route('teams.create'));

        $response->assertStatus(403);
    }

    public function test_create_shows_form_when_authenticated(): void
    {
        $response = $this->actingAs($this->user)->get(route('teams.create'));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Teams/Create')
                ->has('users')
                ->has('currentUser')
                ->where('currentUser.id', $this->user->id)
                ->where('currentUser.name', $this->user->name)
                ->where('currentUser.email', $this->user->email)
        );
    }

    public function test_create_includes_all_users(): void
    {
        $response = $this->actingAs($this->user)->get(route('teams.create'));

        $response->assertInertia(fn($page) =>
            $page->has('users', 3) // user, otherUser, thirdUser
                ->has('currentUser')
                ->where('currentUser.id', $this->user->id)
        );
    }

    // STORE ENDPOINT TESTS
    public function test_store_creates_team_successfully(): void
    {
        $teamData = [
            'name' => 'Test Team',
            'user_ids' => [$this->user->id, $this->otherUser->id]
        ];

        $response = $this->actingAs($this->user)->post(route('teams.store'), $teamData);

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success', 'Team created successfully.');

        $this->assertDatabaseHas('teams', ['name' => 'Test Team']);

        $team = Team::where('name', 'Test Team')->first();
        $this->assertCount(2, $team->users);
        $this->assertTrue($team->users->contains($this->user));
        $this->assertTrue($team->users->contains($this->otherUser));
    }

    public function test_store_requires_authenticated_user_in_team(): void
    {
        $teamData = [
            'name' => 'Test Team',
            'user_ids' => [$this->otherUser->id, $this->thirdUser->id] // Auth user not included
        ];

        $response = $this->actingAs($this->user)->post(route('teams.store'), $teamData);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('teams', ['name' => 'Test Team']);
    }

    public function test_store_requires_unique_team_name(): void
    {
        Team::factory()->create(['name' => 'Existing Team']);

        $teamData = [
            'name' => 'Existing Team',
            'user_ids' => [$this->user->id, $this->otherUser->id]
        ];

        $response = $this->actingAs($this->user)->post(route('teams.store'), $teamData);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_exactly_two_users(): void
    {
        $teamData = [
            'name' => 'Test Team',
            'user_ids' => [$this->user->id] // Only one user
        ];

        $response = $this->actingAs($this->user)->post(route('teams.store'), $teamData);

        $response->assertSessionHasErrors('user_ids');

        $teamData['user_ids'] = [$this->user->id, $this->otherUser->id, $this->thirdUser->id]; // Three users

        $response = $this->actingAs($this->user)->post(route('teams.store'), $teamData);

        $response->assertSessionHasErrors('user_ids');
    }

    public function test_store_requires_existing_users(): void
    {
        $teamData = [
            'name' => 'Test Team',
            'user_ids' => [$this->user->id, 999] // Non-existent user
        ];

        $response = $this->actingAs($this->user)->post(route('teams.store'), $teamData);

        $response->assertSessionHasErrors('user_ids.1');
    }

    public function test_store_requires_distinct_users(): void
    {
        $teamData = [
            'name' => 'Test Team',
            'user_ids' => [$this->user->id, $this->user->id] // Duplicate user
        ];

        $response = $this->actingAs($this->user)->post(route('teams.store'), $teamData);

        $response->assertSessionHasErrors('user_ids.1');
    }

    // SHOW ENDPOINT TESTS
    public function test_show_displays_team_details(): void
    {
        $team = Team::factory()->create(['name' => 'Test Team']);
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->get(route('teams.show', $team));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Teams/Show')
                ->where('team.id', $team->id)
                ->where('team.name', 'Test Team')
                ->has('team.users', 2)
        );
    }

    public function test_show_works_without_authentication(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->get(route('teams.show', $team));

        $response->assertStatus(200);
    }

    // EDIT ENDPOINT TESTS
    public function test_edit_requires_authentication(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->get(route('teams.edit', $team));

        $response->assertStatus(403);
    }

    public function test_edit_shows_form_when_authenticated(): void
    {
        $team = Team::factory()->create(['name' => 'Test Team']);
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get(route('teams.edit', $team));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Teams/Edit')
                ->where('team.name', 'Test Team')
                ->has('team.users', 2)
                ->has('users')
                ->has('currentUser')
                ->where('currentUser.id', $this->user->id)
                ->where('currentUser.name', $this->user->name)
                ->where('currentUser.email', $this->user->email)
        );
    }

    // UPDATE ENDPOINT TESTS
    public function test_update_modifies_team_successfully(): void
    {
        $team = Team::factory()->create(['name' => 'Original Name']);
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $updateData = [
            'name' => 'Updated Name',
            'user_ids' => [$this->user->id, $this->thirdUser->id]
        ];

        $response = $this->actingAs($this->user)->put(route('teams.update', $team), $updateData);

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success', 'Team updated successfully.');

        $team->refresh();
        $this->assertEquals('Updated Name', $team->name);
        $this->assertCount(2, $team->users);
        $this->assertTrue($team->users->contains($this->user));
        $this->assertTrue($team->users->contains($this->thirdUser));
        $this->assertFalse($team->users->contains($this->otherUser));
    }

    public function test_update_requires_user_to_be_team_member(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach([$this->otherUser->id, $this->thirdUser->id]); // Auth user not a member

        $updateData = [
            'name' => 'Updated Name',
            'user_ids' => [$this->otherUser->id, $this->thirdUser->id]
        ];

        $response = $this->actingAs($this->user)->put(route('teams.update', $team), $updateData);

        $response->assertStatus(403);
    }

    public function test_update_allows_team_name_to_stay_same(): void
    {
        $team = Team::factory()->create(['name' => 'Same Name']);
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $updateData = [
            'name' => 'Same Name', // Same name should be allowed
            'user_ids' => [$this->user->id, $this->otherUser->id]
        ];

        $response = $this->actingAs($this->user)->put(route('teams.update', $team), $updateData);

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success', 'Team updated successfully.');
    }

    // DESTROY ENDPOINT TESTS
    public function test_destroy_deletes_team_successfully(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->actingAs($this->user)->delete(route('teams.destroy', $team));

        $response->assertRedirect(route('teams.index'));
        $response->assertSessionHas('success', 'Team deleted successfully.');

        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
        $this->assertDatabaseMissing('team_user', ['team_id' => $team->id]);
    }

    public function test_destroy_requires_user_to_be_team_member(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach([$this->otherUser->id, $this->thirdUser->id]); // Auth user not a member

        $response = $this->actingAs($this->user)->delete(route('teams.destroy', $team));

        $response->assertStatus(403);
        $this->assertDatabaseHas('teams', ['id' => $team->id]);
    }

    // USER TEAMS ENDPOINT TESTS
    public function test_user_teams_displays_user_teams(): void
    {
        $team1 = Team::factory()->create(['name' => 'User Team 1']);
        $team1->users()->attach([$this->user->id, $this->otherUser->id]);

        $team2 = Team::factory()->create(['name' => 'User Team 2']);
        $team2->users()->attach([$this->user->id, $this->thirdUser->id]);

        $team3 = Team::factory()->create(['name' => 'Other Team']);
        $team3->users()->attach([$this->otherUser->id, $this->thirdUser->id]); // User not in this team

        $response = $this->get(route('user.teams', $this->user));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Teams/UserTeams')
                ->where('user.id', $this->user->id)
                ->has('teams', 2)
                ->where('teams.0.name', 'User Team 1')
                ->where('teams.1.name', 'User Team 2')
        );
    }

    public function test_user_teams_shows_empty_list_for_user_with_no_teams(): void
    {
        $response = $this->get(route('user.teams', $this->user));

        $response->assertStatus(200);
        $response->assertInertia(fn($page) =>
            $page->component('Teams/UserTeams')
                ->where('user.id', $this->user->id)
                ->has('teams', 0)
        );
    }

    public function test_user_teams_works_without_authentication(): void
    {
        $response = $this->get(route('user.teams', $this->user));

        $response->assertStatus(200);
    }

    public function test_store_requires_authentication(): void
    {
        $teamData = [
            'name' => 'Test Team',
            'user_ids' => [$this->user->id, $this->otherUser->id]
        ];

        $response = $this->post(route('teams.store'), $teamData);

        $response->assertForbidden();
    }

    public function test_update_requires_authentication(): void
    {
        $team = Team::factory()->create();

        $updateData = [
            'name' => 'Updated Name',
            'user_ids' => [$this->user->id, $this->otherUser->id]
        ];

        $response = $this->put(route('teams.update', $team), $updateData);

        $response->assertForbidden();
    }

    public function test_destroy_requires_authentication(): void
    {
        $team = Team::factory()->create();

        $response = $this->delete(route('teams.destroy', $team));

        $response->assertForbidden();
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('teams.store'), []);

        $response->assertSessionHasErrors(['name', 'user_ids']);
    }

    public function test_update_validates_required_fields(): void
    {
        $team = Team::factory()->create();
        $team->users()->attach([$this->user->id, $this->otherUser->id]);

        $response = $this->actingAs($this->user)->put(route('teams.update', $team), []);

        $response->assertSessionHasErrors(['name', 'user_ids']);
    }
}
