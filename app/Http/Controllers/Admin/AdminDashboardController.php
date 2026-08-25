<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class AdminDashboardController extends Controller
{
    /**
     * Muestra el panel principal del administrador.
     */
    public function index()
    {
        return view('admin.dashboard');
    }
}