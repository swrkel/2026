<?php
namespace Modules\POS\Entities;
use Illuminate\Database\Eloquent\Model;
class POSCashMovement extends Model { protected $table = 'pos_cash_movements'; protected $guarded = ['id']; }
