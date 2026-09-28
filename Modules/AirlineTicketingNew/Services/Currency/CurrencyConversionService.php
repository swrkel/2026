<?php
namespace Modules\AirlineTicketingNew\Services\Currency;
use Modules\AirlineTicketingNew\Entities\ExchangeRate;
class CurrencyConversionService {
    public function rate(int $businessId,string $from,string $to,?string $date=null): float {
        if($from===$to)return 1;
        return (float)(ExchangeRate::where('business_id',$businessId)->where('from_currency',$from)->where('to_currency',$to)
            ->whereDate('rate_date','<=',$date?:now()->toDateString())->latest('rate_date')->value('rate')?:0);
    }
    public function convert(int $businessId,float $amount,string $from,string $to,?string $date=null): float {
        return round($amount*$this->rate($businessId,$from,$to,$date),4);
    }
}
