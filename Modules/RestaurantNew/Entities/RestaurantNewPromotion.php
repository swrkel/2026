<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewPromotion extends Model
{
    protected $table = 'restaurant_new_promotions';
    protected $guarded = ['id'];

    public function rules()
    {
        return $this->hasMany(RestaurantNewPromotionRule::class, 'promotion_id');
    }
}
