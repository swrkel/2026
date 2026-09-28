<?php

namespace Modules\CommunicationHub\Services\Chat;

use Modules\CommunicationHub\Support\TenantConnection;

class CommunicationHubChatService
{
    public function unreadInternalCount($businessId = null): int
    {
        if (!TenantConnection::hasTable('communication_hub_internal_messages')) { return 0; }
        $q = TenantConnection::db()->table('communication_hub_internal_messages')->where('status', 'unread');
        if ($businessId !== null && TenantConnection::hasColumn('communication_hub_internal_messages', 'business_id')) { $q->where('business_id', $businessId); }
        return (int) $q->count();
    }
}
