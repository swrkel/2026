<?php
namespace Modules\Graphs\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GraphAnalyticsService
{
    public function businessId(): int
    {
        return (int) request()->session()->get('user.business_id', 0);
    }

    public function locations(int $businessId): array
    {
        if (!$businessId || !Schema::hasTable('business_locations')) return [];
        return DB::table('business_locations')->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')->map(fn($v, $k) => ['id' => (int)$k, 'name' => $v])->values()->all();
    }

    public function tanks(int $businessId, ?int $locationId = null): array
    {
        if (!$businessId || !Schema::hasTable('fuel_tanks') || !Schema::hasTable('products')) return [];
        $q = DB::table('fuel_tanks as ft')
            ->leftJoin('products as p', 'p.id', '=', 'ft.product_id')
            ->leftJoin('business_locations as bl', 'bl.id', '=', 'ft.location_id')
            ->where('ft.business_id', $businessId)
            ->select('ft.id','ft.fuel_tank_number','ft.storage_volume','ft.current_balance','ft.product_id','ft.location_id','p.name as product_name','p.alert_quantity','bl.name as location_name')
            ->orderBy('ft.fuel_tank_number');
        if ($locationId) $q->where('ft.location_id', $locationId);
        $rows = $q->get();
        $balances = $this->tankBalances($businessId, $rows->pluck('id')->map(fn($v)=>(int)$v)->all());
        return $rows->map(function ($r) use ($balances) {
            $storageVolume = max(0, (float)$r->storage_volume);
            $current = max(0, (float)($balances[(int)$r->id] ?? $r->current_balance));
            $reorder = max(0, (float)$r->alert_quantity);
            return [
                'id'=>(int)$r->id,'tank'=>(string)$r->fuel_tank_number,'product'=>(string)($r->product_name ?: 'Fuel'),
                'location'=>(string)($r->location_name ?: ''),'storage_volume'=>$storageVolume,'current'=>$current,'reorder'=>$reorder,
                'current_pct'=>$storageVolume > 0 ? round(($current/$storageVolume)*100,2) : 0,
                'reorder_pct'=>$storageVolume > 0 ? round(($reorder/$storageVolume)*100,2) : 0,
                'needs_reorder'=>$reorder > 0 && $current <= $reorder,
            ];
        })->all();
    }

    public function reorderAlerts(int $businessId): array
    {
        return array_values(array_filter($this->tanks($businessId), fn($t) => $t['needs_reorder']));
    }

    public function fuelSales(int $businessId, string $period, Carbon $startDate, Carbon $endDate, ?int $locationId = null): array
    {
        [$start,$end,$labels,$bucketSql,$bucketMode] = $this->rangeDefinition($period, $startDate, $endDate);
        $fuelIds = $this->fuelProductIds($businessId, $locationId);
        if (!$fuelIds || !Schema::hasTable('transactions') || !Schema::hasTable('transaction_sell_lines')) {
            return ['period'=>$period,'start'=>$start->toDateString(),'end'=>$end->toDateString(),'labels'=>$labels,'series'=>[]];
        }

        $q = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.business_id', $businessId)->where('t.type', 'sell')->where('t.status', 'final')
            ->whereNull('t.deleted_at')->whereBetween('t.transaction_date', [$start, $end])
            ->whereIn('tsl.product_id', $fuelIds)
            ->selectRaw("{$bucketSql} as bucket, p.name as product_name, SUM(GREATEST(COALESCE(tsl.quantity,0)-COALESCE(tsl.quantity_returned,0),0)) as qty")
            ->groupBy('bucket','p.name')->orderBy('bucket');
        if ($locationId) $q->where('t.location_id', $locationId);
        $rows = $q->get();

        $series = [];
        foreach ($rows as $r) {
            $name = (string)$r->product_name;
            if (!isset($series[$name])) $series[$name] = array_fill_keys($labels, 0.0);
            $label = $this->bucketLabel((string)$r->bucket, $bucketMode);
            if (array_key_exists($label, $series[$name])) $series[$name][$label] = round((float)$r->qty, 3);
        }
        $series = array_map(fn($vals, $name) => ['name'=>$name,'data'=>array_values($vals)], $series, array_keys($series));
        return ['period'=>$period,'start'=>$start->toDateString(),'end'=>$end->toDateString(),'labels'=>$labels,'series'=>array_values($series)];
    }

