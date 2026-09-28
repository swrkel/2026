<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrintHistoryController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $rows = $this->tableReady('cheq_print_history')
            ? DB::table('cheq_print_history')
                ->leftJoin('cheq_cheques', 'cheq_print_history.cheq_cheque_id', '=', 'cheq_cheques.id')
                ->leftJoin('users', 'cheq_print_history.printed_by', '=', 'users.id')
                ->where('cheq_print_history.business_id', $this->businessId())
                ->select('cheq_print_history.*', 'cheq_cheques.cheque_no', 'cheq_cheques.payee_name', 'cheq_cheques.amount', 'users.first_name', 'users.last_name')
                ->orderByDesc('cheq_print_history.id')
                ->paginate(25)
            : collect();

        return view('chequer::print_history.index', compact('rows'));
    }
}
