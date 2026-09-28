<?php

namespace Modules\Poultry\Entities\Shared;

/**
 * The ERP's shared products table.
 *
 * Feed rations, vaccines, medication, eggs by grade and live birds are all
 * ordinary products here. That is what lets the existing Purchase module buy
 * feed and the existing POS / Distribution modules sell eggs, with no sales or
 * purchasing code in this module at all.
 */
class Product extends SharedModel
{
    protected $sharedTableKey = 'products';
    protected $table = 'products';

    public function variations()
    {
        return $this->hasMany(Variation::class, 'product_id');
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Products in the categories configured as feed / medication.
     * Category ids are held in poultry_settings so an admin can point the
     * module at whatever category tree the tenant already uses.
     */
    public function scopeInCategories($query, array $categoryIds)
    {
        if (empty($categoryIds)) {
            return $query;
        }

        return $query->where(function ($q) use ($categoryIds) {
            $q->whereIn('category_id', $categoryIds)
              ->orWhereIn('sub_category_id', $categoryIds);
        });
    }

    /**
     * variation_id => label list for a select box, with the variation id as the
     * key because stock is held per variation, not per product.
     */
    public static function variationDropdown(array $categoryIds = [], $businessId = null)
    {
        $rows = static::query()
            ->forBusiness($businessId)
            ->notDeleted()
            ->inCategories($categoryIds)
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->orderBy('products.name')
            ->select([
                'variations.id as variation_id',
                'products.name as product_name',
                'variations.name as variation_name',
                'products.sku as sku',
            ])
            ->get();

        $list = [];
        foreach ($rows as $row) {
            $label = $row->product_name;
            if (! empty($row->variation_name) && $row->variation_name !== 'DUMMY') {
                $label .= ' - '.$row->variation_name;
            }
            if (! empty($row->sku)) {
                $label .= ' ('.$row->sku.')';
            }
            $list[$row->variation_id] = $label;
        }

        return $list;
    }
}
