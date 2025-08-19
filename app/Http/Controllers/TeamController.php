<?php

namespace App\Http\Controllers;

use App\DataObjects\Requests\CreateTeamRequestDataObject;
use App\DataObjects\Requests\UpdateTeamRequestDataObject;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class TeamController extends Controller
{
    /**
     * Display a listing of teams
     */
    public function index()
    {
        $teams = Team::with('users')->get();

        return Inertia::render('Teams/Index', [
            'teams' => $teams
        ]);
    }

    /**
     * Show the form for creating a new team
     */
    public function create()
    {
        if (!Auth::check()) {
            abort(403, 'You must be logged in to create a team.');
        }

        $users = User::all();
        $currentUser = User::find(Auth::id());

        return Inertia::render('Teams/Create', [
            'users' => $users,
            'currentUser' => $currentUser,
        ]);
    }

    /**
     * Store a newly created team
     */
    public function store(CreateTeamRequestDataObject $request)
    {
        // Check if the authenticated user is included in the team being created
        if (!in_array(Auth::id(), $request->user_ids)) {
            abort(403, 'You can only create teams that you are a member of.');
        }

        $team = Team::create([
            'name' => $request->name
        ]);

        $team->users()->attach($request->user_ids);

        return Redirect::route('teams.index')->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified team
     */
    public function show(Team $team)
    {
        $team->load('users');

        return Inertia::render('Teams/Show', [
            'team' => $team
        ]);
    }

    /**
     * Show the form for editing the specified team
     */
    public function edit(Team $team)
    {
        if (!Auth::check()) {
            abort(403, 'You must be logged in to create a team.');
        }

        $team->load('users');
        $users = User::all();
        $currentUser = User::find(Auth::id());

        return Inertia::render('Teams/Edit', [
            'team' => $team,
            'users' => $users,
            'currentUser' => $currentUser,
        ]);
    }

    /**
     * Update the specified team
     */
    public function update(UpdateTeamRequestDataObject $request, Team $team)
    {
        // Check if the authenticated user is a member of the team
        if (!$team->users()->where('user_id', Auth::id())->exists()) {
            abort(403, 'You can only update teams that you are a member of.');
        }

        $team->update([
            'name' => $request->name
        ]);

        $team->users()->sync($request->user_ids);

        return Redirect::route('teams.index')->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified team
     */
    public function destroy(Team $team)
    {
        // Check if the authenticated user is a member of the team
        if (!$team->users()->where('user_id', Auth::id())->exists()) {
            abort(403, 'You can only delete teams that you are a member of.');
        }

        $team->users()->detach();
        $team->delete();

        return Redirect::route('teams.index')->with('success', 'Team deleted successfully.');
    }

    /**
     * Get all teams for a specific user
     */
    public function userTeams(User $user)
    {
        $teams = $user->teams()->with('users')->get();

        return Inertia::render('Teams/UserTeams', [
            'user' => $user,
            'teams' => $teams
        ]);
    }
}
