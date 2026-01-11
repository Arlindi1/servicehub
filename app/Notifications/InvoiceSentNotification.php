<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class InvoiceSentNotification extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice)
    {
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
        return [
            'title' => 'Invoice sent',
            'message' => "Invoice {$this->invoice->number} is ready.",
            'invoice_id' => $this->invoice->id,
            'number' => $this->invoice->number,
            'url' => route('portal.invoices.show', $this->invoice->id, absolute: false),
        ];
    }
}

