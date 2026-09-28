<?php
namespace Modules\StockTakingNew\Entities;
use Illuminate\Database\Eloquent\Model;
class StockTakeImportBatch extends Model
{
    protected $table='stk_import_batches'; protected $guarded=[]; protected $casts=['started_at'=>'datetime','completed_at'=>'datetime','summary'=>'array'];
}
