<?php
namespace Modules\BeautySaloons\Entities;

use Illuminate\Database\Eloquent\Model;

class BeautyProduct extends Model
{
    protected $table = 'beauty_salon_products';
    protected $guarded = ['id'];

    public function category() { return $this->belongsTo(BeautyProductCategory::class, 'category_id'); }
    public function brand() { return $this->belongsTo(BeautyProductBrand::class, 'brand_id'); }
}
