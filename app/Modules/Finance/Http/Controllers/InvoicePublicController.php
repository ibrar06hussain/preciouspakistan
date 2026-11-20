<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\Invoice;
use App\Modules\Finance\Services\InvoicePdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class InvoicePublicController extends Controller
{
    public function __construct(
        private readonly InvoicePdfService $pdfService,
    ) {}

    /**
     * Public QR verification endpoint.
     * Deliberately displays ONLY non-sensitive authenticity facts (invoice number, type, status, issue date, currency, total).
     * NEVER exposes customer name, email, phone, address, or internal notes.
     */
    public function verify(string $token): View
    {
        $invoice = Invoice::query()
            ->where('verification_token', $token)
            ->firstOrFail();

        return view('finance.verify-invoice', [
            'invoice' => $invoice,
        ]);
    }

    public function downloadPdf(Request $request, Invoice $invoice): Response
    {
        Gate::forUser($request->user())->authorize('view', $invoice);

        return $this->pdfService->downloadResponse($invoice);
    }
}
