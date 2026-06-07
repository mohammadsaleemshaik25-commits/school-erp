@extends('layouts.app')

@section('title', 'User Management')

@section('content')
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">User Management</h2>
            <p class="text-sm text-slate-600">Create users, assign roles, and activate/deactivate access.</p>
        </div>

        @if(session('status'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded text-sm">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white border rounded-lg shadow-sm p-4">
            <h2 class="text-lg font-semibold mb-3">Create User</h2>
            <form action="{{ route('users.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <input class="border rounded px-3 py-2 text-sm" name="full_name" placeholder="Full Name" required>
                <input class="border rounded px-3 py-2 text-sm" name="username" placeholder="Username" required>
                <input class="border rounded px-3 py-2 text-sm" type="email" name="email" placeholder="Email (optional)">
                <input class="border rounded px-3 py-2 text-sm" type="password" name="password" placeholder="Password" required>
                <select class="border rounded px-3 py-2 text-sm" name="role_id" required>
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                    @endforeach
                </select>
                <label class="text-sm inline-flex items-center gap-2">
                    <input type="checkbox" name="is_active" value="1" checked> Active
                </label>
                <div class="md:col-span-3">
                    <button class="bg-indigo-700 text-white px-4 py-2 rounded text-sm">Create User</button>
                </div>
            </form>
        </div>

        <div class="bg-white border rounded-lg shadow-sm p-4">
            <h2 class="text-lg font-semibold mb-3">Existing Users</h2>
            <div class="overflow-x-auto rounded border">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-2 border text-left">Name</th>
                            <th class="p-2 border text-left">Username</th>
                            <th class="p-2 border text-left">Role</th>
                            <th class="p-2 border text-left">Status</th>
                            <th class="p-2 border text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="p-2 border">{{ $user->full_name }}</td>
                                <td class="p-2 border">{{ $user->username }}</td>
                                <td class="p-2 border">{{ optional($user->role)->role_name ?? '-' }}</td>
                                <td class="p-2 border">{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
                                <td class="p-2 border">
                                    <form action="{{ route('users.update', $user) }}" method="POST" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        @method('PUT')
                                        <select name="role_id" class="border rounded px-2 py-1 text-xs">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->role_id }}" @selected($user->role_id === $role->role_id)>{{ $role->role_name }}</option>
                                            @endforeach
                                        </select>
                                        <label class="text-xs inline-flex items-center gap-1">
                                            <input type="checkbox" name="is_active" value="1" @checked($user->is_active)> Active
                                        </label>
                                        <input type="hidden" name="full_name" value="{{ $user->full_name }}">
                                        <input type="hidden" name="username" value="{{ $user->username }}">
                                        <input type="hidden" name="email" value="{{ $user->email }}">
                                        <button class="bg-gray-800 text-white px-2 py-1 rounded text-xs">Update</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-3 text-center text-gray-500">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
