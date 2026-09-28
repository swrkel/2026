<?php
namespace Modules\Distribution\Entities;

use Modules\Distribution\Entities\DistributionCategory as Category;
use Illuminate\Database\Eloquent\Model;

class Distribution_discount extends Model
{
    protected $table = "distribution_discounts";

    protected $fillable = [
        'business_id', 'date_time', 'category_id', 'sub_category_id',
    ];

    public function products()
    {
        return $this->hasMany(Distribution_discount_product::class, 'discount_id')->with('product');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'sub_category_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Unit::class);
    }

}
