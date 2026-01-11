<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ClientUploadedFileNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public ProjectFile $file
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
        $fileName = $this->file->original_name;

        return [
            'title' => 'New client upload',
            'message' => "{$fileName} was uploaded to {$projectTitle}.",
            'project_id' => $this->project->id,
            'file_id' => $this->file->id,
            'url' => route('app.projects.show', ['project' => $this->project->id, 'tab' => 'files'], absolute: false),
        ];
    }
}

