<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Agent;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('agent')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'total_users' => User::count(),
            'super_admins' => User::where('role', UserRole::SuperAdmin)->count(),
            'admins' => User::where('role', UserRole::Admin)->count(),
            'agents' => User::where('role', UserRole::Agent)->count(),
        ];

        return Inertia::render('superadmin/users/index', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return Inertia::render('superadmin/users/create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => 'required|in:SuperAdmin,Admin,Agent',
            'extension' => 'required_if:role,Agent|string|unique:agents,extension',
        ]);

        DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
            ]);

            if ($request->role === 'Agent') {
                $agent = Agent::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'extension' => $request->extension,
                ]);

                $user->update(['agent_id' => $agent->id]);
            }
        });

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load('agent');
        
        return Inertia::render('superadmin/users/edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email,' . $user->id,
            'role' => 'required|in:SuperAdmin,Admin,Agent',
            'extension' => 'required_if:role,Agent|string|unique:agents,extension,' . ($user->agent_id ?? 'NULL') . ',id',
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($request, $user) {
            $userData = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
            ];

            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // Handle agent role changes
            if ($request->role === 'Agent') {
                if ($user->agent) {
                    $user->agent->update([
                        'name' => $request->name,
                        'extension' => $request->extension,
                    ]);
                } else {
                    $agent = Agent::create([
                        'user_id' => $user->id,
                        'name' => $request->name,
                        'extension' => $request->extension,
                    ]);
                    $user->update(['agent_id' => $agent->id]);
                }
            } else {
                // If role changed from Agent to something else, remove agent record
                if ($user->agent) {
                    $user->agent->delete();
                    $user->update(['agent_id' => null]);
                }
            }
        });

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        DB::transaction(function () use ($user) {
            if ($user->agent) {
                $user->agent->delete();
            }
            $user->delete();
        });

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}