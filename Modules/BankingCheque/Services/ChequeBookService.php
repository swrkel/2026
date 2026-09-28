<?php
namespace Modules\BankingCheque\Services;
use Illuminate\Support\Facades\DB; use Modules\BankingCheque\Entities\ChequeBook; use Modules\BankingCheque\Entities\ChequeLeaf;
class ChequeBookService { public function issue(array $data): ChequeBook { return DB::transaction(function() use ($data){ $data['total_leaves']=($data['end_leaf_no']-$data['start_leaf_no'])+1; $book=ChequeBook::create($data); for($i=$data['start_leaf_no'];$i<=$data['end_leaf_no'];$i++){ ChequeLeaf::create(['cheque_book_id'=>$book->id,'account_no'=>$data['account_no'],'leaf_no'=>$i,'status'=>'available']); } return $book; }); } }
