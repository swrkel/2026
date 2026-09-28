<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewDuplicateReview extends Model
{
    protected $table = 'products_new_duplicate_reviews';
    protected $guarded = ['id'];
    protected $casts = ['matched_fields'=>'array','score'=>'decimal:2'];
}
