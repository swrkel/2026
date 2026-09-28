<?php

namespace Modules\DigitalWallet\Services\Wallets;

use Modules\DigitalWallet\Entities\DigitalWalletType;

class DigitalWalletTypeService
{
    public function seedDefaults(): void
    {
        foreach ($this->defaultTypes() as $type) {
            DigitalWalletType::firstOrCreate(['type_code' => $type['type_code']], $type);
        }
    }

    public function defaultTypes(): array
    {
        return [
            ['type_code' => 'general', 'type_name' => 'General Wallet', 'channel' => null, 'is_default' => true, 'is_active' => true],
            ['type_code' => 'communication', 'type_name' => 'Communication Credits', 'channel' => 'communication', 'is_default' => true, 'is_active' => true],
            ['type_code' => 'sms', 'type_name' => 'SMS Credits', 'channel' => 'sms', 'is_default' => true, 'is_active' => true],
            ['type_code' => 'email', 'type_name' => 'Email Credits', 'channel' => 'email', 'is_default' => true, 'is_active' => true],
            ['type_code' => 'whatsapp', 'type_name' => 'WhatsApp Credits', 'channel' => 'whatsapp', 'is_default' => true, 'is_active' => true],
            ['type_code' => 'push', 'type_name' => 'Push Notification Credits', 'channel' => 'push', 'is_default' => true, 'is_active' => true],
            ['type_code' => 'api', 'type_name' => 'API Credits', 'channel' => 'api', 'is_default' => true, 'is_active' => true],
            ['type_code' => 'ai', 'type_name' => 'AI Service Credits', 'channel' => 'ai', 'is_default' => true, 'is_active' => true],
        ];
    }
}
