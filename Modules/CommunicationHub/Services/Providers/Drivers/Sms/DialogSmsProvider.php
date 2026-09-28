<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\Sms;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class DialogSmsProvider extends AbstractProviderDriver
{
    public function key(): string { return 'sms.dialog'; }
    public function channel(): string { return 'sms'; }
    public function label(): string { return 'Dialog SMS'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
