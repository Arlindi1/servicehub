<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Project;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $search = $request->string('search')->trim()->value();

        $clients = Client::query()
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('App/Clients/Index', [
            'filters' => [
                'search' => $search,
            ],
            'can' => [
                'create' => $request->user()->can('create', Client::class),
            ],
            'clients' => $clients->through(fn (Client $client) => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'created_at' => $client->created_at?->toDateString(),
                'can' => [
                    'view' => $request->user()->can('view', $client),
                    'update' => $request->user()->can('update', $client),
                    'delete' => $request->user()->can('delete', $client),
                ],
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('App/Clients/Create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $client = Client::create([
            ...$request->validated(),
            'organization_id' => $request->user()->organization_id,
        ]);

        ActivityLogger::log(
            $request->user()->organization_id,
            $request->user(),
            $client,
            'client.created',
            [
                'client_id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ]
        );

        return redirect()
            ->to(route('app.clients.show', $client, absolute: false))
            ->with('success', 'Client created.');
    }

    public function show(Request $request, Client $client): Response
    {
        $this->authorize('view', $client);

        $projectsCount = $client->projects()->count();
        $latestProjects = $client->projects()
            ->select(['id', 'title', 'status', 'priority', 'due_date'])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status,
                'priority' => $project->priority,
                'due_date' => $project->due_date?->toDateString(),
            ])
            ->values();

        $invoicesCount = $client->invoices()->count();
        $latestInvoices = $client->invoices()
            ->select(['id', 'number', 'status', 'total', 'due_at'])
            ->orderByDesc('id')
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'total' => $invoice->total,
                'due_at' => $invoice->due_at?->toDateString(),
            ])
            ->values();

        return Inertia::render('App/Clients/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'notes' => $client->notes,
                'created_at' => $client->created_at?->toDateTimeString(),
            ],
            'can' => [
                'update' => $request->user()->can('update', $client),
                'delete' => $request->user()->can('delete', $client),
            ],
            'projectsCount' => $projectsCount,
            'invoicesCount' => $invoicesCount,
            'latestProjects' => $latestProjects,
            'latestInvoices' => $latestInvoices,
        ]);
    }

    public function edit(Request $request, Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('App/Clients/Edit', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'phone' => $client->phone,
                'notes' => $client->notes,
            ],
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $before = $client->only(['name', 'email', 'phone', 'notes']);

        $client->update($request->validated());

        ActivityLogger::log(
            $request->user()->organization_id,
            $request->user(),
            $client,
            'client.updated',
            [
                'client_id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'before' => $before,
                'after' => $client->only(['name', 'email', 'phone', 'notes']),
            ]
        );

        return redirect()
            ->to(route('app.clients.show', $client, absolute: false))
            ->with('success', 'Client updated.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()
            ->to(route('app.clients.index', absolute: false))
            ->with('success', 'Client deleted.');
    }
}
