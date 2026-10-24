<?php

namespace App\Http\Controllers;

use App\Models\Invoice;

class InvoiceController extends Controller
{
    public function show(Invoice $invoice)
    {
        $invoice->load('items', 'company', 'subscription.plan', 'payment');

        return view('subscriptions.invoice-show', compact('invoice'));
    }
}
