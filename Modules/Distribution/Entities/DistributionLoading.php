<?php
// app/Models/DistributionLoading.php
namespace Modules\Distribution\Entities;

use Modules\Distribution\Entities\DistributionCategory as Category;
use Modules\Distribution\Entities\DistributionUser as User;
use Modules\Distribution\Entities\DistributionSalesAgent as SalesAgent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Distribution\Entities\DistributionLoadingLine;
use Modules\Distribution\Entities\DistributionVehicles;

class DistributionLoading extends Model
{
    use HasFactory;

    protected $table = 'distribution_loadings';

    protected $fillable = [
        'business_id', 'loading_no', 'date_time', 'sales_rep_id',
        'vehicle_id', 'product_category_id', 'product_sub_category_id',
        'total_sale_price', 'created_by', 'status'
    ];

    public function salesRep()
    {
        return $this->belongsTo(SalesAgent::class, 'sales_rep_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(DistributionVehicles::class, 'vehicle_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'product_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(DistributionLoadingLine::class, 'loading_id');
    }

    public function subcategory()
    {
         return $this->belongsTo(\Modules\Distribution\Entities\Core\Category::class, 'product_sub_category_id');
    }
}
