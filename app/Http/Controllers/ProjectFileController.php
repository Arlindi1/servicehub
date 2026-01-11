<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectFileRequest;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\Notifications\ClientUploadedFileNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectFileController extends Controller
{
    public function store(StoreProjectFileRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [ProjectFile::class, $project]);

        $uploadedFile = $request->file('file');
        abort_unless($uploadedFile, 422);

        $extension = $uploadedFile->getClientOriginalExtension();
        $filename = Str::uuid()->toString().($extension ? '.'.$extension : '');

        $directory = "project-files/{$project->organization_id}/{$project->id}";
        $path = $uploadedFile->storeAs($directory, $filename, 'local');

        $projectFile = $project->files()->create([
            'organization_id' => $project->organization_id,
            'uploaded_by_user_id' => $request->user()->id,
            'uploader_type' => $request->user()->hasRole('Client') ? 'client' : 'staff',
            'file_type' => $request->validated('file_type'),
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $uploadedFile->getMimeType() ?? 'application/octet-stream',
            'size' => $uploadedFile->getSize() ?? 0,
        ]);

        ActivityLogger::log(
            $project->organization_id,
            $request->user(),
            $projectFile,
            'file.uploaded',
            [
                'file_id' => $projectFile->id,
                'project_id' => $project->id,
                'project_title' => $project->title,
                'uploader_type' => $projectFile->uploader_type,
                'file_type' => $projectFile->file_type,
                'original_name' => $projectFile->original_name,
            ]
        );

        if ($request->user()->hasRole('Client') && $projectFile->file_type === 'Client Upload') {
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

            Notification::send($recipients, new ClientUploadedFileNotification($project, $projectFile));
        }

        return back()->with('success', 'File uploaded.');
    }

    public function download(Request $request, ProjectFile $projectFile): StreamedResponse
    {
        $this->authorize('view', $projectFile);

        abort_unless(Storage::disk('local')->exists($projectFile->path), 404);

        return Storage::disk('local')->download($projectFile->path, $projectFile->original_name);
    }

    public function destroy(Request $request, ProjectFile $projectFile): RedirectResponse
    {
        $this->authorize('delete', $projectFile);

        ActivityLogger::log(
            $projectFile->organization_id,
            $request->user(),
            $projectFile,
            'file.deleted',
            [
                'file_id' => $projectFile->id,
                'project_id' => $projectFile->project_id,
                'uploader_type' => $projectFile->uploader_type,
                'file_type' => $projectFile->file_type,
                'original_name' => $projectFile->original_name,
            ]
        );

        Storage::disk('local')->delete($projectFile->path);
        $projectFile->delete();

        return back()->with('success', 'File deleted.');
    }
}
