<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoleDashboardController extends Controller
{
    public function superAdmin(): Response
    {
        return Inertia::render('superadmin/SuperAdminDashboard');
    }

    public function admin(): Response
    {
        return Inertia::render('admin/AdminDashboard');
    }
}
