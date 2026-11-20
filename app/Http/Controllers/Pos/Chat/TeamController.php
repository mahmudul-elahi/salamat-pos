<?php

namespace App\Http\Controllers\Pos\Chat;

use App\Http\Controllers\Controller;
use App\Models\Pos\Chat\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index()
    {
        return Team::query()
            ->withCount('users')
            ->with('users:id,name')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:8'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $team = Team::query()->create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        $team->users()->sync($data['user_ids'] ?? []);

        return successResponse('Team created successfully', $team->load('users:id,name'));
    }

    public function update(Request $request, Team $chat_team)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon' => ['nullable', 'string', 'max:8'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $chat_team->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        $chat_team->users()->sync($data['user_ids'] ?? []);

        return successResponse('Team updated successfully', $chat_team->load('users:id,name'));
    }

    public function destroy(Team $chat_team)
    {
        $chat_team->delete();

        return successResponse('Team deleted successfully');
    }
}
