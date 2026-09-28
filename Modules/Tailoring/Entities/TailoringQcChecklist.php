<?php
namespace Modules\Tailoring\Entities;
use Illuminate\Database\Eloquent\Model;
class TailoringQcChecklist extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['checklist_items'=>'array','checked_items'=>'array','checked_at'=>'datetime','is_passed'=>'boolean'];
}
