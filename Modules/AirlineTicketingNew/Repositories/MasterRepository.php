<?php

namespace Modules\AirlineTicketingNew\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MasterRepository
{
    public function query(string $modelClass, int $businessId): Builder
    {
        return $modelClass::query()->where('business_id', $businessId);
    }

    public function create(string $modelClass, array $data): Model
    {
        return $modelClass::query()->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->fill($data)->save();
        return $model->refresh();
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }
}
