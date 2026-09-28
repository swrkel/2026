<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class IntegrityCheck extends Model
{
    protected $table = 'stn_integrity_checks';
    protected $guarded = ['id'];
    protected $casts = ['findings'=>'array','checked_at'=>'datetime'];
}
