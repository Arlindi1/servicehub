<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $redirectRoute = $request->user()->organization_id === null
            ? 'onboarding.organization.create'
            : $request->user()->homeRouteName();

        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($redirectRoute, absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
