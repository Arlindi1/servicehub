<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class OrganizationController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if ($request->user()->organization_id !== null) {
            return redirect()->to(route($request->user()->homeRouteName(), absolute: false));
        }

        return Inertia::render('Onboarding/Organization', [
            'userEmail' => $request->user()->email,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->organization_id !== null) {
            return redirect()->to(route($request->user()->homeRouteName(), absolute: false));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $organization = Organization::create([
                'name' => $validated['name'],
            ]);

            $request->user()->organization()->associate($organization);
            $request->user()->save();

            $ownerRole = Role::findOrCreate('Owner');
            $request->user()->assignRole($ownerRole);
        });

        return redirect()->to(route('app.dashboard', absolute: false));
    }
}
