<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberActiveRequest;
use App\Http\Requests\UpdateTeamMemberRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    private function findMember(Request $request, int $userId): User
    {
        return User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->whereKey($userId)
            ->firstOrFail();
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $members = User::query()
            ->where('organization_id', $request->user()->organization_id)
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->getRoleNames()->first(),
                'is_active' => (bool) $member->is_active,
                'created_at' => $member->created_at?->toDateString(),
                'can' => [
                    'update' => $request->user()->can('update', $member),
                ],
            ])
            ->values();

        return Inertia::render('App/Team/Index', [
            'members' => $members,
            'reference' => [
                'roles' => ['Staff', 'Client'],
            ],
            'can' => [
                'create' => $request->user()->can('create', User::class),
            ],
        ]);
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $password = $request->boolean('set_password_later')
            ? Str::password(32)
            : $request->string('password')->value();

        $user = User::create([
            'organization_id' => $request->user()->organization_id,
            'name' => $request->string('name')->trim()->value(),
            'email' => $request->string('email')->trim()->lower()->value(),
            'password' => Hash::make($password),
        ]);

        $user->forceFill([
            'email_verified_at' => now(),
            'is_active' => true,
        ])->save();

        $user->syncRoles([Role::findOrCreate('Staff')]);

        $redirect = redirect()
            ->to(route('app.team.index', absolute: false))
            ->with('success', 'Staff member added.');

        if ($request->boolean('set_password_later')) {
            $token = Password::broker()->createToken($user);
            $resetLink = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ], absolute: false);

            $redirect->with('reset_link', $resetLink);
        }

        return $redirect;
    }

    public function updateRole(UpdateTeamMemberRoleRequest $request, int $user): RedirectResponse
    {
        $member = $this->findMember($request, $user);

        $this->authorize('update', $member);

        $roleName = $request->string('role')->value();

        $member->syncRoles([Role::findOrCreate($roleName)]);

        return back()->with('success', 'Role updated.');
    }

    public function updateActive(UpdateTeamMemberActiveRequest $request, int $user): RedirectResponse
    {
        $member = $this->findMember($request, $user);

        $this->authorize('update', $member);

        $isActive = $request->boolean('is_active');

        $member->forceFill(['is_active' => $isActive])->save();

        if (! $isActive) {
            DB::table('sessions')->where('user_id', $member->id)->delete();
        }

        return back()->with('success', $isActive ? 'User reactivated.' : 'User deactivated.');
    }
}
