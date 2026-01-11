<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Comment;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ActivityLog::class);

        $event = $request->string('event')->trim()->value();
        $subjectType = $request->string('subject_type')->trim()->value();
        $from = $request->string('from')->trim()->value();
        $to = $request->string('to')->trim()->value();

        $query = ActivityLog::query()
            ->with(['actor:id,name'])
            ->when($event, fn ($q) => $q->where('event', $event))
            ->when($subjectType, fn ($q) => $q->where('subject_type', $subjectType))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->orderByDesc('created_at');

        $activity = $query
            ->paginate(20)
            ->withQueryString();

        $eventOptions = ActivityLog::query()
            ->select('event')
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->values();

        $subjectTypeOptions = ActivityLog::query()
            ->select('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->values();

        return Inertia::render('App/Activity/Index', [
            'filters' => [
                'event' => $event ?: null,
                'subject_type' => $subjectType ?: null,
                'from' => $from ?: null,
                'to' => $to ?: null,
            ],
            'reference' => [
                'events' => $eventOptions,
                'subject_types' => $subjectTypeOptions,
            ],
            'activity' => $activity->through(fn (ActivityLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'subject_type' => $log->subject_type,
                'subject_label' => $this->subjectLabel($log->subject_type),
                'actor_type' => $log->actor_type,
                'actor' => $log->actor
                    ? [
                        'id' => $log->actor->id,
                        'name' => $log->actor->name,
                    ]
                    : null,
                'description' => $log->description ?? [],
                'created_at' => $log->created_at?->toIso8601String(),
                'url' => $this->subjectUrl($log),
            ]),
        ]);
    }

    private function subjectLabel(string $subjectType): string
    {
        return match ($subjectType) {
            Client::class => 'Client',
            Project::class => 'Project',
            Task::class => 'Task',
            ProjectFile::class => 'File',
            Comment::class => 'Comment',
            Invoice::class => 'Invoice',
            default => class_basename($subjectType),
        };
    }

    private function subjectUrl(ActivityLog $log): ?string
    {
        $description = is_array($log->description) ? $log->description : [];

        if ($log->subject_type === Client::class) {
            return route('app.clients.show', $log->subject_id, absolute: false);
        }

        if ($log->subject_type === Project::class) {
            return route('app.projects.show', $log->subject_id, absolute: false);
        }

        if ($log->subject_type === Invoice::class) {
            return route('app.invoices.show', $log->subject_id, absolute: false);
        }

        $projectId = $description['project_id'] ?? null;

        if (! $projectId) {
            return null;
        }

        if ($log->subject_type === Task::class) {
            return route('app.projects.show', ['project' => $projectId, 'tab' => 'tasks'], absolute: false);
        }

        if ($log->subject_type === ProjectFile::class) {
            return route('app.projects.show', ['project' => $projectId, 'tab' => 'files'], absolute: false);
        }

        if ($log->subject_type === Comment::class) {
            return route('app.projects.show', ['project' => $projectId, 'tab' => 'discussion'], absolute: false);
        }

        return null;
    }
}

