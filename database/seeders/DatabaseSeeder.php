<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Comment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ClientUploadedFileNotification;
use App\Notifications\InvoiceSentNotification;
use App\Notifications\ProjectCommentedNotification;
use App\Services\ActivityLogger;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ownerRole = Role::findOrCreate('Owner');
        $staffRole = Role::findOrCreate('Staff');
        $clientRole = Role::findOrCreate('Client');

        $organization = Organization::firstOrCreate([
            'name' => 'Acme Studio',
        ]);
        $organization->update([
            'brand_color' => $organization->brand_color ?? '#0ea5e9',
            'invoice_prefix' => $organization->invoice_prefix ?? 'INV',
            'invoice_due_days_default' => $organization->invoice_due_days_default ?? 14,
            'billing_email' => $organization->billing_email ?? 'billing@acme.test',
        ]);

        $owner = User::updateOrCreate(['email' => 'owner@acme.test'], [
            'organization_id' => $organization->id,
            'name' => 'Alex Owner',
            'password' => Hash::make('password'),
        ]);
        $owner->forceFill(['email_verified_at' => now()])->save();
        $owner->syncRoles([$ownerRole]);

        $staff1 = User::updateOrCreate(['email' => 'staff1@acme.test'], [
            'organization_id' => $organization->id,
            'name' => 'Sam Staff',
            'password' => Hash::make('password'),
        ]);
        $staff1->forceFill(['email_verified_at' => now()])->save();
        $staff1->syncRoles([$staffRole]);

        $staff2 = User::updateOrCreate(['email' => 'staff2@acme.test'], [
            'organization_id' => $organization->id,
            'name' => 'Taylor Staff',
            'password' => Hash::make('password'),
        ]);
        $staff2->forceFill(['email_verified_at' => now()])->save();
        $staff2->syncRoles([$staffRole]);

        $client1 = User::updateOrCreate(['email' => 'client1@acme.test'], [
            'organization_id' => $organization->id,
            'name' => 'Casey Client',
            'password' => Hash::make('password'),
        ]);
        $client1->forceFill(['email_verified_at' => now()])->save();
        $client1->syncRoles([$clientRole]);

        $client2 = User::updateOrCreate(['email' => 'client2@acme.test'], [
            'organization_id' => $organization->id,
            'name' => 'Riley Client',
            'password' => Hash::make('password'),
        ]);
        $client2->forceFill(['email_verified_at' => now()])->save();
        $client2->syncRoles([$clientRole]);

        $client3 = User::updateOrCreate(['email' => 'client3@acme.test'], [
            'organization_id' => $organization->id,
            'name' => 'Jordan Client',
            'password' => Hash::make('password'),
        ]);
        $client3->forceFill(['email_verified_at' => now()])->save();
        $client3->syncRoles([$clientRole]);

        $clientRecord1 = Client::updateOrCreate([
            'organization_id' => $organization->id,
            'email' => 'client1@acme.test',
        ], [
            'user_id' => $client1->id,
            'name' => 'Casey Client',
            'phone' => '(555) 010-1001',
            'notes' => 'Primary point of contact for deliverables and invoices.',
        ]);

        $clientRecord2 = Client::updateOrCreate([
            'organization_id' => $organization->id,
            'email' => 'client2@acme.test',
        ], [
            'user_id' => $client2->id,
            'name' => 'Riley Client',
            'phone' => '(555) 010-1002',
            'notes' => 'Prefers email updates on milestones.',
        ]);

        $clientRecord3 = Client::updateOrCreate([
            'organization_id' => $organization->id,
            'email' => 'client3@acme.test',
        ], [
            'user_id' => $client3->id,
            'name' => 'Jordan Client',
            'phone' => null,
            'notes' => null,
        ]);

        $projects = [
            [
                'title' => 'Website Redesign',
                'description' => 'Full website redesign with updated IA and responsive components.',
                'status' => 'Active',
                'priority' => 'high',
                'due_date' => now()->addDays(14)->toDateString(),
                'client_id' => $clientRecord1->id,
                'created_by_user_id' => $owner->id,
                'staff_ids' => [$staff1->id, $staff2->id],
            ],
            [
                'title' => 'Brand Identity Kit',
                'description' => 'Logo refresh, typography and color palette documentation.',
                'status' => 'Waiting on Client',
                'priority' => 'medium',
                'due_date' => now()->addDays(7)->toDateString(),
                'client_id' => $clientRecord2->id,
                'created_by_user_id' => $staff1->id,
                'staff_ids' => [$staff1->id],
            ],
            [
                'title' => 'Landing Page Build',
                'description' => 'High-converting landing page with analytics and form integration.',
                'status' => 'Delivered',
                'priority' => 'medium',
                'due_date' => now()->subDays(3)->toDateString(),
                'client_id' => $clientRecord3->id,
                'created_by_user_id' => $staff2->id,
                'staff_ids' => [$staff2->id],
            ],
            [
                'title' => 'Maintenance Retainer',
                'description' => 'Ongoing updates and small fixes under a monthly retainer.',
                'status' => 'Draft',
                'priority' => 'low',
                'due_date' => null,
                'client_id' => $clientRecord1->id,
                'created_by_user_id' => $owner->id,
                'staff_ids' => [$staff1->id],
            ],
            [
                'title' => 'Marketing Assets',
                'description' => 'Social graphics pack and ad creative templates.',
                'status' => 'Completed',
                'priority' => 'low',
                'due_date' => now()->subDays(20)->toDateString(),
                'client_id' => $clientRecord2->id,
                'created_by_user_id' => $owner->id,
                'staff_ids' => [$staff2->id],
            ],
        ];

        $createdProjects = [];

        foreach ($projects as $data) {
            $staffIds = $data['staff_ids'];
            unset($data['staff_ids']);

            $project = Project::updateOrCreate([
                'organization_id' => $organization->id,
                'title' => $data['title'],
            ], [
                ...$data,
            ]);

            $project->staff()->syncWithPivotValues($staffIds, [
                'organization_id' => $organization->id,
            ]);

            $createdProjects[] = $project;

            $primaryAssignee = $staffIds[0] ?? null;
            $secondaryAssignee = $staffIds[1] ?? $primaryAssignee;

            $doneIfCompleted = in_array($project->status, ['Delivered', 'Completed', 'Archived'], true) ? 'Done' : 'Todo';

            $taskTemplates = [
                [
                    'title' => 'Kickoff & requirements',
                    'status' => 'Done',
                    'assigned_to_user_id' => $primaryAssignee,
                    'due_date' => now()->subDays(5)->toDateString(),
                ],
                [
                    'title' => 'Create initial draft',
                    'status' => in_array($project->status, ['Delivered', 'Completed', 'Archived'], true) ? 'Done' : 'In Progress',
                    'assigned_to_user_id' => $secondaryAssignee,
                    'due_date' => now()->addDays(2)->toDateString(),
                ],
                [
                    'title' => 'Client review & feedback',
                    'status' => $project->status === 'Waiting on Client' ? 'Blocked' : 'Todo',
                    'assigned_to_user_id' => null,
                    'due_date' => now()->addDays(5)->toDateString(),
                ],
                [
                    'title' => 'Final delivery package',
                    'status' => $doneIfCompleted,
                    'assigned_to_user_id' => $primaryAssignee,
                    'due_date' => $project->due_date?->toDateString(),
                ],
            ];

            foreach ($taskTemplates as $task) {
                $seededTask = Task::withTrashed()->updateOrCreate([
                    'organization_id' => $organization->id,
                    'project_id' => $project->id,
                    'title' => $task['title'],
                ], [
                    ...$task,
                    'created_by_user_id' => $project->created_by_user_id,
                ]);

                $seededTask->restore();
            }

            $clientUserId = Client::query()
                ->withoutGlobalScopes()
                ->where('id', $project->client_id)
                ->value('user_id');

            $seedFiles = [
                [
                    'uploaded_by_user_id' => $primaryAssignee,
                    'uploader_type' => 'staff',
                    'file_type' => 'Deliverable',
                    'original_name' => "{$project->title} - Deliverable.pdf",
                    'path' => "project-files/demo/{$project->id}-deliverable.pdf",
                    'mime_type' => 'application/pdf',
                    'size' => 245_760,
                ],
                [
                    'uploaded_by_user_id' => $clientUserId,
                    'uploader_type' => 'client',
                    'file_type' => 'Client Upload',
                    'original_name' => "{$project->title} - Client Upload.zip",
                    'path' => "project-files/demo/{$project->id}-client-upload.zip",
                    'mime_type' => 'application/zip',
                    'size' => 1_048_576,
                ],
            ];

            foreach ($seedFiles as $file) {
                ProjectFile::updateOrCreate([
                    'organization_id' => $organization->id,
                    'project_id' => $project->id,
                    'path' => $file['path'],
                ], [
                    ...$file,
                ]);
            }

            $commentTemplates = [
                [
                    'author_user_id' => $primaryAssignee ?? $owner->id,
                    'author_type' => 'staff',
                    'body' => "Kickoff complete — posting updates and deliverables here for {$project->title}.",
                ],
                [
                    'author_user_id' => $clientUserId,
                    'author_type' => 'client',
                    'body' => 'Thanks! I’ll review and share feedback here.',
                ],
                [
                    'author_user_id' => $secondaryAssignee ?? $primaryAssignee ?? $owner->id,
                    'author_type' => 'staff',
                    'body' => 'Sounds good — tagging key milestones as we progress.',
                ],
            ];

            foreach ($commentTemplates as $comment) {
                if (! $comment['author_user_id']) {
                    continue;
                }

                Comment::updateOrCreate([
                    'organization_id' => $organization->id,
                    'project_id' => $project->id,
                    'author_user_id' => $comment['author_user_id'],
                    'body' => $comment['body'],
                ], [
                    ...$comment,
                ]);
            }
        }

        $invoiceTemplates = [
            [
                'number' => 'INV-00001',
                'status' => 'Draft',
                'issued_at' => null,
                'due_at' => now()->addDays(14)->toDateString(),
                'client_id' => $clientRecord1->id,
                'project_id' => $createdProjects[0]->id ?? null,
                'notes' => 'Draft invoice ready for review.',
                'items' => [
                    ['description' => 'Design milestone 1', 'quantity' => 1, 'unit_price' => 750_00],
                    ['description' => 'Development sprint', 'quantity' => 10, 'unit_price' => 120_00],
                ],
            ],
            [
                'number' => 'INV-00002',
                'status' => 'Sent',
                'issued_at' => now()->subDays(5)->toDateString(),
                'due_at' => now()->addDays(9)->toDateString(),
                'client_id' => $clientRecord2->id,
                'project_id' => $createdProjects[1]->id ?? null,
                'notes' => 'Thanks! Please pay by the due date.',
                'items' => [
                    ['description' => 'Brand identity kit', 'quantity' => 1, 'unit_price' => 1_250_00],
                    ['description' => 'Print-ready export pack', 'quantity' => 1, 'unit_price' => 150_00],
                ],
            ],
            [
                'number' => 'INV-00003',
                'status' => 'Paid',
                'issued_at' => now()->subDays(20)->toDateString(),
                'due_at' => now()->subDays(10)->toDateString(),
                'client_id' => $clientRecord3->id,
                'project_id' => $createdProjects[2]->id ?? null,
                'notes' => 'Payment received — thank you!',
                'items' => [
                    ['description' => 'Landing page build', 'quantity' => 1, 'unit_price' => 900_00],
                    ['description' => 'Analytics setup', 'quantity' => 1, 'unit_price' => 100_00],
                ],
            ],
        ];

        foreach ($invoiceTemplates as $data) {
            $items = $data['items'];
            unset($data['items']);

            $invoice = Invoice::updateOrCreate([
                'organization_id' => $organization->id,
                'number' => $data['number'],
            ], [
                ...$data,
                'created_by_user_id' => $owner->id,
                'subtotal' => 0,
                'total' => 0,
            ]);

            InvoiceItem::query()->where('invoice_id', $invoice->id)->delete();

            $subtotal = 0;

            foreach ($items as $item) {
                $lineTotal = (int) $item['quantity'] * (int) $item['unit_price'];
                $subtotal += $lineTotal;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (int) $item['unit_price'],
                    'line_total' => $lineTotal,
                ]);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
            ]);
        }

        $firstProject = $createdProjects[0] ?? null;

        if ($firstProject) {
            ActivityLogger::log($organization->id, $owner, $clientRecord1, 'client.created', [
                'client_id' => $clientRecord1->id,
                'name' => $clientRecord1->name,
                'email' => $clientRecord1->email,
            ]);

            ActivityLogger::log($organization->id, $owner, $firstProject, 'project.created', [
                'project_id' => $firstProject->id,
                'title' => $firstProject->title,
                'status' => $firstProject->status,
                'priority' => $firstProject->priority,
                'due_date' => $firstProject->due_date?->toDateString(),
                'client_id' => $firstProject->client_id,
                'client_name' => $clientRecord1->name,
            ]);

            $sampleTask = Task::query()
                ->where('project_id', $firstProject->id)
                ->orderBy('id')
                ->first();

            if ($sampleTask) {
                ActivityLogger::log($organization->id, $staff1, $sampleTask, 'task.created', [
                    'task_id' => $sampleTask->id,
                    'title' => $sampleTask->title,
                    'status' => $sampleTask->status,
                    'project_id' => $firstProject->id,
                    'project_title' => $firstProject->title,
                ]);
            }

            $sampleClientFile = ProjectFile::query()
                ->where('project_id', $firstProject->id)
                ->where('uploader_type', 'client')
                ->orderByDesc('id')
                ->first();

            if ($sampleClientFile) {
                ActivityLogger::log($organization->id, $client1, $sampleClientFile, 'file.uploaded', [
                    'file_id' => $sampleClientFile->id,
                    'project_id' => $firstProject->id,
                    'project_title' => $firstProject->title,
                    'uploader_type' => $sampleClientFile->uploader_type,
                    'file_type' => $sampleClientFile->file_type,
                    'original_name' => $sampleClientFile->original_name,
                ]);

                $owner->notify(new ClientUploadedFileNotification($firstProject, $sampleClientFile));
                $staff2->notify(new ClientUploadedFileNotification($firstProject, $sampleClientFile));
            }

            $sampleClientComment = Comment::query()
                ->where('project_id', $firstProject->id)
                ->where('author_type', 'client')
                ->orderByDesc('id')
                ->first();

            if ($sampleClientComment) {
                ActivityLogger::log($organization->id, $client1, $sampleClientComment, 'comment.posted', [
                    'comment_id' => $sampleClientComment->id,
                    'project_id' => $firstProject->id,
                    'project_title' => $firstProject->title,
                    'author_type' => $sampleClientComment->author_type,
                ]);

                $owner->notify(new ProjectCommentedNotification($firstProject, $sampleClientComment));
                $staff1->notify(new ProjectCommentedNotification($firstProject, $sampleClientComment));
            }
        }

        $sampleInvoice = Invoice::query()
            ->where('organization_id', $organization->id)
            ->orderByDesc('id')
            ->first();

        if ($sampleInvoice) {
            ActivityLogger::log($organization->id, $owner, $sampleInvoice, 'invoice.created', [
                'invoice_id' => $sampleInvoice->id,
                'number' => $sampleInvoice->number,
                'status' => $sampleInvoice->status,
                'client_id' => $sampleInvoice->client_id,
                'total' => $sampleInvoice->total,
            ]);
        }

        $sentInvoice = Invoice::query()
            ->where('organization_id', $organization->id)
            ->where('status', 'Sent')
            ->orderByDesc('id')
            ->first();

        if ($sentInvoice) {
            $sentInvoice->loadMissing(['client.user']);

            if ($sentInvoice->client?->user) {
                $sentInvoice->client->user->notify(new InvoiceSentNotification($sentInvoice));
            }
        }
    }
}
