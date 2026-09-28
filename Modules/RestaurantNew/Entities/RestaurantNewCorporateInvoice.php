<?php

namespace Modules\RestaurantNew\Entities;

use Illuminate\Database\Eloquent\Model;

class RestaurantNewCorporateInvoice extends Model
{
    protected $table = 'restaurant_new_corporate_invoices';
    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo(RestaurantNewCorporateAccount::class, 'corporate_account_id');
    }
}
