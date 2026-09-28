<?php

namespace Modules\StockTransferNew\Entities;

use Illuminate\Database\Eloquent\Model;

class TransporterScorecardNote extends Model
{
    protected $table = 'stn_transporter_scorecard_notes';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'transporter_name', 'score_period',
        'score', 'note', 'created_by', 'updated_by',
    ];
}
