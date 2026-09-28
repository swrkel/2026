<?php
namespace Modules\Tailoring\Entities;
use Illuminate\Database\Eloquent\Model;
class TailoringProductionTimeline extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['started_at'=>'datetime','completed_at'=>'datetime','meta'=>'array'];
}
