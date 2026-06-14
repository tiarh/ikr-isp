<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

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
        ]);
    }
}
