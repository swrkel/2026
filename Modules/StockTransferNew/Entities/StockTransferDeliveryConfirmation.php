<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class StockTransferDeliveryConfirmation extends Model
{
    protected $table = 'stn_delivery_confirmations';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'transfer_id', 'delivered_at',
        'received_by', 'receiver_mobile', 'condition_status', 'remarks',
        'signature_data', 'photo_reference', 'confirmed_by',
    ];

    protected $casts = ['delivered_at' => 'datetime'];

    public function damages()
    {
        return $this->hasMany(StockTransferDeliveryDamage::class, 'delivery_confirmation_id');
    }
}
