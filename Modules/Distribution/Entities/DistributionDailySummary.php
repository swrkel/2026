<?php
namespace Modules\Distribution\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Distribution\Entities\DistributionUser as User;
use Modules\Distribution\Entities\DistributionCategory as Category;
use Modules\Distribution\Entities\DistributionVehicles;
use Modules\Distribution\Entities\Distribution_routes;

class DistributionDailySummary extends Model
{
    protected $table = 'distribution_daily_summaries';

    protected $fillable = [
        'business_id','sheet_number','sales_rep_id','agent_name','date','route_id','vehicle_id',
        'product_category_id','distance_km','total_this_page','previous_page_gt','grand_total',
        'page_no','total_pages','product_list','status','created_by',
        'cash_deposited','cheque_deposited','credit_bills_bf','cheques_in_hand','credit_bills_in_hand','cash_in_hand',
        'calls_visited','productive_calls','calls_visited_bf','productive_calls_bf',
        'loading_sheet_no','loading_sheets','stock_status'
    ];

    protected $casts = [
        'product_list' => 'array',
        'stock_status' => 'array',
        'loading_sheets' => 'array',
        'date' => 'date',
    ];

    public function lines()
    {
        return $this->hasMany(DistributionDailySummaryLine::class, 'daily_summary_id');
    }

    public function salesRep()
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    public function route()
    {
        return $this->belongsTo(Distribution_routes::class, 'route_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(DistributionVehicles::class, 'vehicle_id');
    }

    public function productCategory()
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }
}
