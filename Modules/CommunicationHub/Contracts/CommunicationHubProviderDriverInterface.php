<?php

namespace Modules\CommunicationHub\Contracts;

interface CommunicationHubProviderDriverInterface
{
    public function key(): string;

    public function channel(): string;

    public function label(): string;

    public function send(array $message, array $providerConfig = []): array;

    public function healthCheck(array $providerConfig = []): array;
}
