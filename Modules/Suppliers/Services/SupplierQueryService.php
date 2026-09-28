<?php

namespace Modules\Suppliers\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Suppliers\Entities\Supplier;

class SupplierQueryService
{
    public function baseQuery(?int $locationId = null): Builder
    {
        return Supplier::query()
            ->forCurrentBusiness()
            ->supplierOnly()
            ->forLocation($locationId);
    }

    public function listQuery(array $filters = []): Builder
    {
        $query = $this->baseQuery($filters['location_id'] ?? null);

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('supplier_business_name', 'like', "%{$search}%")
                    ->orWhere('contact_id', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $this->applyDateRange($query, $filters['date_range'] ?? null);

        return $query->orderByDesc('id');
    }

    private function applyDateRange(Builder $query, ?string $dateRange): void
    {
        if (empty($dateRange) || !str_contains($dateRange, '~')) {
            return;
        }

        [$startDate, $endDate] = array_map('trim', explode('~', $dateRange, 2));

        if ($startDate && $endDate) {
            $query->whereDate('created_at', '>=', $startDate)
                ->whereDate('created_at', '<=', $endDate);
        }
    }
}
