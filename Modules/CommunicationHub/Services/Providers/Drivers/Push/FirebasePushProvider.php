<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\Push;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class FirebasePushProvider extends AbstractProviderDriver
{
    public function key(): string { return 'push.firebase'; }
    public function channel(): string { return 'push'; }
    public function label(): string { return 'Firebase Push'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
