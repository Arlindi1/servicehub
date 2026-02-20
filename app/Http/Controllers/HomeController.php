<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response|RedirectResponse
    {
        if (auth()->check()) {
            $homeRoute = $request->user()?->organization_id === null
                ? 'onboarding.organization.create'
                : $request->user()->homeRouteName();

            return redirect()->to(route($homeRoute, absolute: false));
        }

        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'canDemoLogin' => (bool) config('app.demo_login_enabled') && Route::has('demo.login'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    }
}
