<?php

namespace App\Modules\Communications\Contracts;

interface CommunicationChannelConnector
{
    public function channelName(): string;

    public function isAuthorizedAndEnabled(): bool;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function sendMessage(string $recipientId, string $message, array $payload = []): void;
}
