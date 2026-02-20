<?php

use App\Http\Controllers\Onboarding\OrganizationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\App\ActivityController;
use App\Http\Controllers\App\ClientController;
use App\Http\Controllers\App\InvoiceController;
use App\Http\Controllers\App\DashboardController as AppDashboardController;
use App\Http\Controllers\App\NotificationController;
use App\Http\Controllers\App\ProjectController;
use App\Http\Controllers\App\SettingsController;
use App\Http\Controllers\App\TaskController;
use App\Http\Controllers\App\TeamController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\ProjectFileController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\InvoiceController as PortalInvoiceController;
use App\Http\Controllers\Portal\ProjectController as PortalProjectController;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

Route::get('/', HomeController::class);

Route::middleware(['auth'])->get('/dashboard', function (Request $request) {
    $homeRoute = $request->user()?->organization_id === null
        ? 'onboarding.organization.create'
        : $request->user()->homeRouteName();

    return redirect()->to(route($homeRoute, absolute: false));
})->name('dashboard');

Route::get('/demo-login', function () {
    abort_unless(config('app.demo_login_enabled'), 404);

    $existingDemoUser = User::query()
        ->where('email', 'demo@servicehub.test')
        ->first();

    $organization = $existingDemoUser?->organization
        ?? Organization::query()->where('name', 'Acme Studio')->first()
        ?? Organization::create([
            'name' => 'ServiceHub Demo',
            'brand_color' => '#0ea5e9',
            'invoice_prefix' => 'INV',
            'invoice_due_days_default' => 14,
            'billing_email' => 'billing@servicehub.test',
        ]);

    $ownerRole = Role::findOrCreate('Owner');

    $demoUser = User::updateOrCreate(
        ['email' => 'demo@servicehub.test'],
        [
            'organization_id' => $organization->id,
            'name' => $existingDemoUser?->name ?? 'Demo User',
            'password' => Hash::make('password'),
            'is_active' => true,
        ],
    );

    if (! $demoUser->hasVerifiedEmail() || ! $demoUser->is_active || $demoUser->organization_id !== $organization->id) {
        $demoUser->forceFill([
            'organization_id' => $organization->id,
            'email_verified_at' => $demoUser->email_verified_at ?? now(),
            'is_active' => true,
        ])->save();
    }

    $demoUser->syncRoles([$ownerRole]);

    Auth::login($demoUser);

    $redirectTo = Route::has('dashboard')
        ? route('dashboard', absolute: false)
        : '/dashboard';

    return redirect()->to($redirectTo);
})->name('demo.login');

Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding/organization', [OrganizationController::class, 'create'])
        ->name('onboarding.organization.create');
    Route::post('/onboarding/organization', [OrganizationController::class, 'store'])
        ->name('onboarding.organization.store');
});

Route::middleware(['auth', 'verified', 'has-organization'])->group(function () {
    Route::get('/files/{projectFile}/download', [ProjectFileController::class, 'download'])
        ->whereNumber('projectFile')
        ->name('files.download');

    Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'download'])
        ->whereNumber('invoice')
        ->name('invoices.pdf');
});

Route::middleware(['auth', 'verified', 'has-organization', 'role:Owner|Staff'])
    ->prefix('app')
    ->name('app.')
    ->group(function () {
        Route::get('/dashboard', AppDashboardController::class)->name('dashboard');

        Route::patch('/projects/{project}/meta', [ProjectController::class, 'updateMeta'])
            ->whereNumber('project')
            ->name('projects.meta.update');

        Route::post('/projects/{project}/tasks', [TaskController::class, 'store'])
            ->whereNumber('project')
            ->name('projects.tasks.store');

        Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])
            ->whereNumber('project')
            ->name('projects.files.store');

        Route::post('/projects/{project}/comments', [CommentController::class, 'store'])
            ->whereNumber('project')
            ->name('projects.comments.store');

        Route::patch('/tasks/{task}', [TaskController::class, 'update'])
            ->whereNumber('task')
            ->name('tasks.update');

        Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])
            ->whereNumber('task')
            ->name('tasks.status.update');

        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])
            ->whereNumber('task')
            ->name('tasks.destroy');

        Route::delete('/files/{projectFile}', [ProjectFileController::class, 'destroy'])
            ->whereNumber('projectFile')
            ->name('files.destroy');

        Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])
            ->whereNumber('comment')
            ->name('comments.destroy');

        Route::resource('projects', ProjectController::class)
            ->whereNumber('project');

        Route::resource('clients', ClientController::class)
            ->whereNumber('client');

        Route::patch('/invoices/{invoice}/mark-sent', [InvoiceController::class, 'markSent'])
            ->whereNumber('invoice')
            ->name('invoices.markSent');

        Route::patch('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid'])
            ->whereNumber('invoice')
            ->name('invoices.markPaid');

        Route::patch('/invoices/{invoice}/void', [InvoiceController::class, 'void'])
            ->whereNumber('invoice')
            ->name('invoices.void');

        Route::resource('invoices', InvoiceController::class)
            ->whereNumber('invoice');

        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
            ->whereUuid('notification')
            ->name('notifications.read');

        Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
            ->name('notifications.readAll');

        Route::middleware('role:Owner')->group(function () {
            Route::get('/team', [TeamController::class, 'index'])->name('team.index');
            Route::post('/team', [TeamController::class, 'store'])->name('team.store');
            Route::patch('/team/{user}/role', [TeamController::class, 'updateRole'])
                ->whereNumber('user')
                ->name('team.role.update');
            Route::patch('/team/{user}/active', [TeamController::class, 'updateActive'])
                ->whereNumber('user')
                ->name('team.active.update');
            Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.index');
            Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::get('/settings/logo', [SettingsController::class, 'logo'])->name('settings.logo');
            Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
        });

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

Route::middleware(['auth', 'verified', 'has-organization', 'role:Client'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function () {
        Route::get('/dashboard', PortalDashboardController::class)->name('dashboard');

        Route::resource('projects', PortalProjectController::class)
            ->only(['index', 'show'])
            ->whereNumber('project');

        Route::post('/projects/{project}/files', [ProjectFileController::class, 'store'])
            ->whereNumber('project')
            ->name('projects.files.store');

        Route::post('/projects/{project}/comments', [CommentController::class, 'store'])
            ->whereNumber('project')
            ->name('projects.comments.store');

        Route::resource('invoices', PortalInvoiceController::class)
            ->only(['index', 'show'])
            ->whereNumber('invoice');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

require __DIR__.'/auth.php';
