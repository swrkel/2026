<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewUnloadingChecklist extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_unloading_checklists';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'unloading_id',
    'trip_id',
    'checked_by',
    'checked_at',
    'checklist_json',
    'status',
    'remarks'
    ];
}
