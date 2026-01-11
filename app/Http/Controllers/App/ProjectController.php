<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectMetaRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Comment;
use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Task;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $clientId = $request->filled('client_id') ? (int) $request->input('client_id') : null;

        $status = in_array($status, Project::STATUSES, true) ? $status : null;

        $projects = Project::query()
            ->with([
                'client:id,name',
                'staff:id,name',
            ])
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByRaw('due_date is null, due_date')
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('App/Projects/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'client_id' => $clientId,
            ],
            'reference' => [
                'statuses' => Project::STATUSES,
            ],
            'can' => [
                'create' => $request->user()->can('create', Project::class),
            ],
            'clients' => $clients,
            'projects' => $projects->through(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status,
                'priority' => $project->priority,
                'due_date' => $project->due_date?->toDateString(),
                'client' => $project->client
                    ? [
                        'id' => $project->client->id,
                        'name' => $project->client->name,
                    ]
                    : null,
                'assignees' => $project->staff->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ])->values(),
                'can' => [
                    'view' => $request->user()->can('view', $project),
                    'update' => $request->user()->can('update', $project),
                ],
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Project::class);

        $organizationId = $request->user()->organization_id;
        $prefillClientId = $request->filled('client_id') ? (int) $request->input('client_id') : null;

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($prefillClientId && ! $clients->contains('id', $prefillClientId)) {
            $prefillClientId = null;
        }

        $staffUsers = User::query()
            ->where('organization_id', $organizationId)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Owner', 'Staff']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('App/Projects/Create', [
            'reference' => [
                'statuses' => Project::STATUSES,
                'priorities' => Project::PRIORITIES,
            ],
            'clients' => $clients,
            'staffUsers' => $staffUsers,
            'prefillClientId' => $prefillClientId,
        ]);
    }

    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $staffIds = $validated['staff_ids'] ?? [];
        unset($validated['staff_ids']);

        $organizationId = $request->user()->organization_id;

        $project = Project::create([
            ...$validated,
            'organization_id' => $organizationId,
            'created_by_user_id' => $request->user()->id,
        ]);

        $project->staff()->syncWithPivotValues($staffIds, [
            'organization_id' => $organizationId,
        ]);

        $project->loadMissing(['client:id,name']);

        ActivityLogger::log(
            $organizationId,
            $request->user(),
            $project,
            'project.created',
            [
                'project_id' => $project->id,
                'title' => $project->title,
                'status' => $project->status,
                'priority' => $project->priority,
                'due_date' => $project->due_date?->toDateString(),
                'client_id' => $project->client_id,
                'client_name' => $project->client?->name,
            ]
        );

        return redirect()
            ->to(route('app.projects.show', $project, absolute: false))
            ->with('success', 'Project created.');
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $tab = $request->string('tab')->trim()->lower()->value();
        $tab = in_array($tab, ['overview', 'tasks', 'files', 'discussion', 'invoice'], true) ? $tab : 'overview';

        $project->loadMissing([
            'client:id,name,email',
            'staff:id,name,email',
            'createdBy:id,name',
        ]);

        $tasks = null;
        $taskStatus = null;
        $taskAssignedToUserId = null;
        $taskAssignees = [];
        $taskCanCreate = false;
        $files = null;
        $fileTypes = [];
        $fileCanCreate = false;
        $comments = null;
        $commentCanCreate = false;

        if ($tab === 'tasks') {
            $taskStatus = $request->string('task_status')->trim()->value();
            $taskStatus = in_array($taskStatus, Task::STATUSES, true) ? $taskStatus : null;

            $taskAssignedToUserId = $request->filled('task_assigned_to_user_id')
                ? (int) $request->input('task_assigned_to_user_id')
                : null;

            $tasks = $project->tasks()
                ->with([
                    'assignee:id,name',
                    'createdBy:id,name',
                ])
                ->when($taskStatus, fn ($query) => $query->where('status', $taskStatus))
                ->when($taskAssignedToUserId, fn ($query) => $query->where('assigned_to_user_id', $taskAssignedToUserId))
                ->orderByRaw('due_date is null, due_date')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString();

            $taskAssignees = User::query()
                ->where('organization_id', $request->user()->organization_id)
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Owner', 'Staff']))
                ->orderBy('name')
                ->get(['id', 'name']);

            $taskCanCreate = $request->user()->can('create', [Task::class, $project]);
        }

        if ($tab === 'files') {
            $files = $project->files()
                ->with(['uploadedBy:id,name'])
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString();

            $fileTypes = ProjectFile::FILE_TYPES;
            $fileCanCreate = $request->user()->can('create', [ProjectFile::class, $project]);
        }

        if ($tab === 'discussion') {
            $comments = $project->comments()
                ->with(['author:id,name'])
                ->orderByDesc('id')
                ->paginate(50)
                ->withQueryString();

            $commentCanCreate = $request->user()->can('create', [Comment::class, $project]);
        }

        return Inertia::render('App/Projects/Show', [
            'tab' => $tab,
            'reference' => [
                'statuses' => Project::STATUSES,
                'priorities' => Project::PRIORITIES,
            ],
            'taskFilters' => [
                'status' => $taskStatus,
                'assigned_to_user_id' => $taskAssignedToUserId,
            ],
            'taskReference' => [
                'statuses' => Task::STATUSES,
                'assignees' => $taskAssignees,
            ],
            'taskCan' => [
                'create' => $taskCanCreate,
            ],
            'fileReference' => [
                'types' => $fileTypes,
            ],
            'fileCan' => [
                'create' => $fileCanCreate,
            ],
            'commentCan' => [
                'create' => $commentCanCreate,
            ],
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status,
                'priority' => $project->priority,
                'due_date' => $project->due_date?->toDateString(),
                'created_at' => $project->created_at?->toDateTimeString(),
                'client' => $project->client
                    ? [
                        'id' => $project->client->id,
                        'name' => $project->client->name,
                        'email' => $project->client->email,
                    ]
                    : null,
                'created_by' => $project->createdBy
                    ? [
                        'id' => $project->createdBy->id,
                        'name' => $project->createdBy->name,
                    ]
                    : null,
                'assignees' => $project->staff->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ])->values(),
            ],
            'tasks' => $tasks?->through(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'assigned_to_user_id' => $task->assigned_to_user_id,
                'due_date' => $task->due_date?->toDateString(),
                'assignee' => $task->assignee
                    ? [
                        'id' => $task->assignee->id,
                        'name' => $task->assignee->name,
                    ]
                    : null,
                'created_by' => $task->createdBy
                    ? [
                        'id' => $task->createdBy->id,
                        'name' => $task->createdBy->name,
                    ]
                    : null,
                'can' => [
                    'update' => $request->user()->can('update', $task),
                    'delete' => $request->user()->can('delete', $task),
                ],
            ]),
            'files' => $files?->through(fn (ProjectFile $file) => [
                'id' => $file->id,
                'file_type' => $file->file_type,
                'uploader_type' => $file->uploader_type,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->size,
                'created_at' => $file->created_at?->toDateTimeString(),
                'uploader' => $file->uploadedBy
                    ? [
                        'id' => $file->uploadedBy->id,
                        'name' => $file->uploadedBy->name,
                    ]
                    : null,
                'can' => [
                    'delete' => $request->user()->can('delete', $file),
                ],
            ]),
            'comments' => $comments?->through(fn (Comment $comment) => [
                'id' => $comment->id,
                'author_type' => $comment->author_type,
                'author' => $comment->author
                    ? [
                        'id' => $comment->author->id,
                        'name' => $comment->author->name,
                    ]
                    : null,
                'body' => $comment->body,
                'created_at' => $comment->created_at?->toDateTimeString(),
                'can' => [
                    'delete' => $request->user()->can('delete', $comment),
                ],
            ]),
            'can' => [
                'update' => $request->user()->can('update', $project),
                'delete' => $request->user()->can('delete', $project),
            ],
        ]);
    }

    public function edit(Request $request, Project $project): Response
    {
        $this->authorize('update', $project);

        $organizationId = $request->user()->organization_id;

        $project->loadMissing(['staff:id']);

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $staffUsers = User::query()
            ->where('organization_id', $organizationId)
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Owner', 'Staff']))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return Inertia::render('App/Projects/Edit', [
            'reference' => [
                'statuses' => Project::STATUSES,
                'priorities' => Project::PRIORITIES,
            ],
            'clients' => $clients,
            'staffUsers' => $staffUsers,
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
                'description' => $project->description,
                'status' => $project->status,
                'priority' => $project->priority,
                'due_date' => $project->due_date?->toDateString(),
                'client_id' => $project->client_id,
                'staff_ids' => $project->staff->pluck('id')->values(),
            ],
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $beforeStatus = $project->status;
        $before = $project->only(['title', 'description', 'status', 'priority', 'due_date', 'client_id']);

        $validated = $request->validated();
        $staffIds = $validated['staff_ids'] ?? [];
        unset($validated['staff_ids']);

        $project->update($validated);

        $project->staff()->syncWithPivotValues($staffIds, [
            'organization_id' => $project->organization_id,
        ]);

        $project->loadMissing(['client:id,name']);

        if ($beforeStatus !== $project->status) {
            ActivityLogger::log(
                $project->organization_id,
                $request->user(),
                $project,
                'project.status_changed',
                [
                    'project_id' => $project->id,
                    'title' => $project->title,
                    'from' => $beforeStatus,
                    'to' => $project->status,
                    'client_id' => $project->client_id,
                    'client_name' => $project->client?->name,
                ]
            );
        } else {
            ActivityLogger::log(
                $project->organization_id,
                $request->user(),
                $project,
                'project.updated',
                [
                    'project_id' => $project->id,
                    'title' => $project->title,
                    'before' => $before,
                    'after' => $project->only(['title', 'description', 'status', 'priority', 'due_date', 'client_id']),
                    'client_id' => $project->client_id,
                    'client_name' => $project->client?->name,
                ]
            );
        }

        return redirect()
            ->to(route('app.projects.show', $project, absolute: false))
            ->with('success', 'Project updated.');
    }

    public function updateMeta(UpdateProjectMetaRequest $request, Project $project): RedirectResponse
    {
        $beforeStatus = $project->status;
        $before = $project->only(['status', 'priority', 'due_date']);

        $project->update($request->validated());

        if ($beforeStatus !== $project->status) {
            ActivityLogger::log(
                $project->organization_id,
                $request->user(),
                $project,
                'project.status_changed',
                [
                    'project_id' => $project->id,
                    'title' => $project->title,
                    'from' => $beforeStatus,
                    'to' => $project->status,
                ]
            );
        } else {
            ActivityLogger::log(
                $project->organization_id,
                $request->user(),
                $project,
                'project.updated',
                [
                    'project_id' => $project->id,
                    'title' => $project->title,
                    'before' => $before,
                    'after' => $project->only(['status', 'priority', 'due_date']),
                ]
            );
        }

        return back()->with('success', 'Project updated.');
    }

    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return redirect()
            ->to(route('app.projects.index', absolute: false))
            ->with('success', 'Project deleted.');
    }
}
