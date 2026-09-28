<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\Sms;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class CustomHttpSmsProvider extends AbstractProviderDriver
{
    public function key(): string { return 'sms.custom_http'; }
    public function channel(): string { return 'sms'; }
    public function label(): string { return 'Custom HTTP SMS'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
