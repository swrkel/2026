<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\WhatsApp;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class MetaWhatsAppProvider extends AbstractProviderDriver
{
    public function key(): string { return 'whatsapp.meta'; }
    public function channel(): string { return 'whatsapp'; }
    public function label(): string { return 'Meta WhatsApp Cloud API'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
