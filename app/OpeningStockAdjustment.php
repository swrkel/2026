<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OpeningStockAdjustment extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'original_quantity' => 'float',
        'adjusted_quantity' => 'float',
        'quantity_difference' => 'float',
        'transaction_date' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function sub_category()
    {
        return $this->belongsTo(Category::class, 'sub_category_id');
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class);
    }

    public function created_by_user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
