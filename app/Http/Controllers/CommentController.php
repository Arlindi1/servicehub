<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ProjectCommentedNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CommentController extends Controller
{
    public function store(StoreCommentRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Comment::class, $project]);

        $comment = $project->comments()->create([
            'organization_id' => $project->organization_id,
            'author_user_id' => $request->user()->id,
            'author_type' => $request->user()->hasRole('Client') ? 'client' : 'staff',
            'body' => $request->validated('body'),
        ]);

        ActivityLogger::log(
            $project->organization_id,
            $request->user(),
            $comment,
            'comment.posted',
            [
                'comment_id' => $comment->id,
                'project_id' => $project->id,
                'project_title' => $project->title,
                'author_type' => $comment->author_type,
            ]
        );

        if ($request->user()->hasRole('Client')) {
            $project->loadMissing(['staff:id,name']);

            $owners = User::query()
                ->where('organization_id', $project->organization_id)
                ->whereHas('roles', fn ($query) => $query->where('name', 'Owner'))
                ->get(['id', 'name']);

            $recipients = $owners
                ->merge($project->staff)
                ->unique('id')
                ->reject(fn (User $user) => $user->id === $request->user()->id)
                ->values();

            Notification::send($recipients, new ProjectCommentedNotification($project, $comment));
        }

        return back()->with('success', 'Comment posted.');
    }

    public function destroy(Request $request, Comment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }
}
