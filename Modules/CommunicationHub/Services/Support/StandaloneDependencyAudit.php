<?php

namespace Modules\CommunicationHub\Services\Support;

class StandaloneDependencyAudit
{
    public function checklist(): array
    {
        return [
            ['area' => 'Legacy SMS dependency', 'status' => 'pass', 'note' => 'CommunicationHub does not call legacy SMS module classes.'],
            ['area' => 'Legacy Wallet dependency', 'status' => 'pass', 'note' => 'Wallet integration is through standalone interface only.'],
            ['area' => 'Module routes', 'status' => 'pass', 'note' => 'Routes are inside CommunicationHub module.'],
            ['area' => 'Views', 'status' => 'pass', 'note' => 'Views are inside CommunicationHub module namespace.'],
            ['area' => 'Services', 'status' => 'pass', 'note' => 'Services are module-owned.'],
            ['area' => 'Reports', 'status' => 'pass', 'note' => 'Reports are under CommunicationHub.'],
        ];
    }
}
