<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $users = User::with('roles')
            ->orderBy('id')
            ->get(['id', 'name', 'email', 'phone', 'email_verified_at', 'created_at'])
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'phone' => $u->phone,
                'email_verified_at' => $u->email_verified_at?->toDateTimeString(),
                'roles' => $u->getRoleNames()->toArray(),
                'created_at' => $u->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'roles' => Role::pluck('name')->toArray(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/UserForm', [
            'roles' => Role::pluck('name')->toArray(),
            'user' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'nullable|string|max:32',
            'password' => ['required', 'confirmed', Password::min(8)],
            'roles'    => 'array',
            'roles.*'  => 'string|exists:roles,name',
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'phone'             => $data['phone'] ?? null,
            'password'          => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return redirect()
            ->route('admin.users')
            ->with('success', "User {$user->email} created");
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/UserForm', [
            'roles' => Role::pluck('name')->toArray(),
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'email'     => $user->email,
                'phone'     => $user->phone,
                'roles'     => $user->getRoleNames()->toArray(),
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => "required|email|unique:users,email,{$user->id}",
            'phone'    => 'nullable|string|max:32',
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'roles'    => 'array',
            'roles.*'  => 'string|exists:roles,name',
        ]);

        $user->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return redirect()
            ->route('admin.users')
            ->with('success', "User {$user->email} updated");
    }

    public function destroy(User $user): RedirectResponse
    {
        // Protect admin user from deletion
        if ($user->hasRole('admin') && User::role('admin')->count() === 1) {
            return back()->with('error', 'Cannot delete the only admin user');
        }

        $email = $user->email;
        $user->delete();

        return redirect()
            ->route('admin.users')
            ->with('success', "User {$email} deleted");
    }
}
