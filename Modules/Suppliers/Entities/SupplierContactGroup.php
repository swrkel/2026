<?php

namespace Modules\Suppliers\Entities;

use Illuminate\Database\Eloquent\Model;

class SupplierContactGroup extends Model
{
    protected $table = 'contact_groups';
    protected $guarded = ['id'];

    public static function forSupplierDropdown(int $businessId, bool $prependNone = true)
    {
        $items = static::where('business_id', $businessId)
            ->where(function ($q) {
                $q->where('type', 'supplier')->orWhereNull('type');
            })
            ->pluck('name', 'id');

        return $prependNone ? $items->prepend(__('lang_v1.none'), '') : $items;
    }
}
