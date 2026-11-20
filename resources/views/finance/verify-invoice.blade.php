@extends('layouts.public')

@section('title', 'Invoice Verification — Precious Pakistan')

@section('content')
<section class="py-16 bg-slate-50 min-h-[65vh]">
    <div class="max-w-xl mx-auto px-4">
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                Authentic Precious Pakistan Record
            </div>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">
                Invoice {{ $invoice->invoice_number ?: 'Draft #'.$invoice->id }}
            </h1>
            <p class="mt-1 text-xs text-slate-500">
                Privacy notice: Public QR verification confirms document authenticity only and hides personal customer details.
            </p>

            <dl class="mt-6 divide-y divide-slate-100 text-sm">
                <div class="py-3 flex justify-between">
                    <dt class="text-slate-500">Document Type</dt>
                    <dd class="font-semibold text-slate-900 uppercase">{{ $invoice->invoice_type }}</dd>
                </div>
                <div class="py-3 flex justify-between">
                    <dt class="text-slate-500">Status</dt>
                    <dd class="font-semibold text-slate-900">{{ \App\Modules\Finance\Models\Invoice::STATUSES[$invoice->status] ?? $invoice->status }}</dd>
                </div>
                <div class="py-3 flex justify-between">
                    <dt class="text-slate-500">Issued At</dt>
                    <dd class="font-semibold text-slate-900">{{ $invoice->issued_at ? \App\Shared\Support\DisplayTimezone::format($invoice->issued_at) : 'Unissued Draft' }}</dd>
                </div>
                <div class="py-3 flex justify-between">
                    <dt class="text-slate-500">Total Amount</dt>
                    <dd class="font-semibold text-slate-900">{{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}</dd>
                </div>
                <div class="py-3 flex justify-between">
                    <dt class="text-slate-500">Balance Due</dt>
                    <dd class="font-semibold text-slate-900">{{ $invoice->currency }} {{ number_format((float) $invoice->balance_due, 2) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</section>
@endsection
