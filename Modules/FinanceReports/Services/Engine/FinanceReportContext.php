<?php

namespace Modules\FinanceReports\Services\Engine;

use Illuminate\Http\Request;

class FinanceReportContext
{
    public int $business_id;
    public ?string $start_date;
    public ?string $end_date;
    public ?string $as_at;
    public $location_id;
    public bool $is_consolidated;
    public ?int $financial_year_id;

    public function __construct(int $business_id, ?string $start_date = null, ?string $end_date = null, $location_id = null, ?string $as_at = null, ?int $financial_year_id = null)
    {
        $this->business_id = $business_id;
        $this->start_date = $start_date ?: now()->startOfMonth()->format('Y-m-d');
        $this->end_date = $end_date ?: now()->format('Y-m-d');
        $this->as_at = $as_at ?: $this->end_date;
        $this->location_id = empty($location_id) || $location_id === 'all' ? null : $location_id;
        $this->is_consolidated = empty($this->location_id);
        $this->financial_year_id = $financial_year_id;
    }

    public static function fromRequest(Request $request, int $business_id): self
    {
        return new self(
            $business_id,
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('location_id'),
            $request->input('as_at'),
            $request->filled('financial_year_id') ? (int) $request->input('financial_year_id') : null
        );
    }

    public function label(): string
    {
        return $this->is_consolidated ? 'Consolidated - All Branches' : 'Branch / Location ID: ' . $this->location_id;
    }
}
