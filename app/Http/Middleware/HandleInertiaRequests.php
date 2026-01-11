<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['organization', 'roles']);
        }

        return [
            ...parent::share($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'reset_link' => fn () => $request->session()->get('reset_link'),
            ],
            'auth' => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                    ]
                    : null,
                'organization' => $user?->organization
                    ? [
                        'id' => $user->organization->id,
                        'name' => $user->organization->name,
                    ]
                    : null,
                'roles' => $user ? $user->getRoleNames()->values() : [],
            ],
            'notifications' => $user && $user->hasAnyRole(['Owner', 'Staff'])
                ? [
                    'unread_count' => fn () => $user->unreadNotifications()->count(),
                    'items' => fn () => $user->notifications()
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->map(fn ($notification) => [
                            'id' => $notification->id,
                            'read_at' => $notification->read_at?->toIso8601String(),
                            'created_at' => $notification->created_at?->toIso8601String(),
                            'data' => $notification->data,
                        ])
                        ->values(),
                ]
                : null,
        ];
    }
}
