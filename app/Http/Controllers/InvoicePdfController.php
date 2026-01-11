<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoicePdfController extends Controller
{
    public function download(Request $request, Invoice $invoice): StreamedResponse
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing([
            'organization:id,name',
            'client:id,name,email,phone',
            'project:id,title',
            'items',
        ]);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
        ]);

        $filename = 'Invoice-'.$invoice->number.'.pdf';

        return $pdf->download($filename);
    }
}

