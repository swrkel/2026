<?php

namespace Modules\CommunicationHub\Services\Support;

class CommunicationHubSidebarRegistrar
{
    public static function sidebarPartial(): string
    {
        return 'communicationhub::partials.sidebar';
    }

    public static function menu(): array
    {
        return config('communicationhub_menu', []);
    }
}
