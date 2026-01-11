@php
    $money = fn (int $cents): string => '$' . number_format($cents / 100, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; line-height: 1.5; }
        .muted { color: #6b7280; }
        .row { display: flex; justify-content: space-between; gap: 16px; }
        .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 13px; margin: 0 0 8px 0; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; border: 1px solid #e5e7eb; font-size: 11px; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        .table td { padding: 10px 0; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .right { text-align: right; }
        .totals { width: 240px; margin-left: auto; }
        .totals td { padding: 6px 0; }
        .totals .label { color: #6b7280; }
        .totals .grand { font-weight: 700; font-size: 13px; }
        .spacer { height: 14px; }
    </style>
</head>
<body>
    <div class="row">
        <div>
            <h1>{{ $invoice->organization->name ?? 'ServiceHub' }}</h1>
            <div class="muted">Invoice {{ $invoice->number }}</div>
        </div>

        <div class="right">
            <div class="badge">{{ $invoice->status }}</div>
            <div class="muted">
                @if($invoice->issued_at)
                    Issued: {{ $invoice->issued_at->toDateString() }}
                @endif
                @if($invoice->due_at)
                    <br />Due: {{ $invoice->due_at->toDateString() }}
                @endif
            </div>
        </div>
    </div>

    <div class="spacer"></div>

    <div class="row">
        <div class="card" style="flex: 1;">
            <h2>Bill to</h2>
            <div><strong>{{ $invoice->client->name ?? '-' }}</strong></div>
            <div class="muted">{{ $invoice->client->email ?? '' }}</div>
            @if(!empty($invoice->client?->phone))
                <div class="muted">{{ $invoice->client->phone }}</div>
            @endif
        </div>

        <div class="card" style="flex: 1;">
            <h2>Details</h2>
            <div class="muted">Project: <span style="color:#111827">{{ $invoice->project->title ?? '-' }}</span></div>
            <div class="muted">Created: <span style="color:#111827">{{ $invoice->created_at?->toDateString() }}</span></div>
        </div>
    </div>

    <div class="spacer"></div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 55%;">Description</th>
                <th class="right" style="width: 15%;">Qty</th>
                <th class="right" style="width: 15%;">Unit</th>
                <th class="right" style="width: 15%;">Line</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ $money((int) $item->unit_price) }}</td>
                    <td class="right">{{ $money((int) $item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="spacer"></div>

    <table class="totals">
        <tr>
            <td class="label">Subtotal</td>
            <td class="right">{{ $money((int) $invoice->subtotal) }}</td>
        </tr>
        <tr>
            <td class="label grand">Total</td>
            <td class="right grand">{{ $money((int) $invoice->total) }}</td>
        </tr>
    </table>

    @if(!empty($invoice->notes))
        <div class="spacer"></div>
        <div class="card">
            <h2>Notes</h2>
            <div style="white-space: pre-wrap;">{{ $invoice->notes }}</div>
        </div>
    @endif
</body>
</html>

