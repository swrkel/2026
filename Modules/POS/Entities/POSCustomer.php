<?php
namespace Modules\POS\Entities;
use Illuminate\Database\Eloquent\Model;
class POSCustomer extends Model
{
    protected $table = 'pos_customers';
    protected $guarded = [];
}
