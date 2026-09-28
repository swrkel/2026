<?php

namespace Modules\PetroDirectNew\Reports;

use Illuminate\Support\Facades\Schema;
use Modules\PetroDirectNew\Reports\Contracts\PetroDirectNewReport;
use Modules\PetroDirectNew\Support\BusinessContext;

abstract class AbstractReport implements PetroDirectNewReport
{
    public function __construct(protected BusinessContext $context) {}
    abstract protected function modelClass(): string;
    protected function dateColumns(): array { return ['transaction_date','payment_date','collection_date','reading_date','transfer_date','created_at']; }

    public function rows(array $filters = [])
    {
        $modelClass = $this->modelClass();
        $model = new $modelClass;
        $query = $modelClass::query()->where('business_id', $this->context->requireBusiness());
        if (!empty($filters['location_id']) && Schema::hasColumn($model->getTable(), 'location_id')) {
            $query->where('location_id', (int) $filters['location_id']);
        }
        $dateColumn = null;
        foreach ($this->dateColumns() as $candidate) {
            if (Schema::hasColumn($model->getTable(), $candidate)) { $dateColumn = $candidate; break; }
        }
        if ($dateColumn && !empty($filters['date_from'])) $query->whereDate($dateColumn, '>=', $filters['date_from']);
        if ($dateColumn && !empty($filters['date_to'])) $query->whereDate($dateColumn, '<=', $filters['date_to']);
        return $query->latest('id')->limit(5000)->get();
    }
}
