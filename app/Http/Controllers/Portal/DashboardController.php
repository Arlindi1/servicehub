<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $activeProjects = Project::query()->where('status', 'Active')->count();
        $outstandingInvoices = Invoice::query()->whereIn('status', ['Sent', 'Overdue'])->count();

        $recentComments = Comment::query()
            ->with(['project:id,title', 'author:id,name'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'project_id', 'author_user_id', 'author_type', 'body', 'created_at'])
            ->map(fn (Comment $comment) => [
                'type' => 'comment',
                'id' => $comment->id,
                'project_id' => $comment->project_id,
                'project_title' => $comment->project?->title,
                'title' => 'New comment',
                'message' => Str::of($comment->body)->limit(120)->value(),
                'created_at' => $comment->created_at?->toIso8601String(),
                'url' => route('portal.projects.show', ['project' => $comment->project_id, 'tab' => 'discussion'], absolute: false),
            ]);

        $recentFiles = ProjectFile::query()
            ->with(['project:id,title'])
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'project_id', 'original_name', 'file_type', 'created_at'])
            ->map(fn (ProjectFile $file) => [
                'type' => 'file',
                'id' => $file->id,
                'project_id' => $file->project_id,
                'project_title' => $file->project?->title,
                'title' => 'New file',
                'message' => "{$file->original_name} ({$file->file_type})",
                'created_at' => $file->created_at?->toIso8601String(),
                'url' => route('portal.projects.show', ['project' => $file->project_id, 'tab' => 'files'], absolute: false),
            ]);

        $updates = $recentComments
            ->merge($recentFiles)
            ->sortByDesc(fn ($item) => $item['created_at'] ?? '')
            ->take(10)
            ->values();

        return Inertia::render('Portal/Dashboard', [
            'stats' => [
                'active_projects' => $activeProjects,
                'outstanding_invoices' => $outstandingInvoices,
            ],
            'updates' => $updates,
        ]);
    }
}

