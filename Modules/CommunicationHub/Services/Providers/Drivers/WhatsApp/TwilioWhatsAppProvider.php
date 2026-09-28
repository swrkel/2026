<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\WhatsApp;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class TwilioWhatsAppProvider extends AbstractProviderDriver
{
    public function key(): string { return 'whatsapp.twilio'; }
    public function channel(): string { return 'whatsapp'; }
    public function label(): string { return 'Twilio WhatsApp'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
