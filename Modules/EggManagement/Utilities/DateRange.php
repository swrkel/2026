<?php
namespace Modules\EggManagement\Utilities;
use Carbon\Carbon;
class DateRange
{
    public static function resolve($request)
    {
        $range=$request->input('range');
        $now=now();
        if(!$range && !$request->filled('from') && !$request->filled('to')) $range='this_year';
        if($range==='this_year') return [$now->copy()->startOfYear(),$now->copy()->endOfYear()];
        if($range==='last_year'){ $x=$now->copy()->subYear(); return [$x->copy()->startOfYear(),$x->copy()->endOfYear()]; }
        if(in_array($range,['this_fy','last_fy'],true)){
            $m=(int)config('egg.fiscal_year_start_month',4);$start=Carbon::create($now->year,$m,1,0,0,0,$now->timezone);if($now->month<$m)$start->subYear();if($range==='last_fy')$start->subYear();return [$start->copy()->startOfDay(),$start->copy()->addYear()->subDay()->endOfDay()];
        }
        $to = $request->input('to') ? Carbon::parse($request->input('to'))->endOfDay() : $now->copy()->endOfDay();
        $from = $request->input('from') ? Carbon::parse($request->input('from'))->startOfDay() : $now->copy()->startOfYear();
        if ($from->gt($to)) { $x=$from; $from=$to->copy()->startOfDay(); $to=$x->copy()->endOfDay(); }
        return [$from,$to];
    }
}
