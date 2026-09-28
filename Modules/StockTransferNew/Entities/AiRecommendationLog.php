<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class AiRecommendationLog extends Model
{
    protected $table = 'stn_ai_recommendation_logs';

    protected $fillable = [
        'business_id','module_area','reference_type','reference_id','action','before_payload','after_payload',
        'performed_by','ip_address','user_agent','remarks'
    ];
}
