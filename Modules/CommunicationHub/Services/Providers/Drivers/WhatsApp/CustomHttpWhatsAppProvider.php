<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\WhatsApp;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class CustomHttpWhatsAppProvider extends AbstractProviderDriver
{
    public function key(): string { return 'whatsapp.custom_http'; }
    public function channel(): string { return 'whatsapp'; }
    public function label(): string { return 'Custom HTTP WhatsApp Gateway'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
