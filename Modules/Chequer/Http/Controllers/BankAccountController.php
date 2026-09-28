<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100]) ? $perPage : 25;
        $q = trim((string) $request->get('q', ''));

        $rows = $this->tableReady('accounts')
            ? $this->bankAccountsQuery()
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('accounts.name', 'like', "%{$q}%")
                            ->orWhere('accounts.account_number', 'like', "%{$q}%");
                    });
                })
                ->paginate($perPage)
                ->appends($request->query())
            : collect();

        return view('chequer::bank_accounts.index', compact('rows'));
    }
}
