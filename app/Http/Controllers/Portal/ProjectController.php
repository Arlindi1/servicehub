<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Project::class);

        $search = $request->string('search')->trim()->value();

        $projects = Project::query()
            ->with(['client:id,name'])
            ->when($search, fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->orderByRaw('due_date is null, due_date')
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Portal/Projects/Index', [
            'filters' => [
                'search' => $search,
            ],
            'projects' => $projects->through(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status,
                'due_date' => $project->due_date?->toDateString(),
            ]),
        ]);
    }

    public function show(Request $request, Project $project): Response
    {
        $this->authorize('view', $project);

        $tab = $request->string('tab')->trim()->lower()->value();
        $tab = in_array($tab, ['overview', 'tasks', 'files', 'discussion', 'invoice'], true) ? $tab : 'overview';

        $project->loadMissing([
            'client:id,name,email',
            'staff:id,name,email',
        ]);

        $tasks = null;
        $taskStatus = null;
        $taskAssignedToUserId = null;
        $taskAssignees = [];
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
                ->with(['assignee:id,name'])
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
        }

        if ($tab === 'files') {
            $files = $project->files()
                ->with(['uploadedBy:id,name'])
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString();

            $fileTypes = ['Client Upload'];
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

        return Inertia::render('Portal/Projects/Show', [
            'tab' => $tab,
            'taskFilters' => [
                'status' => $taskStatus,
                'assigned_to_user_id' => $taskAssignedToUserId,
            ],
            'taskReference' => [
                'statuses' => Task::STATUSES,
                'assignees' => $taskAssignees,
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
                'client' => $project->client
                    ? [
                        'id' => $project->client->id,
                        'name' => $project->client->name,
                        'email' => $project->client->email,
                    ]
                    : null,
                'assignees' => $project->staff->map(fn ($user) => [
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
            ]),
        ]);
    }
}
