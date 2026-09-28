<?php

namespace Modules\Distribution\Entities;

use Modules\Distribution\Entities\DistributionProduct as Product;
use Modules\Distribution\Entities\DistributionCategory as Category;
use Modules\Distribution\Entities\DistributionUser as User;
use Illuminate\Database\Eloquent\Model;

class DistributionFreeIssue extends Model
{
    protected $table = 'distribution_free_issues';

    protected $fillable = [
    'business_id',
    'form_no',
    'date_time',
    'date_since',
    'date_till',
    'product_name',
    'product_category',
    'product_subcategory',
    'unit_id',
    'free_products',
    'qty_from',
    'qty_till',
    'free_qty',
    'qty_type',
    'is_free',
    'is_free_bottles',
    'status',
    'created_by',
    'updated_by', // Add this line
];
    protected $casts = [
        'free_products'       => 'array',
    ];

    public function addedBy()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_name');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'product_category');
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'product_subcategory');
    }

    public function unit()
    {
        return $this->belongsTo(\Modules\Distribution\Entities\Core\Unit::class, 'unit_id');
    }



    // In DistributionFreeIssue.php
public function createdBy()
{
    return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'created_by');
}

public function updatedBy()
{
    return $this->belongsTo(\Modules\Distribution\Entities\Core\User::class, 'updated_by');
}

}