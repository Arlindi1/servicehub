<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrganizationSettingsRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        /** @var Organization $organization */
        $organization = $request->user()->organization;

        $this->authorize('update', $organization);

        return Inertia::render('App/Settings/Index', [
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'brand_color' => $organization->brand_color,
                'logo_path' => $organization->logo_path,
                'invoice_prefix' => $organization->invoice_prefix ?: 'INV',
                'invoice_due_days_default' => $organization->invoice_due_days_default ?? 14,
                'billing_email' => $organization->billing_email,
            ],
            'logoUrl' => $organization->logo_path ? route('app.settings.logo', absolute: false) : null,
        ]);
    }

    public function update(UpdateOrganizationSettingsRequest $request): RedirectResponse
    {
        /** @var Organization $organization */
        $organization = $request->user()->organization;

        $this->authorize('update', $organization);

        $validated = $request->validated();
        unset($validated['logo']);

        $validated['invoice_prefix'] = $validated['invoice_prefix'] ?: 'INV';
        $validated['invoice_due_days_default'] = (int) $validated['invoice_due_days_default'];

        if ($request->hasFile('logo')) {
            $previousPath = $organization->logo_path;

            $file = $request->file('logo');
            $extension = $file->getClientOriginalExtension() ?: 'png';
            $filename = Str::uuid().'.'.$extension;

            $validated['logo_path'] = $file->storeAs("org-logos/{$organization->id}", $filename);

            if ($previousPath && Storage::disk('local')->exists($previousPath)) {
                Storage::disk('local')->delete($previousPath);
            }
        }

        $organization->update($validated);

        return back()->with('success', 'Settings saved.');
    }

    public function logo(Request $request)
    {
        /** @var Organization $organization */
        $organization = $request->user()->organization;

        $this->authorize('update', $organization);

        if (! $organization->logo_path || ! Storage::disk('local')->exists($organization->logo_path)) {
            abort(404);
        }

        return Storage::disk('local')->response($organization->logo_path);
    }
}
