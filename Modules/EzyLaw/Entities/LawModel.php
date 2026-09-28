<?php
namespace Modules\EzyLaw\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;

abstract class LawModel extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::addGlobalScope('ezylaw_business', function ($query): void {
            if (app()->runningInConsole() || !auth()->check()) return;
            try {
                $id = EzyLawTenantGuard::businessId();
                $query->where($query->getModel()->getTable().'.business_id', $id);
            } catch (\Throwable $e) {
                $query->whereRaw('1 = 0');
            }
        });
        static::creating(function ($model): void {
            if (empty($model->business_id) && auth()->check()) {
                $model->business_id = EzyLawTenantGuard::businessId();
            }
            if (empty($model->created_by) && auth()->check() && in_array('created_by', $model->getFillable(), true)) {
                $model->created_by = auth()->id();
            }
        });
    }
}
