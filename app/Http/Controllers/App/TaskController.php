<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(StoreTaskRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Task::class, $project]);

        $task = $project->tasks()->create([
            ...$request->validated(),
            'organization_id' => $project->organization_id,
            'created_by_user_id' => $request->user()->id,
        ]);

        ActivityLogger::log(
            $project->organization_id,
            $request->user(),
            $task,
            'task.created',
            [
                'task_id' => $task->id,
                'title' => $task->title,
                'status' => $task->status,
                'project_id' => $project->id,
                'project_title' => $project->title,
            ]
        );

        return back()->with('success', 'Task created.');
    }

    public function update(UpdateTaskRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $beforeStatus = $task->status;
        $before = $task->only(['title', 'status', 'assigned_to_user_id', 'due_date']);

        $task->update($request->validated());

        if ($beforeStatus !== $task->status) {
            ActivityLogger::log(
                $task->organization_id,
                $request->user(),
                $task,
                'task.status_changed',
                [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'from' => $beforeStatus,
                    'to' => $task->status,
                    'project_id' => $task->project_id,
                ]
            );
        } else {
            ActivityLogger::log(
                $task->organization_id,
                $request->user(),
                $task,
                'task.updated',
                [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'project_id' => $task->project_id,
                    'before' => $before,
                    'after' => $task->only(['title', 'status', 'assigned_to_user_id', 'due_date']),
                ]
            );
        }

        return back()->with('success', 'Task updated.');
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $beforeStatus = $task->status;

        $task->update($request->validated());

        if ($beforeStatus !== $task->status) {
            ActivityLogger::log(
                $task->organization_id,
                $request->user(),
                $task,
                'task.status_changed',
                [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'from' => $beforeStatus,
                    'to' => $task->status,
                    'project_id' => $task->project_id,
                ]
            );
        }

        return back()->with('success', 'Task updated.');
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back()->with('success', 'Task deleted.');
    }
}
