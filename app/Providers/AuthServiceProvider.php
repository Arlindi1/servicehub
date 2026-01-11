<?php

namespace App\Providers;

use App\Models\Comment;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Task;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\CommentPolicy;
use App\Policies\ClientPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ProjectFilePolicy;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        ActivityLog::class => ActivityLogPolicy::class,
        Comment::class => CommentPolicy::class,
        Client::class => ClientPolicy::class,
        Invoice::class => InvoicePolicy::class,
        Organization::class => OrganizationPolicy::class,
        Project::class => ProjectPolicy::class,
        ProjectFile::class => ProjectFilePolicy::class,
        Task::class => TaskPolicy::class,
        User::class => UserPolicy::class,
    ];
}
