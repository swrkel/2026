<?php

namespace Modules\LeadsNew\Services;

class LeadsNewStandaloneAuditRc4Service
{
    public function expectedOwnFolders(): array
    {
        return [
            'Config', 'Database', 'Http', 'Models', 'Services', 'Routes', 'Resources', 'Lang',
            'Requests', 'Policies', 'Jobs', 'Console', 'Imports', 'Exports', 'Notifications', 'Tests', 'Docs'
        ];
    }

    public function forbiddenReferences(): array
    {
        return [
            'Modules\\Leads\\',
            "module('Leads')",
            "view('leads::",
            "route('leads.",
        ];
    }
}
