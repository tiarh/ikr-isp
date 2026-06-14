<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\PsbOrder;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        $stats = [
            'users_total' => User::count(),
            'psb_total' => PsbOrder::count(),
            'psb_draft' => PsbOrder::where('status', 'draft')->count(),
            'psb_provisioning' => PsbOrder::where('status', 'provisioning')->count(),
            'psb_done' => PsbOrder::where('status', 'done')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
        ]);
    }
}
