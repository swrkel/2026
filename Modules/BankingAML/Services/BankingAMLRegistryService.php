<?php

namespace Modules\BankingAML\Services;

class BankingAMLRegistryService
{
    public function menu(): array
    {
        return [
            {
                        "slug": "dashboard",
                        "title": "AML Dashboard",
                        "route": "bankingaml.dashboard"
            },
            {
                        "slug": "kyc-reviews",
                        "title": "KYC Reviews",
                        "route": "bankingaml.kyc_reviews"
            },
            {
                        "slug": "screening",
                        "title": "Screening",
                        "route": "bankingaml.screening"
            },
            {
                        "slug": "cases",
                        "title": "AML Cases",
                        "route": "bankingaml.cases"
            },
            {
                        "slug": "alerts",
                        "title": "Compliance Alerts",
                        "route": "bankingaml.alerts"
            },
            {
                        "slug": "regulatory-reports",
                        "title": "Regulatory Reports",
                        "route": "bankingaml.regulatory_reports"
            },
            {
                        "slug": "audit",
                        "title": "Compliance Audit",
                        "route": "bankingaml.audit"
            },
            {
                        "slug": "settings",
                        "title": "AML Settings",
                        "route": "bankingaml.settings"
            }
];
    }
}
