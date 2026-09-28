<?php
namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;

class POSDevice extends Model
{
    protected $table = 'pos_devices';
    protected $guarded = ['id'];
}
