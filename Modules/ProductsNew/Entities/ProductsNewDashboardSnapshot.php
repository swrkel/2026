<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
class ProductsNewDashboardSnapshot extends Model { protected $table = 'products_new_dashboard_snapshots'; protected $guarded = ['id']; protected $casts = ['snapshot_payload' => 'array']; }
