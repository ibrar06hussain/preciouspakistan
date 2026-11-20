<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Response;

final class InvoicePdfService
{
    public function renderPdfBytes(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'payments', 'creditNotes', 'booking.tour']);

        $verificationUrl = route('invoices.verify', ['token' => $invoice->verification_token]);
        $qrOptions = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
            'scale' => 4,
            'quietzoneSize' => 1,
        ]);
        $qrDataUri = (new QRCode($qrOptions))->render($verificationUrl);

        $pdf = Pdf::loadView('finance.invoice-pdf', [
            'invoice' => $invoice,
            'verificationUrl' => $verificationUrl,
            'qrDataUri' => $qrDataUri,
        ])->setPaper('a4');

        return $pdf->output();
    }

    public function downloadResponse(Invoice $invoice): Response
    {
        $filename = ($invoice->invoice_number ?: 'DRAFT-INV-'.$invoice->id).'.pdf';

        return response($this->renderPdfBytes($invoice), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
