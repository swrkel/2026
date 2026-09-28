<?php
namespace Modules\LeadsNew\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LeadsNewImportBatch extends Model {

    protected $table = 'leads_new_import_batches';
    protected $guarded = ['id'];
    protected $casts = ['snapshot'=>'array','old_values'=>'array','new_values'=>'array','errors'=>'array','items'=>'array','quote_date'=>'date','valid_until'=>'date','expected_close_date'=>'date','converted_at'=>'datetime'];
}
