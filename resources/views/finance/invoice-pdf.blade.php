<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number ?: 'DRAFT INVOICE' }} — Precious Pakistan</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; margin: 0; padding: 24px; }
        .header { width: 100%; border-bottom: 2px solid #047857; padding-bottom: 14px; margin-bottom: 18px; }
        .brand { font-size: 20px; font-weight: bold; color: #065f46; }
        .meta { font-size: 10px; color: #475569; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.items th { background: #f1f5f9; text-align: left; padding: 7px 8px; border-bottom: 1px solid #cbd5e1; font-size: 10px; text-transform: uppercase; }
        table.items td { padding: 7px 8px; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .totals { width: 45%; margin-left: auto; margin-top: 14px; border-collapse: collapse; }
        .totals td { padding: 4px 6px; }
        .totals tr.grand td { border-top: 2px solid #0f172a; font-weight: bold; font-size: 13px; padding-top: 8px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; background: #ecfdf5; color: #065f46; }
        .footer { margin-top: 24px; border-top: 1px solid #cbd5e1; padding-top: 12px; font-size: 10px; color: #475569; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="brand">Precious Pakistan</div>
                <div class="meta">Company-Operated Pakistan Tourism Platform · Asia/Karachi (PKT)</div>
                <div class="meta">bookings@preciouspakistan.com · https://preciouspakistan.com</div>
            </td>
            <td class="right">
                <div style="font-size: 16px; font-weight: bold;">{{ strtoupper($invoice->invoice_type) }} INVOICE</div>
                <div style="font-size: 12px; font-weight: bold; color: #047857;">{{ $invoice->invoice_number ?: 'DRAFT (UNISSUED)' }}</div>
                <div class="meta">Status: <span class="badge">{{ strtoupper($invoice->status) }}</span></div>
                @if($invoice->issued_at)
                    <div class="meta">Issued: {{ \App\Shared\Support\DisplayTimezone::format($invoice->issued_at) }}</div>
                @endif
                @if($invoice->due_date)
                    <div class="meta">Due Date: {{ $invoice->due_date->format('d M Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-bottom: 14px;">
        <tr>
            <td style="width: 55%; vertical-align: top;">
                <div style="font-weight: bold; margin-bottom: 4px;">Billed To</div>
                <div>{{ $invoice->customer_name }}</div>
                <div class="meta">{{ $invoice->customer_email }}</div>
                @if($invoice->customer_phone)<div class="meta">{{ $invoice->customer_phone }}</div>@endif
            </td>
            <td style="width: 45%; vertical-align: top;" class="right">
                @if($invoice->booking)
                    <div><strong>Booking Ref:</strong> {{ $invoice->booking->booking_reference }}</div>
                    <div class="meta">Tour: {{ $invoice->booking->tour?->title }}</div>
                @endif
                <div><strong>Currency:</strong> {{ $invoice->currency }}</div>
                @if($invoice->fx_rate_used)
                    <div class="meta">FX Snapshot: 1 USD = {{ $invoice->fx_rate_used }} PKR ({{ $invoice->fx_source }})</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty / Unit</th>
                <th class="right">Unit Price</th>
                <th class="right">Amount ({{ $invoice->currency }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->label }}</td>
                    <td class="right">{{ $item->quantity }} {{ $item->charge_unit }}</td>
                    <td class="right">{{ $item->is_included ? 'INCLUDED' : number_format((float) $item->unit_price, 2) }}</td>
                    <td class="right">{{ $item->is_included ? 'INCLUDED (0.00)' : number_format((float) $item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td></tr>
        @if((float) $invoice->discount_total > 0)
            <tr><td>Discount</td><td class="right">-{{ $invoice->currency }} {{ number_format((float) $invoice->discount_total, 2) }}</td></tr>
        @endif
        @if((float) $invoice->tax_total > 0)
            <tr><td>Tax ({{ $invoice->tax_rate_percent }}%)</td><td class="right">{{ $invoice->currency }} {{ number_format((float) $invoice->tax_total, 2) }}</td></tr>
        @endif
        @if((float) $invoice->credit_note_total > 0)
            <tr><td>Credit Notes</td><td class="right">-{{ $invoice->currency }} {{ number_format((float) $invoice->credit_note_total, 2) }}</td></tr>
        @endif
        <tr><td>Amount Paid</td><td class="right">{{ $invoice->currency }} {{ number_format((float) $invoice->amount_paid, 2) }}</td></tr>
        <tr class="grand"><td>Balance Due</td><td class="right">{{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</td></tr>
    </table>

    <table class="footer" style="width: 100%;">
        <tr>
            <td style="width: 72%; vertical-align: top;">
                <div><strong>Payment Instructions</strong></div>
                <div>{{ $invoice->payment_instructions }}</div>
                <div style="margin-top: 6px;">Issued invoices are immutable financial records. Adjustments are handled strictly via Credit Notes.</div>
            </td>
            <td style="width: 28%; text-align: right; vertical-align: top;">
                <img src="{{ $qrDataUri }}" alt="Invoice QR Verification" style="width: 84px; height: 84px;">
                <div style="font-size: 9px; margin-top: 4px;">Scan to Verify Authenticity</div>
            </td>
        </tr>
    </table>
</body>
</html>
