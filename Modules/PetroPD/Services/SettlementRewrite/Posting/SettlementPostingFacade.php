<?php

namespace Modules\PetroPD\Services\SettlementRewrite\Posting;

class SettlementPostingFacade
{
    public static function post(array $context): array
    {
        $bridge = new SettlementLegacyPostingBridge(new SettlementPostingDuplicateGuard());

        return $bridge->post($context)->toArray();
    }
}
