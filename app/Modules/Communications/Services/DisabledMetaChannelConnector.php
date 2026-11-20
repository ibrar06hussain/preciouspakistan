<?php

namespace App\Modules\Communications\Services;

use App\Modules\Communications\Contracts\CommunicationChannelConnector;
use RuntimeException;

/**
 * Safe default connector for optional official Meta WhatsApp Cloud API / Messenger / Instagram inbox.
 * Strictly disabled unless officially authorized & configured; NEVER fabricates API connectivity.
 */
final class DisabledMetaChannelConnector implements CommunicationChannelConnector
{
    public function channelName(): string
    {
        return 'meta_official_channels';
    }

    public function isAuthorizedAndEnabled(): bool
    {
        return false;
    }

    public function sendMessage(string $recipientId, string $message, array $payload = []): void
    {
        throw new RuntimeException(
            'Official Meta WhatsApp Cloud API / Messenger / Instagram integration is disabled until authorized credentials and webhook verification are configured.'
        );
    }
}
