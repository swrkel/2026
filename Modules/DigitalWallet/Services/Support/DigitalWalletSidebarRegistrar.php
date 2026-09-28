<?php

namespace Modules\DigitalWallet\Services\Support;

class DigitalWalletSidebarRegistrar
{
    public static function sidebarPartial(): string
    {
        return 'digitalwallet::partials.sidebar';
    }

    public static function menu(): array
    {
        return config('digitalwallet_menu', []);
    }
}
