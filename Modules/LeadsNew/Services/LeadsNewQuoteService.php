<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewQuote;
class LeadsNewQuoteService { public function createDraft(array $data): LeadsNewQuote { $items=$data['items']??[]; $subtotal=collect($items)->sum(fn($i)=>(float)($i['qty']??1)*(float)($i['rate']??0)); return LeadsNewQuote::create(array_merge($data,['quote_no'=>$data['quote_no'] ?? 'QN-'.date('Ymd').'-'.str_pad((string)(LeadsNewQuote::count()+1),5,'0',STR_PAD_LEFT),'subtotal'=>$subtotal,'total'=>$subtotal+(float)($data['tax_total']??0),'status'=>'draft'])); } }
