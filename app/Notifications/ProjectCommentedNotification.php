<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ProjectCommentedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public Comment $comment
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $projectTitle = $this->project->title;

        return [
            'title' => 'New project comment',
            'message' => "New client comment on {$projectTitle}: ".Str::of($this->comment->body)->limit(100)->value(),
            'project_id' => $this->project->id,
            'comment_id' => $this->comment->id,
            'url' => route('app.projects.show', ['project' => $this->project->id, 'tab' => 'discussion'], absolute: false),
        ];
    }
}

