<?php
namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewLoadingChecklist extends Model
{
    use SoftDeletes;
    protected $table = 'disnew_loading_checklists';
    protected $guarded = ['id'];
    protected $fillable = [
        'business_id',
    'loading_id',
    'trip_id',
    'checked_by',
    'checked_at',
    'checklist_json',
    'status',
    'remarks'
    ];
}
