<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Notifications\InvoiceSentNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();
        $clientId = $request->filled('client_id') ? (int) $request->input('client_id') : null;

        $status = in_array($status, Invoice::STATUSES, true) ? $status : null;

        $invoices = Invoice::query()
            ->with([
                'client:id,name',
                'project:id,title',
            ])
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('number', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($clientId, fn ($query) => $query->where('client_id', $clientId))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('App/Invoices/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
                'client_id' => $clientId,
            ],
            'reference' => [
                'statuses' => Invoice::STATUSES,
            ],
            'can' => [
                'create' => $request->user()->can('create', Invoice::class),
            ],
            'clients' => $clients,
            'invoices' => $invoices->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at?->toDateString(),
                'due_at' => $invoice->due_at?->toDateString(),
                'subtotal' => $invoice->subtotal,
                'total' => $invoice->total,
                'client' => $invoice->client
                    ? [
                        'id' => $invoice->client->id,
                        'name' => $invoice->client->name,
                    ]
                    : null,
                'project' => $invoice->project
                    ? [
                        'id' => $invoice->project->id,
                        'title' => $invoice->project->title,
                    ]
                    : null,
                'can' => [
                    'view' => $request->user()->can('view', $invoice),
                ],
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Invoice::class);

        $prefillClientId = $request->filled('client_id') ? (int) $request->input('client_id') : null;

        $organization = $request->user()->organization;
        $defaultDueAt = now()
            ->addDays($organization?->invoice_due_days_default ?? 14)
            ->toDateString();

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        if ($prefillClientId && ! $clients->contains('id', $prefillClientId)) {
            $prefillClientId = null;
        }

        $projects = Project::query()
            ->orderBy('title')
            ->get(['id', 'title', 'client_id']);

        return Inertia::render('App/Invoices/Create', [
            'reference' => [
                'statuses' => Invoice::STATUSES,
            ],
            'clients' => $clients,
            'projects' => $projects,
            'prefillClientId' => $prefillClientId,
            'defaultDueAt' => $defaultDueAt,
        ]);
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $organizationId = $request->user()->organization_id;
        $organization = $request->user()->organization;
        $invoicePrefix = $organization?->invoice_prefix ?: 'INV';

        $dueAt = $validated['due_at'] ?? null;
        if (! $dueAt) {
            $dueAt = now()
                ->addDays($organization?->invoice_due_days_default ?? 14)
                ->toDateString();
        }

        $invoice = null;

        DB::transaction(function () use ($request, $validated, $items, $organizationId, $invoicePrefix, $dueAt, &$invoice): void {
            $number = $this->generateInvoiceNumber($organizationId, $invoicePrefix);

            $invoice = Invoice::create([
                'organization_id' => $organizationId,
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'] ?? null,
                'number' => $number,
                'status' => 'Draft',
                'issued_at' => null,
                'due_at' => $dueAt,
                'subtotal' => 0,
                'total' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by_user_id' => $request->user()->id,
            ]);

            [$normalizedItems, $subtotal, $total] = $this->normalizeInvoiceItems($items);

            foreach ($normalizedItems as $item) {
                $invoice->items()->create($item);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        });

        $invoice->loadMissing(['client:id,name']);

        ActivityLogger::log(
            $organizationId,
            $request->user(),
            $invoice,
            'invoice.created',
            [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client?->name,
                'total' => $invoice->total,
            ]
        );

        return redirect()
            ->to(route('app.invoices.show', $invoice, absolute: false))
            ->with('success', 'Invoice created.');
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing([
            'client:id,name,email',
            'project:id,title',
            'createdBy:id,name',
            'items',
        ]);

        return Inertia::render('App/Invoices/Show', [
            'reference' => [
                'statuses' => Invoice::STATUSES,
            ],
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at?->toDateString(),
                'due_at' => $invoice->due_at?->toDateString(),
                'subtotal' => $invoice->subtotal,
                'total' => $invoice->total,
                'notes' => $invoice->notes,
                'created_at' => $invoice->created_at?->toDateTimeString(),
                'client' => $invoice->client
                    ? [
                        'id' => $invoice->client->id,
                        'name' => $invoice->client->name,
                        'email' => $invoice->client->email,
                    ]
                    : null,
                'project' => $invoice->project
                    ? [
                        'id' => $invoice->project->id,
                        'title' => $invoice->project->title,
                    ]
                    : null,
                'created_by' => $invoice->createdBy
                    ? [
                        'id' => $invoice->createdBy->id,
                        'name' => $invoice->createdBy->name,
                    ]
                    : null,
                'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])->values(),
            ],
            'can' => [
                'update' => $request->user()->can('update', $invoice),
                'markSent' => $request->user()->can('markSent', $invoice),
                'markPaid' => $request->user()->can('markPaid', $invoice),
                'void' => $request->user()->can('void', $invoice),
            ],
        ]);
    }

    public function edit(Request $request, Invoice $invoice): Response
    {
        $this->authorize('update', $invoice);

        $invoice->loadMissing(['items']);

        $clients = Client::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $projects = Project::query()
            ->orderBy('title')
            ->get(['id', 'title', 'client_id']);

        return Inertia::render('App/Invoices/Edit', [
            'reference' => [
                'statuses' => Invoice::STATUSES,
            ],
            'clients' => $clients,
            'projects' => $projects,
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'client_id' => $invoice->client_id,
                'project_id' => $invoice->project_id,
                'due_at' => $invoice->due_at?->toDateString(),
                'notes' => $invoice->notes,
                'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                ])->values(),
            ],
        ]);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        DB::transaction(function () use ($invoice, $validated, $items): void {
            $invoice->update([
                'client_id' => $validated['client_id'],
                'project_id' => $validated['project_id'] ?? null,
                'due_at' => $validated['due_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            $invoice->items()->delete();

            [$normalizedItems, $subtotal, $total] = $this->normalizeInvoiceItems($items);

            foreach ($normalizedItems as $item) {
                $invoice->items()->create($item);
            }

            $invoice->update([
                'subtotal' => $subtotal,
                'total' => $total,
            ]);
        });

        $invoice->loadMissing(['client:id,name']);

        ActivityLogger::log(
            $invoice->organization_id,
            $request->user(),
            $invoice,
            'invoice.updated',
            [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client?->name,
                'total' => $invoice->total,
            ]
        );

        return redirect()
            ->to(route('app.invoices.show', $invoice, absolute: false))
            ->with('success', 'Invoice updated.');
    }

    public function markSent(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('markSent', $invoice);

        $invoice->update([
            'status' => 'Sent',
            'issued_at' => $invoice->issued_at ?? now(),
        ]);

        $invoice->loadMissing(['client:id,name,user_id', 'client.user:id']);

        ActivityLogger::log(
            $invoice->organization_id,
            $request->user(),
            $invoice,
            'invoice.marked_sent',
            [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client?->name,
            ]
        );

        if ($invoice->client?->user) {
            $invoice->client->user->notify(new InvoiceSentNotification($invoice));
        }

        return back()->with('success', 'Invoice marked as sent.');
    }

    public function markPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('markPaid', $invoice);

        $invoice->update([
            'status' => 'Paid',
        ]);

        $invoice->loadMissing(['client:id,name']);

        ActivityLogger::log(
            $invoice->organization_id,
            $request->user(),
            $invoice,
            'invoice.marked_paid',
            [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client?->name,
                'total' => $invoice->total,
            ]
        );

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function void(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('void', $invoice);

        $invoice->update([
            'status' => 'Void',
        ]);

        $invoice->loadMissing(['client:id,name']);

        ActivityLogger::log(
            $invoice->organization_id,
            $request->user(),
            $invoice,
            'invoice.voided',
            [
                'invoice_id' => $invoice->id,
                'number' => $invoice->number,
                'client_id' => $invoice->client_id,
                'client_name' => $invoice->client?->name,
            ]
        );

        return back()->with('success', 'Invoice voided.');
    }

    /**
     * @param  array<int, array{description: string, quantity: int, unit_price: int}>  $items
     * @return array{0: array<int, array{description: string, quantity: int, unit_price: int, line_total: int}>, 1: int, 2: int}
     */
    private function normalizeInvoiceItems(array $items): array
    {
        $normalized = [];
        $subtotal = 0;

        foreach ($items as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (int) ($item['unit_price'] ?? 0);
            $lineTotal = $quantity * $unitPrice;

            $normalized[] = [
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineTotal;
        }

        $total = $subtotal;

        return [$normalized, $subtotal, $total];
    }

    private function generateInvoiceNumber(int $organizationId, string $prefix): string
    {
        $prefix = trim(strtoupper($prefix)) ?: 'INV';

        $next = 1;

        $last = Invoice::withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->where('number', 'like', $prefix.'-%')
            ->orderByDesc('id')
            ->value('number');

        $pattern = '/^'.preg_quote($prefix, '/').'-(\d+)/';

        if (is_string($last) && preg_match($pattern, $last, $matches)) {
            $next = max(1, (int) $matches[1] + 1);
        }

        for ($i = 0; $i < 1000; $i++) {
            $number = $prefix.'-'.str_pad((string) ($next + $i), 5, '0', STR_PAD_LEFT);

            if (! Invoice::withoutGlobalScopes()->where('organization_id', $organizationId)->where('number', $number)->exists()) {
                return $number;
            }
        }

        throw new \RuntimeException('Unable to generate unique invoice number.');
    }
}
