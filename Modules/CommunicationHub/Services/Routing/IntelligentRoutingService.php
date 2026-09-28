<?php

namespace Modules\CommunicationHub\Services\Routing;

use Modules\CommunicationHub\Entities\CommunicationHubProvider;

class IntelligentRoutingService
{
    public function providersFor(string $channel, array $message = []): array
    {
        $query = CommunicationHubProvider::query()
            ->where('channel', $channel)
            ->where('is_active', 1)
            ->orderBy('priority')
            ->orderBy('id');

        if (! empty($message['country'])) {
            $country = strtoupper((string) $message['country']);
            $query->where(function ($q) use ($country) {
                $q->whereNull('country_code')->orWhere('country_code', $country);
            });
        }

        return $query->get()->filter(function (CommunicationHubProvider $provider) {
            return ! in_array($provider->health_status, ['disabled', 'blocked'], true);
        })->values()->all();
    }
}
