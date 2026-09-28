<?php
namespace Modules\StockTransferNew\Entities;
use Illuminate\Database\Eloquent\Model;

class PerformanceMetric extends Model
{
    protected $table = 'stn_performance_metrics';
    protected $guarded = ['id'];
    public $timestamps = true;
}
