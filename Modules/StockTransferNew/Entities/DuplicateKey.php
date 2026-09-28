<?php
namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class DuplicateKey extends Model
{
    protected $table = 'stn_duplicate_keys';
    protected $guarded = ['id'];
    protected $casts = ['payload_hash'=>'string','expires_at'=>'datetime','meta'=>'array'];
}
