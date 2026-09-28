<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChequeLeafController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $business_id = $this->businessId();
        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100]) ? $perPage : 25;
        $q = trim((string) $request->get('q', ''));

        $rows = $this->tableReady('cheq_cheque_leaves')
            ? DB::table('cheq_cheque_leaves')
                ->leftJoin('cheq_cheque_books', 'cheq_cheque_leaves.cheq_cheque_book_id', '=', 'cheq_cheque_books.id')
                ->leftJoin('accounts', 'cheq_cheque_books.account_id', '=', 'accounts.id')
                ->where('cheq_cheque_leaves.business_id', $business_id)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('cheq_cheque_leaves.cheque_no', 'like', "%{$q}%")
                            ->orWhere('cheq_cheque_books.book_no', 'like', "%{$q}%")
                            ->orWhere('accounts.name', 'like', "%{$q}%")
                            ->orWhere('accounts.account_number', 'like', "%{$q}%")
                            ->orWhere('cheq_cheque_leaves.status', 'like', "%{$q}%");
                    });
                })
                ->select(
                    'cheq_cheque_leaves.*',
                    'cheq_cheque_books.book_no',
                    'accounts.name as bank_account_name',
                    'accounts.account_number'
                )
                ->orderByDesc('cheq_cheque_leaves.id')
                ->paginate($perPage)
                ->appends($request->query())
            : collect();

        return view('chequer::cheque_leaves.index', compact('rows'));
    }
}
