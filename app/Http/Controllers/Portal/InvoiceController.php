<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Invoice::class);

        $search = $request->string('search')->trim()->value();
        $status = $request->string('status')->trim()->value();

        $status = in_array($status, Invoice::STATUSES, true) ? $status : null;

        $invoices = Invoice::query()
            ->with(['project:id,title'])
            ->when($search, fn ($query) => $query->where('number', 'like', "%{$search}%"))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Portal/Invoices/Index', [
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'reference' => [
                'statuses' => Invoice::STATUSES,
            ],
            'invoices' => $invoices->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at?->toDateString(),
                'due_at' => $invoice->due_at?->toDateString(),
                'total' => $invoice->total,
                'project' => $invoice->project
                    ? [
                        'id' => $invoice->project->id,
                        'title' => $invoice->project->title,
                    ]
                    : null,
            ]),
        ]);
    }

    public function show(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing([
            'client:id,name,email',
            'project:id,title',
            'items',
        ]);

        return Inertia::render('Portal/Invoices/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'issued_at' => $invoice->issued_at?->toDateString(),
                'due_at' => $invoice->due_at?->toDateString(),
                'subtotal' => $invoice->subtotal,
                'total' => $invoice->total,
                'notes' => $invoice->notes,
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
                'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                    'id' => $item->id,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                ])->values(),
            ],
        ]);
    }
}