    public function nonFuelSales(int $businessId, string $period, Carbon $startDate, Carbon $endDate, ?int $locationId = null): array
    {
        $start=$startDate->copy()->startOfDay();
        $end=$endDate->copy()->endOfDay();
        if ($end->lt($start)) {
            $swapStart=$endDate->copy()->startOfDay();
            $swapEnd=$startDate->copy()->endOfDay();
            $start=$swapStart;
            $end=$swapEnd;
        }

        if (!Schema::hasTable('transactions') || !Schema::hasTable('transaction_sell_lines') || !Schema::hasTable('products')) {
            return ['period'=>$period,'start'=>$start->toDateString(),'end'=>$end->toDateString(),'categories'=>[],'fuel_total'=>0,'non_fuel_total'=>0,'fuel_pct'=>0,'non_fuel_pct'=>0];
        }
        $fuelIds = $this->fuelProductIds($businessId, $locationId);
        $base = DB::table('transaction_sell_lines as tsl')->join('transactions as t','t.id','=','tsl.transaction_id')->join('products as p','p.id','=','tsl.product_id')
            ->where('t.business_id',$businessId)->where('t.type','sell')->where('t.status','final')->whereNull('t.deleted_at')->whereBetween('t.transaction_date',[$start,$end]);
        if ($locationId) $base->where('t.location_id',$locationId);
        $amountExpr = 'GREATEST(COALESCE(tsl.quantity,0)-COALESCE(tsl.quantity_returned,0),0) * COALESCE(tsl.unit_price_inc_tax, tsl.unit_price, 0)';

        $fuelTotal = 0.0;
        if ($fuelIds) $fuelTotal = (float)(clone $base)->whereIn('tsl.product_id',$fuelIds)->selectRaw("SUM({$amountExpr}) total")->value('total');

        $non = clone $base;
        if ($fuelIds) $non->whereNotIn('tsl.product_id',$fuelIds);
        $non->leftJoin('categories as sc','sc.id','=','p.sub_category_id');
        $rows = $non->selectRaw("COALESCE(sc.name, 'Uncategorised') as category, SUM({$amountExpr}) as amount")->groupBy('category')->orderByDesc('amount')->get();
        $categories = $rows->map(fn($r)=>['name'=>(string)$r->category,'amount'=>round((float)$r->amount,2)])->all();
        $nonTotal = array_sum(array_column($categories,'amount'));
        $grand = $fuelTotal + $nonTotal;
        return [
            'period'=>$period,'start'=>$start->toDateString(),'end'=>$end->toDateString(),'categories'=>$categories,
            'fuel_total'=>round($fuelTotal,2),'non_fuel_total'=>round($nonTotal,2),
            'fuel_pct'=>$grand > 0 ? round(($fuelTotal/$grand)*100,2) : 0,
            'non_fuel_pct'=>$grand > 0 ? round(($nonTotal/$grand)*100,2) : 0,
        ];
    }

    private function tankBalances(int $businessId, array $tankIds): array
    {
        if (!$tankIds) return [];
        $sumMap = static function ($rows, string $key='qty'): array {
            $out=[]; foreach ($rows as $r) $out[(int)$r->tank_id]=(float)$r->{$key}; return $out;
        };

        $purchases=[];
        if (Schema::hasTable('tank_purchase_lines') && Schema::hasTable('transactions')) {
            $purchases=$sumMap(DB::table('tank_purchase_lines as tpl')
                ->join('fuel_tanks as ft','ft.id','=','tpl.tank_id')
                ->leftJoin('transactions as t','t.id','=','tpl.transaction_id')
                ->where('ft.business_id',$businessId)->whereIn('tpl.tank_id',$tankIds)
                ->where(function($q){$q->whereNull('t.type')->orWhere('t.type','!=','_deleted_purchase');})
                ->whereNull('tpl.new_deleted_at')
                ->groupBy('tpl.tank_id')->selectRaw('tpl.tank_id, SUM(COALESCE(tpl.quantity,0)) qty')->get());
        }
        $sales=[];
        if (Schema::hasTable('tank_sell_lines')) {
            $sales=$sumMap(DB::table('tank_sell_lines')->whereIn('tank_id',$tankIds)->groupBy('tank_id')->selectRaw('tank_id, SUM(COALESCE(quantity,0)) qty')->get());
        }
        $transferIn=$transferOut=[];
        if (Schema::hasTable('tank_transfers')) {
            $transferIn=$sumMap(DB::table('tank_transfers')->whereIn('to_tank',$tankIds)->groupBy('to_tank')->selectRaw('to_tank as tank_id, SUM(COALESCE(quantity,0)) qty')->get());
            $transferOut=$sumMap(DB::table('tank_transfers')->whereIn('from_tank',$tankIds)->groupBy('from_tank')->selectRaw('from_tank as tank_id, SUM(COALESCE(quantity,0)) qty')->get());
        }
        $adjustments=[];
        if (Schema::hasTable('stock_adjustment_lines') && Schema::hasTable('transactions')) {
            $adjustments=$sumMap(DB::table('stock_adjustment_lines as sal')
                ->join('transactions as t','t.id','=','sal.transaction_id')
                ->whereIn('sal.tank_id',$tankIds)
                ->where(function($q){$q->whereNull('t.sub_type')->orWhere('t.sub_type','!=','dip_resetting');})
                ->groupBy('sal.tank_id')
                ->selectRaw("sal.tank_id, SUM(CASE WHEN COALESCE(sal.stock_adjustment_type, sal.type)='increase' THEN sal.quantity WHEN COALESCE(sal.stock_adjustment_type, sal.type)='decrease' THEN -1*sal.quantity ELSE 0 END) qty")
                ->get());
        }
        $stored=DB::table('fuel_tanks')->where('business_id',$businessId)->whereIn('id',$tankIds)->pluck('current_balance','id');
        $out=[];
        foreach($tankIds as $id){
            $calc=($purchases[$id]??0)-abs($sales[$id]??0)+($transferIn[$id]??0)-($transferOut[$id]??0)+($adjustments[$id]??0);
            $fallback=(float)($stored[$id]??0);
            $out[$id]=(float)$calc===0.0 && $fallback!==0.0 ? $fallback : $calc;
        }
        return $out;
    }

