<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Models\DepositProduct;
use Modules\Deposits\Services\DepositNumberService;
use Modules\Deposits\Services\DepositPartyService;
use Modules\Deposits\Services\DepositSettingsService;
use Modules\Deposits\Services\DepositStatementService;

class DepositAccountController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('deposit_accounts')) {
            $accounts = collect();
            return view('deposits::accounts.index', compact('accounts'));
        }

        $query = DepositAccount::with('product')->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('account_no', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%');
            });
        }

        $accounts = $query->paginate(20);
        return view('deposits::accounts.index', compact('accounts'));
    }

    public function create(DepositNumberService $numberService)
    {
        $products = Schema::hasTable('deposit_products') ? DepositProduct::where('status', 'active')->get() : collect();
        $account = new DepositAccount(['account_no' => $numberService->nextAccountNumber(), 'opened_on' => date('Y-m-d'), 'status' => 'active']);
        return view('deposits::accounts.form', [
            'account' => $account,
            'products' => $products,
            'locations' => $this->locations(),
            'action' => route('deposits.accounts.store'),
        ]);
    }

    public function store(Request $request, DepositPartyService $partyService)
    {
        $data = $this->prepareAccountData($request);
        $data['business_id'] = session('business.id');
        $data['current_balance'] = $data['principal_amount'] ?? 0;
        $data['maturity_amount'] = $data['current_balance'];
        $data['created_by'] = auth()->id();
        $account = DepositAccount::create($data);
        $partyService->syncPrimaryParties($account, $request);
        return redirect()->route('deposits.accounts.show', $account->id)->with('status', ['success' => 1, 'msg' => __('deposits::lang.account_saved')]);
    }

    public function show($id)
    {
        $account = DepositAccount::with(['product', 'transactions' => function ($query) {
            $query->latest('transaction_date')->latest('id');
        }, 'parties'])->findOrFail($id);
        return view('deposits::accounts.show', compact('account'));
    }


    public function statement(Request $request, $id, DepositStatementService $statementService)
    {
        $account = DepositAccount::with(['product', 'parties'])->findOrFail($id);
        $transactions = $statementService->rows($account, $request->get('date_from'), $request->get('date_to'));
        $totals = $statementService->totals($transactions);

        if ($request->get('format') === 'csv') {
            $filename = 'deposit_statement_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $account->account_no) . '_' . date('Ymd_His') . '.csv';
            return response()->streamDownload(function () use ($account, $transactions, $totals) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Account No', $account->account_no]);
                fputcsv($out, ['Customer', $account->customer_name]);
                fputcsv($out, []);
                fputcsv($out, ['Date', 'Transaction No', 'Type', 'Amount', 'Balance After', 'Reference', 'Notes']);
                foreach ($transactions as $tx) {
                    fputcsv($out, [
                        optional($tx->transaction_date)->format('Y-m-d') ?: $tx->transaction_date,
                        $tx->transaction_no,
                        ucfirst($tx->type),
                        number_format((float) $tx->amount, 2, '.', ''),
                        number_format((float) $tx->balance_after, 2, '.', ''),
                        $tx->reference_no,
                        $tx->notes,
                    ]);
                }
                fputcsv($out, []);
                fputcsv($out, ['Total Credits', number_format((float) $totals['credits'], 2, '.', '')]);
                fputcsv($out, ['Total Debits', number_format((float) $totals['debits'], 2, '.', '')]);
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv']);
        }

        return view('deposits::accounts.statement', compact('account', 'transactions', 'totals'));
    }

    public function edit($id)
    {
        $account = DepositAccount::with('parties')->findOrFail($id);
        $products = DepositProduct::where('status', 'active')->get();
        return view('deposits::accounts.form', [
            'account' => $account,
            'products' => $products,
            'locations' => $this->locations(),
            'action' => route('deposits.accounts.update', $id),
        ]);
    }

    public function update(Request $request, $id, DepositPartyService $partyService)
    {
        $account = DepositAccount::findOrFail($id);
        $data = $this->prepareAccountData($request, $account);
        $data['updated_by'] = auth()->id();
        $account->update($data);
        $partyService->syncPrimaryParties($account, $request);
        return redirect()->route('deposits.accounts.show', $account->id)->with('status', ['success' => 1, 'msg' => __('deposits::lang.account_saved')]);
    }

    public function destroy($id)
    {
        $account = DepositAccount::withCount('transactions')->findOrFail($id);
        if ($account->transactions_count > 0) {
            return back()->with('status', ['success' => 0, 'msg' => 'Cannot delete an account with transactions. Close the account instead.']);
        }
        $account->delete();
        return redirect()->route('deposits.accounts.index')->with('status', ['success' => 1, 'msg' => __('deposits::lang.account_deleted')]);
    }

    private function prepareAccountData(Request $request, ?DepositAccount $account = null): array
    {
        $validated = $this->validatedData($request);
        $product = ! empty($validated['deposit_product_id']) ? DepositProduct::find($validated['deposit_product_id']) : null;

        if ($product) {
            $amount = (float) ($validated['principal_amount'] ?? 0);
            if ($product->minimum_amount && $amount < (float) $product->minimum_amount) {
                throw ValidationException::withMessages(['principal_amount' => 'Principal amount is below the product minimum amount.']);
            }
            if ($product->maximum_amount && $amount > (float) $product->maximum_amount) {
                throw ValidationException::withMessages(['principal_amount' => 'Principal amount is above the product maximum amount.']);
            }
            if (empty($validated['interest_rate'])) {
                $validated['interest_rate'] = $product->interest_rate;
            }
            if (empty($validated['maturity_on']) && ! empty($validated['opened_on']) && ! empty($product->term_months)) {
                $validated['maturity_on'] = Carbon::parse($validated['opened_on'])->addMonths((int) $product->term_months)->format('Y-m-d');
            }
            if ($product->require_nominee && empty($validated['nominee_name'])) {
                throw ValidationException::withMessages(['nominee_name' => 'Nominee is required for this deposit product.']);
            }
            if ($product->require_beneficiary && empty($validated['beneficiary_name'])) {
                throw ValidationException::withMessages(['beneficiary_name' => 'Beneficiary is required for this deposit product.']);
            }
        }

        $data = $this->accountOnlyData($validated);
        if ($account && $account->status !== 'active' && ($data['status'] ?? 'active') === 'active') {
            $data['status'] = $account->status;
        }
        return $data;
    }

    private function accountOnlyData(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'deposit_product_id', 'banking_customer_id', 'location_id', 'account_no',
            'customer_name', 'principal_amount', 'interest_rate', 'opened_on',
            'maturity_on', 'status', 'notes', 'auto_renew'
        ]));
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'deposit_product_id' => 'nullable|integer',
            'banking_customer_id' => 'nullable|integer',
            'location_id' => 'nullable|integer',
            'account_no' => 'required|string|max:191',
            'customer_name' => 'nullable|string|max:191',
            'principal_amount' => 'required|numeric|min:0',
            'interest_rate' => 'nullable|numeric|min:0',
            'opened_on' => 'nullable|date',
            'maturity_on' => 'nullable|date',
            'status' => 'required|string|max:50',
            'auto_renew' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'nominee_name' => 'nullable|string|max:191',
            'nominee_relationship' => 'nullable|string|max:191',
            'nominee_nic_no' => 'nullable|string|max:100',
            'nominee_mobile' => 'nullable|string|max:50',
            'nominee_share_percentage' => 'nullable|numeric|min:0|max:100',
            'nominee_address' => 'nullable|string',
            'beneficiary_name' => 'nullable|string|max:191',
            'beneficiary_relationship' => 'nullable|string|max:191',
            'beneficiary_nic_no' => 'nullable|string|max:100',
            'beneficiary_mobile' => 'nullable|string|max:50',
            'beneficiary_share_percentage' => 'nullable|numeric|min:0|max:100',
            'beneficiary_address' => 'nullable|string',
        ]);
    }

    private function locations()
    {
        if (! Schema::hasTable('business_locations')) {
            return collect();
        }

        $query = DB::table('business_locations')->orderBy('name');
        if (session('business.id')) {
            $query->where('business_id', session('business.id'));
        }
        return $query->pluck('name', 'id');
    }
}
