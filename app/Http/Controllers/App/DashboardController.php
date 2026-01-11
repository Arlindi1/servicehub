<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $activeProjects = Project::query()->where('status', 'Active')->count();
        $waitingOnClient = Project::query()->where('status', 'Waiting on Client')->count();

        $tasksDueSoon = Task::query()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->where('status', '!=', 'Done')
            ->count();

        $draftInvoices = Invoice::query()->where('status', 'Draft')->count();
        $sentInvoices = Invoice::query()->whereIn('status', ['Sent', 'Overdue'])->count();

        $recentActivity = ActivityLog::query()
            ->with(['actor:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'event' => $log->event,
                'actor_type' => $log->actor_type,
                'actor' => $log->actor
                    ? [
                        'id' => $log->actor->id,
                        'name' => $log->actor->name,
                    ]
                    : null,
                'description' => $log->description ?? [],
                'created_at' => $log->created_at?->toIso8601String(),
            ])
            ->values();

        return Inertia::render('App/Dashboard', [
            'stats' => [
                'active_projects' => $activeProjects,
                'waiting_on_client' => $waitingOnClient,
                'tasks_due_soon' => $tasksDueSoon,
                'draft_invoices' => $draftInvoices,
                'sent_invoices' => $sentInvoices,
            ],
            'recentActivity' => $recentActivity,
        ]);
    }
}

