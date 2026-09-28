<?php

namespace Modules\CommunicationHub\Services\Providers;

use Modules\CommunicationHub\Contracts\CommunicationHubProviderDriverInterface;
use Modules\CommunicationHub\Services\Providers\Drivers\Email\MailgunEmailProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\Email\SmtpEmailProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\Push\FirebasePushProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\Sms\CustomHttpSmsProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\Sms\DialogSmsProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\Sms\TwilioSmsProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\WhatsApp\MetaWhatsAppProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\WhatsApp\TwilioWhatsAppProvider;
use Modules\CommunicationHub\Services\Providers\Drivers\WhatsApp\CustomHttpWhatsAppProvider;

class ProviderDriverRegistry
{
    /** @var array<string, CommunicationHubProviderDriverInterface> */
    protected array $drivers = [];

    public function __construct()
    {
        foreach ([
            new DialogSmsProvider(),
            new CustomHttpSmsProvider(),
            new TwilioSmsProvider(),
            new SmtpEmailProvider(),
            new MailgunEmailProvider(),
            new MetaWhatsAppProvider(),
            new TwilioWhatsAppProvider(),
            new CustomHttpWhatsAppProvider(),
            new FirebasePushProvider(),
        ] as $driver) {
            $this->register($driver);
        }
    }

    public function register(CommunicationHubProviderDriverInterface $driver): void
    {
        $this->drivers[$driver->key()] = $driver;
    }

    public function get(?string $key): ?CommunicationHubProviderDriverInterface
    {
        return $key ? ($this->drivers[$key] ?? null) : null;
    }

    public function all(): array
    {
        return $this->drivers;
    }

    public function byChannel(string $channel): array
    {
        return array_filter($this->drivers, fn (CommunicationHubProviderDriverInterface $driver) => $driver->channel() === $channel);
    }

    public function options(): array
    {
        $options = [];
        foreach ($this->drivers as $key => $driver) {
            $options[$driver->channel()][$key] = $driver->label();
        }
        return $options;
    }
}
