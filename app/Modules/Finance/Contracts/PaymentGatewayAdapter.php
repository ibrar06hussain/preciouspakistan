<?php

namespace App\Modules\Finance\Contracts;

use App\Modules\Finance\Models\Invoice;

interface PaymentGatewayAdapter
{
    public function gatewayName(): string;

    public function isConfigured(): bool;

    /**
     * @return array{status: string, redirect_url: ?string, message: string}
     */
    public function createCheckoutSession(Invoice $invoice): array;
}
