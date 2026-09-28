<?php
namespace Modules\DistributionNew\Reports;
use Illuminate\Support\Facades\DB;
class DisnewWarehouseStockReport { public function query(array $filters = []) { $q=DB::table('disnew_warehouse_stocks'); if(!empty($filters['business_id'])) $q->where('business_id',$filters['business_id']); if(!empty($filters['date_from'])) $q->whereDate('created_at','>=',$filters['date_from']); if(!empty($filters['date_to'])) $q->whereDate('created_at','<=',$filters['date_to']); return $q; } public function toolbar(): array { return ['search','date_range','csv','excel','pdf','print','column_visibility']; } }
