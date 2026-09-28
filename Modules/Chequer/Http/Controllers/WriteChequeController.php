<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WriteChequeController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100]) ? $perPage : 25;
        $q = trim((string) $request->get('q', ''));

        $rows = $this->tableReady('cheq_cheques')
            ? DB::table('cheq_cheques')
                ->leftJoin('cheq_cheque_books', 'cheq_cheques.cheq_cheque_book_id', '=', 'cheq_cheque_books.id')
                ->leftJoin('accounts', 'cheq_cheque_books.account_id', '=', 'accounts.id')
                ->leftJoin('cheq_templates', 'cheq_cheques.cheq_template_id', '=', 'cheq_templates.id')
                ->where('cheq_cheques.business_id', $this->businessId())
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('cheq_cheques.cheque_no', 'like', "%{$q}%")
                            ->orWhere('cheq_cheques.payee_name', 'like', "%{$q}%")
                            ->orWhere('cheq_cheques.payment_type', 'like', "%{$q}%")
                            ->orWhere('accounts.name', 'like', "%{$q}%")
                            ->orWhere('cheq_cheques.status', 'like', "%{$q}%");
                    });
                })
                ->select('cheq_cheques.*', 'cheq_cheque_books.book_no', 'accounts.name as bank_account_name', 'cheq_templates.template_name')
                ->orderByDesc('cheq_cheques.id')
                ->paginate($perPage)
                ->appends($request->query())
            : collect();

        return view('chequer::write_cheque.index', compact('rows'));
    }

    public function create()
    {
        $books = $this->activeBooksWithNextLeaf();
        $book = $books->first();
        $next_no = $book ? $book->next_no : null;

        return view('chequer::write_cheque.form', [
            'books' => $books,
            'book' => $book,
            'next_no' => $next_no,
            'bankAccounts' => $this->bankAccountsForDropdown(),
            'templates' => $this->templatesForDropdown(),
            'payees' => $this->payeesForDropdown(),
            'currencies' => $this->currenciesForDropdown(),
            'doubleEntryAccounts' => $this->doubleEntryAccountsForDropdown(),
            'stamps' => $this->stampsForDropdown(),
            'defaultSettings' => $this->defaultSettings(),
            'paymentForOptions' => $this->paymentForOptions(),
            'paymentTypeOptions' => $this->paymentTypeOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'cheq_cheque_book_id' => 'required|integer',
            'cheque_date' => 'required|date',
            'payee_name' => 'required|string|max:191',
            'amount' => 'required|numeric|min:0.01',
            'payment_type' => 'required|string|max:80',
            'memo' => 'nullable|string|max:1000',
            'cheq_template_id' => 'nullable|integer',
            'payee_type' => 'nullable|string|max:80',
            'payee_id' => 'nullable|integer',
            'payment_for' => 'nullable|string|max:120',
            'purchase_id' => 'nullable|string|max:120',
            'purchase_bill_no' => 'nullable|string|max:191',
            'supplier_order_no' => 'nullable|string|max:191',
            'payable_amount' => 'nullable|numeric',
            'payment_status' => 'nullable|string|max:80',
            'bank_account_id' => 'nullable|integer',
            'stamp_id' => 'nullable|string|max:120',
            'date_condition' => 'nullable|string|max:80',
            'currency_id' => 'nullable|string|max:80',
            'currency_code' => 'nullable|string|max:20',
            'on_account_of' => 'nullable|string|max:500',
            'amount_words' => 'nullable|string|max:1000',
            'date_time' => 'nullable|string|max:80',
            'double_entry_account_id' => 'nullable|integer',
            'print_action' => 'nullable|string|max:80',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $business_id = $this->businessId();
        $printAction = $request->input('print_action', 'save');

        $chequeId = DB::transaction(function () use ($data, $business_id, $request) {
            $book = DB::table('cheq_cheque_books')
                ->where('business_id', $business_id)
                ->where('id', $data['cheq_cheque_book_id'])
                ->lockForUpdate()
                ->first();
            abort_if(!$book, 404);

            $leaf = $this->nextAvailableLeaf($book->id, $business_id);
            if (!$leaf) {
                abort(422, 'No available cheque leaf found for the selected cheque book.');
            }

            $options = [
                'post_date_cheque' => $request->boolean('post_date_cheque'),
                'show_in_account' => $request->boolean('show_in_account'),
                'not_negotiable' => $request->boolean('not_negotiable'),
                'account_payee_only' => $request->boolean('account_payee_only'),
                'strike_bearer' => $request->boolean('strike_bearer'),
                'cross_cheque' => $request->boolean('cross_cheque'),
                'prefix' => $request->boolean('prefix'),
                'double_cross_cheque' => $request->boolean('double_cross_cheque'),
                'suffix' => $request->boolean('suffix'),
                'remove_currency' => $request->boolean('remove_currency'),
                'print_signature' => $request->boolean('print_signature'),
                'delete_reverse' => $request->boolean('delete_reverse'),
                'stubs' => $request->boolean('stubs'),
            ];

            $payload = [
                'business_id' => $business_id,
                'cheq_cheque_book_id' => $book->id,
                'cheq_cheque_leaf_id' => $leaf->id,
                'cheque_no' => (string) $leaf->cheque_no,
                'cheque_date' => $data['cheque_date'],
                'payee_name' => $data['payee_name'],
                'amount' => $data['amount'],
                'payment_type' => $data['payment_type'],
                'memo' => $data['memo'] ?? null,
                'status' => $request->input('save_as_draft') ? 'draft' : 'issued',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $extra = [
                'cheq_template_id' => $data['cheq_template_id'] ?? null,
                'payee_type' => $data['payee_type'] ?? null,
                'payee_id' => $data['payee_id'] ?? null,
                'payment_for' => $data['payment_for'] ?? null,
                'purchase_id' => $data['purchase_id'] ?? null,
                'purchase_bill_no' => $data['purchase_bill_no'] ?? null,
                'supplier_order_no' => $data['supplier_order_no'] ?? null,
                'payable_amount' => $data['payable_amount'] ?? null,
                'payment_status' => $data['payment_status'] ?? null,
                'bank_account_id' => $data['bank_account_id'] ?? ($book->account_id ?? null),
                'stamp_id' => $data['stamp_id'] ?? null,
                'date_condition' => $data['date_condition'] ?? null,
                'currency_id' => $data['currency_id'] ?? null,
                'currency_code' => $data['currency_code'] ?? null,
                'on_account_of' => $data['on_account_of'] ?? null,
                'amount_words' => $data['amount_words'] ?? null,
                'date_time' => $data['date_time'] ?? null,
                'double_entry_account_id' => $data['double_entry_account_id'] ?? null,
                'options' => json_encode($options),
            ];

            foreach ($extra as $column => $value) {
                if ($this->columnReady('cheq_cheques', $column)) {
                    $payload[$column] = $value;
                }
            }

            if ($request->hasFile('attachment') && $this->columnReady('cheq_cheques', 'attachment_path')) {
                $payload['attachment_path'] = $request->file('attachment')->store('chequer/attachments', 'public');
            }

            $chequeId = DB::table('cheq_cheques')->insertGetId($payload);

            DB::table('cheq_cheque_leaves')
                ->where('id', $leaf->id)
                ->update([
                    'status' => 'issued',
                    'cheq_cheque_id' => $chequeId,
                    'issued_at' => now(),
                    'updated_at' => now(),
                ]);

            $nextLeaf = $this->nextAvailableLeaf($book->id, $business_id);
            DB::table('cheq_cheque_books')
                ->where('id', $book->id)
                ->update([
                    'next_no' => $nextLeaf ? $nextLeaf->cheque_no : (((int) $leaf->cheque_no) + 1),
                    'status' => $nextLeaf ? $book->status : 'completed',
                    'updated_at' => now(),
                ]);

            return $chequeId;
        });

        if (in_array($printAction, ['print_data_only', 'pre_print', 'print_cheque', 'print_voucher'], true)) {
            return redirect('/chequer-module/write-cheque/' . $chequeId . '/print?action=' . urlencode((string) $printAction));
        }

        return redirect('/chequer-module/write-cheque')->with('status', ['success' => 1, 'msg' => 'Cheque saved successfully']);
    }

    public function print($id)
    {
        $row = DB::table('cheq_cheques')
            ->leftJoin('cheq_cheque_books', 'cheq_cheques.cheq_cheque_book_id', '=', 'cheq_cheque_books.id')
            ->leftJoin('accounts', 'cheq_cheque_books.account_id', '=', 'accounts.id')
            ->leftJoin('cheq_templates', 'cheq_cheques.cheq_template_id', '=', 'cheq_templates.id')
            ->where('cheq_cheques.business_id', $this->businessId())
            ->where('cheq_cheques.id', $id)
            ->select('cheq_cheques.*', 'cheq_cheque_books.book_no', 'accounts.name as bank_account_name', 'accounts.account_number', 'cheq_templates.template_name', 'cheq_templates.field_map')
            ->first();
        abort_if(!$row, 404);

        $action = request()->get('action', 'print_cheque');

        DB::transaction(function () use ($id, $row, $action) {
            DB::table('cheq_cheques')->where('id', $id)->update(['status' => 'printed', 'printed_at' => now(), 'updated_at' => now()]);
            if ($this->tableReady('cheq_cheque_leaves') && !empty($row->cheq_cheque_leaf_id)) {
                DB::table('cheq_cheque_leaves')->where('id', $row->cheq_cheque_leaf_id)->update(['status' => 'printed', 'updated_at' => now()]);
            }
            if ($this->tableReady('cheq_print_history')) {
                $reprintCount = DB::table('cheq_print_history')->where('business_id', $this->businessId())->where('cheq_cheque_id', $id)->count();
                DB::table('cheq_print_history')->insert([
                    'business_id' => $this->businessId(),
                    'cheq_cheque_id' => $id,
                    'print_action' => $action,
                    'printer_name' => request()->get('printer_name'),
                    'template_name' => $row->template_name ?? null,
                    'calibration_profile' => request()->get('calibration_profile'),
                    'reprint_count' => $reprintCount,
                    'printed_by' => auth()->id(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $fieldMap = json_decode($row->field_map ?? '{}', true) ?: [];
        $options = json_decode($row->options ?? '{}', true) ?: [];

        return view('chequer::write_cheque.print', compact('row', 'action', 'fieldMap', 'options'));
    }

    protected function activeBooksWithNextLeaf()
    {
        if (!$this->tableReady('cheq_cheque_books')) {
            return collect();
        }

        $books = DB::table('cheq_cheque_books')
            ->leftJoin('accounts', 'cheq_cheque_books.account_id', '=', 'accounts.id')
            ->where('cheq_cheque_books.business_id', $this->businessId())
            ->where('cheq_cheque_books.status', 'active')
            ->select('cheq_cheque_books.*', 'accounts.name as bank_account_name', 'accounts.account_number')
            ->orderBy('accounts.name')
            ->orderBy('cheq_cheque_books.book_no')
            ->get();

        foreach ($books as $book) {
            $leaf = $this->nextAvailableLeaf($book->id, $this->businessId());
            $book->next_no = $leaf ? $leaf->cheque_no : null;
            $book->available_leaves = $this->tableReady('cheq_cheque_leaves') ? DB::table('cheq_cheque_leaves')
                ->where('business_id', $this->businessId())
                ->where('cheq_cheque_book_id', $book->id)
                ->where('status', 'available')
                ->count() : null;
        }

        return $books->filter(fn($book) => !empty($book->next_no))->values();
    }

    protected function nextAvailableLeaf($book_id, $business_id)
    {
        if (!$this->tableReady('cheq_cheque_leaves')) {
            $book = DB::table('cheq_cheque_books')->where('business_id', $business_id)->where('id', $book_id)->first();
            return $book ? (object) ['id' => null, 'cheque_no' => $book->next_no] : null;
        }

        return DB::table('cheq_cheque_leaves')
            ->where('business_id', $business_id)
            ->where('cheq_cheque_book_id', $book_id)
            ->where('status', 'available')
            ->orderByRaw('CAST(cheque_no AS UNSIGNED) ASC')
            ->lockForUpdate()
            ->first();
    }

    protected function templatesForDropdown()
    {
        if (!$this->tableReady('cheq_templates')) return collect();
        return DB::table('cheq_templates')->where('business_id', $this->businessId())->where('status', 'active')->orderBy('template_name')->get();
    }

    protected function payeesForDropdown()
    {
        if (!$this->tableReady('contacts')) return collect();
        $q = DB::table('contacts')->where('business_id', $this->businessId());
        if ($this->columnReady('contacts', 'deleted_at')) $q->whereNull('deleted_at');
        return $q->orderBy('name')->limit(1000)->get(['id', 'name', 'type']);
    }

    protected function currenciesForDropdown()
    {
        if ($this->tableReady('currencies')) {
            return DB::table('currencies')->orderBy('country')->get(['id', 'code', 'symbol', 'country']);
        }
        return collect([(object)['id'=>null,'code'=>'LKR','symbol'=>'Rs','country'=>'Sri Lanka']]);
    }

    protected function doubleEntryAccountsForDropdown()
    {
        if (!$this->tableReady('accounts')) return collect();
        return DB::table('accounts')->where('business_id', $this->businessId())->orderBy('name')->limit(1000)->get(['id','name','account_number']);
    }

    protected function stampsForDropdown()
    {
        if ($this->tableReady('cheq_stamps')) {
            return DB::table('cheq_stamps')->where('business_id', $this->businessId())->orderBy('name')->get();
        }
        return collect();
    }

    protected function defaultSettings()
    {
        return $this->tableReady('cheq_default_settings') ? DB::table('cheq_default_settings')->where('business_id', $this->businessId())->first() : null;
    }

    protected function paymentForOptions(): array
    {
        return [
            'supplier' => 'Supplier Payment',
            'customer_refund' => 'Customer Refund',
            'expense' => 'Expense Payment',
            'salary' => 'Salary Payment',
            'loan' => 'Loan / Advance',
            'other' => 'Other Payment',
        ];
    }

    protected function paymentTypeOptions(): array
    {
        return [
            'new_payment' => 'New Payment',
            'prepayment' => 'Prepayment',
            'advance_payment' => 'Advance Payment',
            'supplier_payment' => 'Supplier Payment',
            'customer_refund' => 'Customer Refund',
            'salary_payment' => 'Salary Payment',
        ];
    }
}
