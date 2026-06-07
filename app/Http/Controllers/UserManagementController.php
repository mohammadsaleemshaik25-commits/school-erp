<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        $users = User::with('role')->orderBy('full_name')->get();
        $roles = Role::orderBy('role_name')->get();

        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,role_id',
            'is_active' => 'nullable|boolean',
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'password_hash' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->log($request, 'USER_CREATED', 'users', (string) $user->getKey());

        return back()->with('status', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,'.$user->getKey().',user_id',
            'email' => 'nullable|email|max:255|unique:users,email,'.$user->getKey().',user_id',
            'password' => 'nullable|string|min:6',
            'role_id' => 'required|exists:roles,role_id',
            'is_active' => 'nullable|boolean',
        ]);

        $user->fill([
            'full_name' => $validated['full_name'],
            'username' => $validated['username'],
            'email' => $validated['email'] ?? null,
            'role_id' => $validated['role_id'],
            'is_active' => $request->boolean('is_active', false),
        ]);

        if (! empty($validated['password'])) {
            $user->password_hash = Hash::make($validated['password']);
        }

        $user->save();

        $this->log($request, 'USER_UPDATED', 'users', (string) $user->getKey());

        return back()->with('status', 'User updated successfully.');
    }

    private function log(Request $request, string $action, string $table, string $recordId): void
    {
        AuditLog::create([
            'user_id' => (int) $request->user()->getKey(),
            'action' => $action,
            'table_name' => $table,
            'record_id' => $recordId,
            'ip_address' => (string) $request->ip(),
        ]);
    }
}
