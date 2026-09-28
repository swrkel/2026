<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\Email;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class SmtpEmailProvider extends AbstractProviderDriver
{
    public function key(): string { return 'email.smtp'; }
    public function channel(): string { return 'email'; }
    public function label(): string { return 'SMTP Email'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
