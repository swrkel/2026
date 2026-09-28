<?php

namespace Modules\StockTransferNew\Services\GoLive;

class TrainingGuideService
{
    public function sections(): array
    {
        return [
            'Daily User Flow' => [
                'Open Stock Transfer-New Command Center.',
                'Create transfer request with source/destination business, location, and store.',
                'Add products by using the Products module lookup or barcode/SKU scan.',
                'Submit for approval and wait for approval status.',
                'Dispatch approved transfer and confirm stock movement.',
                'Receive goods at destination store and record short/excess/damage quantities.',
            ],
            'Manager Flow' => [
                'Review pending approvals from Command Center.',
                'Check stock availability and variance warnings.',
                'Approve, reject, or return for correction with remarks.',
                'Review audit trail before final closure.',
            ],
            'Reports Flow' => [
                'Use Product-wise, Location-wise, Store-wise, Vehicle-wise, User-wise, Monthly Trend, and Exception reports.',
                'Export CSV only after confirming tenant, business, location, and date range filters.',
            ],
        ];
    }
}
