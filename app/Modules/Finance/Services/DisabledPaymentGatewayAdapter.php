<?php

namespace App\Modules\Finance\Services;

use App\Modules\Finance\Contracts\PaymentGatewayAdapter;
use App\Modules\Finance\Models\Invoice;
use RuntimeException;

/**
 * Default safe adapter: online card/wallet gateways are explicitly disabled until authorized credentials are configured.
 * NEVER fakes or simulates a production payment success.
 */
final class DisabledPaymentGatewayAdapter implements PaymentGatewayAdapter
{
    public function gatewayName(): string
    {
        return 'unconfigured_online_gateway';
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function createCheckoutSession(Invoice $invoice): array
    {
        throw new RuntimeException(
            'Online payment gateway is disabled until official merchant credentials are configured. Please record manual bank transfer payment instead.'
        );
    }
}
