<?php
namespace Modules\EggManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\EggManagement\Services\EggContext;

abstract class EggModel extends Model
{
    use SoftDeletes;
    public $guarded = [];

    public function getConnectionName()
    {
        return config('egg.connection') ?: parent::getConnectionName();
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!isset($model->business_id)) {
                $context = app(EggContext::class);
                $model->business_id = $context->businessId();
            }
        });
    }
}
