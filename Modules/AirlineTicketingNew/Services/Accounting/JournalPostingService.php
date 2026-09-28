<?php
namespace Modules\AirlineTicketingNew\Services\Accounting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\AirlineTicketingNew\Entities\JournalEntry;
use Modules\AirlineTicketingNew\Services\Transactions\DocumentNumberService;
class JournalPostingService {
    public function __construct(private DocumentNumberService $numbers){}
    public function post(array $header,array $lines): JournalEntry {
        $debit=round(collect($lines)->sum(fn($x)=>(float)($x['debit']??0)),4);
        $credit=round(collect($lines)->sum(fn($x)=>(float)($x['credit']??0)),4);
        if(abs($debit-$credit)>0.0001) throw ValidationException::withMessages(['journal'=>'Debit and credit totals must balance.']);
        return DB::transaction(function()use($header,$lines,$debit,$credit){
            $j=JournalEntry::create(array_merge($header,[
                'journal_no'=>$this->numbers->next($header['business_id'],$header['business_location_id']??null,$header['store_id']??null,'journal','ATJ'),
                'total_debit'=>$debit,'total_credit'=>$credit,'status'=>'posted','posted_by'=>auth()->id(),'posted_at'=>now()
            ]));
            foreach($lines as $line){
                $rate=(float)($header['exchange_rate']??1);
                $j->lines()->create(array_merge($line,[
                    'business_id'=>$j->business_id,'business_location_id'=>$j->business_location_id,'store_id'=>$j->store_id,
                    'currency_code'=>$j->currency_code,'base_debit'=>round((float)($line['debit']??0)*$rate,4),
                    'base_credit'=>round((float)($line['credit']??0)*$rate,4)
                ]));
            }
            return $j->fresh('lines');
        });
    }
}
