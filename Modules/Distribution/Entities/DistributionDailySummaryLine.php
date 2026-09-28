<?php
namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;

class DistributionDailySummaryLine extends Model
{
    protected $table = 'distribution_daily_summary_lines';

    protected $fillable = [
        'daily_summary_id', 'page_no', 'bill_id', 'customer_id',
        'gross_sale', 'discount', 'net_sale',
        'products_json', 'free_issues_json', 'row_number',
    ];

    // Remove the 'array' cast — we handle encoding/decoding manually
    // so the raw JSON string is stored and read back as a string.
    // The print blade and controller both handle json_decode themselves.
    protected $casts = [];

    public function summary()
    {
        return $this->belongsTo(DistributionDailySummary::class, 'daily_summary_id');
    }
}