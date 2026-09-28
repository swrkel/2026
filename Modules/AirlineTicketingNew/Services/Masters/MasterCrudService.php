<?php

namespace Modules\AirlineTicketingNew\Services\Masters;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\AirlineTicketingNew\Repositories\MasterRepository;

class MasterCrudService
{
    public function __construct(private readonly MasterRepository $repository)
    {
    }

    public function list(string $modelClass, int $businessId, ?string $search = null)
    {
        $query = $this->repository->query($modelClass, $businessId)->latest('id');

        if ($search !== null && $search !== '') {
            $query->where(function ($inner) use ($search): void {
                $inner->where('name', 'like', '%' . $search . '%');
                if (in_array('code', $inner->getModel()->getFillable(), true)) {
                    $inner->orWhere('code', 'like', '%' . $search . '%');
                }
                if (in_array('iata_code', $inner->getModel()->getFillable(), true)) {
                    $inner->orWhere('iata_code', 'like', '%' . $search . '%');
                }
            });
        }

        return $query->paginate(25)->withQueryString();
    }

    public function create(string $modelClass, array $data): Model
    {
        return DB::transaction(fn () => $this->repository->create($modelClass, $data));
    }

    public function update(Model $model, array $data): Model
    {
        return DB::transaction(fn () => $this->repository->update($model, $data));
    }

    public function delete(Model $model): void
    {
        DB::transaction(fn () => $this->repository->delete($model));
    }
}
