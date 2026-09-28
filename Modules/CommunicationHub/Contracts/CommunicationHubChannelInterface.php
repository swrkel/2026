<?php

namespace Modules\CommunicationHub\Contracts;

interface CommunicationHubChannelInterface
{
    public function channel(): string;

    public function send(array $message, array $providerConfig = []): array;
}
