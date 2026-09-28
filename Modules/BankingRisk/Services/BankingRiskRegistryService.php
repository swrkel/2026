<?php

namespace Modules\BankingRisk\Services;

class BankingRiskRegistryService
{
    public function menu(): array
    {
        return [
            {
                        "slug": "dashboard",
                        "title": "Risk Dashboard",
                        "route": "bankingrisk.dashboard"
            },
            {
                        "slug": "credit-risk",
                        "title": "Credit Risk",
                        "route": "bankingrisk.credit_risk"
            },
            {
                        "slug": "liquidity-risk",
                        "title": "Liquidity Risk",
                        "route": "bankingrisk.liquidity_risk"
            },
            {
                        "slug": "operational-risk",
                        "title": "Operational Risk",
                        "route": "bankingrisk.operational_risk"
            },
            {
                        "slug": "market-risk",
                        "title": "Market Risk",
                        "route": "bankingrisk.market_risk"
            },
            {
                        "slug": "early-warning",
                        "title": "Early Warning",
                        "route": "bankingrisk.early_warning"
            },
            {
                        "slug": "stress-tests",
                        "title": "Stress Tests",
                        "route": "bankingrisk.stress_tests"
            },
            {
                        "slug": "reports",
                        "title": "Risk Reports",
                        "route": "bankingrisk.reports"
            },
            {
                        "slug": "settings",
                        "title": "Risk Settings",
                        "route": "bankingrisk.settings"
            }
];
    }
}