    private function fuelProductIds(int $businessId, ?int $locationId = null): array
    {
        if (!Schema::hasTable('fuel_tanks')) return [];
        $q = DB::table('fuel_tanks')->where('business_id',$businessId)->whereNotNull('product_id');
        if ($locationId) $q->where('location_id',$locationId);
        return $q->distinct()->pluck('product_id')->map(fn($v)=>(int)$v)->all();
    }

    private function rangeDefinition(string $period, Carbon $startDate, Carbon $endDate): array
    {
        $period = in_array($period,['daily','weekly','monthly'],true) ? $period : 'daily';
        $start=$startDate->copy()->startOfDay();
        $end=$endDate->copy()->endOfDay();
        if ($end->lt($start)) {
            $swapStart=$endDate->copy()->startOfDay();
            $swapEnd=$startDate->copy()->endOfDay();
            $start=$swapStart;
            $end=$swapEnd;
        }

        if ($period === 'daily' && $start->isSameDay($end)) {
            $labels=[];
            for($h=0;$h<24;$h++) $labels[]=Carbon::createFromTime($h,0)->format('g A');
            return [$start,$end,$labels,"DATE_FORMAT(t.transaction_date, '%H')",'hour'];
        }

        if ($period === 'weekly') {
            $labels=[];
            $cursor=$start->copy()->startOfWeek(Carbon::MONDAY);
            $last=$end->copy()->startOfWeek(Carbon::MONDAY);
            while($cursor->lte($last)){
                $weekEnd=$cursor->copy()->addDays(6);
                $labels[]=$cursor->format('d M Y').' - '.$weekEnd->format('d M Y');
                $cursor->addWeek();
            }
            return [$start,$end,$labels,"DATE_FORMAT(DATE_SUB(DATE(t.transaction_date), INTERVAL WEEKDAY(t.transaction_date) DAY), '%Y-%m-%d')",'week'];
        }

        if ($period === 'monthly') {
            $labels=[];
            $cursor=$start->copy()->startOfMonth();
            $last=$end->copy()->startOfMonth();
            while($cursor->lte($last)){
                $labels[]=$cursor->format('M Y');
                $cursor->addMonth();
            }
            return [$start,$end,$labels,"DATE_FORMAT(t.transaction_date, '%Y-%m')",'month'];
        }

        $labels=[];
        $cursor=$start->copy()->startOfDay();
        $last=$end->copy()->startOfDay();
        while($cursor->lte($last)){
            $labels[]=$cursor->format('d M Y');
            $cursor->addDay();
        }
        return [$start,$end,$labels,"DATE_FORMAT(t.transaction_date, '%Y-%m-%d')",'day'];
    }

    private function bucketLabel(string $bucket, string $mode): string
    {
        if ($mode === 'hour') return Carbon::createFromTime((int)$bucket,0)->format('g A');
        if ($mode === 'week') {
            $d=Carbon::createFromFormat('Y-m-d',$bucket);
            return $d->format('d M Y').' - '.$d->copy()->addDays(6)->format('d M Y');
        }
        if ($mode === 'month') return Carbon::createFromFormat('Y-m',$bucket)->format('M Y');
        return Carbon::createFromFormat('Y-m-d',$bucket)->format('d M Y');
    }

}
