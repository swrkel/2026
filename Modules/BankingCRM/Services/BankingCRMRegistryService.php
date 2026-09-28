<?php

namespace Modules\BankingCRM\Services;

class BankingCRMRegistryService
{
    public function menu(): array
    {
        return [
            {
                        "slug": "dashboard",
                        "title": "CRM Dashboard",
                        "route": "bankingcrm.dashboard"
            },
            {
                        "slug": "customers",
                        "title": "Customer 360",
                        "route": "bankingcrm.customers"
            },
            {
                        "slug": "relationships",
                        "title": "Relationship Managers",
                        "route": "bankingcrm.relationships"
            },
            {
                        "slug": "interactions",
                        "title": "Interactions",
                        "route": "bankingcrm.interactions"
            },
            {
                        "slug": "service-requests",
                        "title": "Service Requests",
                        "route": "bankingcrm.service_requests"
            },
            {
                        "slug": "complaints",
                        "title": "Complaints",
                        "route": "bankingcrm.complaints"
            },
            {
                        "slug": "campaigns",
                        "title": "Campaigns",
                        "route": "bankingcrm.campaigns"
            },
            {
                        "slug": "reports",
                        "title": "CRM Reports",
                        "route": "bankingcrm.reports"
            },
            {
                        "slug": "settings",
                        "title": "CRM Settings",
                        "route": "bankingcrm.settings"
            }
];
    }
}
