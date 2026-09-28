<?php

namespace Modules\CommunicationHub\Services\Providers\Drivers\Email;

use Modules\CommunicationHub\Services\Providers\Drivers\AbstractProviderDriver;

class MailgunEmailProvider extends AbstractProviderDriver
{
    public function key(): string { return 'email.mailgun'; }
    public function channel(): string { return 'email'; }
    public function label(): string { return 'Mailgun Email'; }
    public function send(array $message, array $providerConfig = []): array { return $this->simulatedSend($message, $providerConfig); }
}
