<?php

namespace Modules\Chequer\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChequeBookController extends Controller
{
    use Concerns;

    public function index(Request $request)
    {
        $business_id = $this->businessId();
        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [25, 50, 100]) ? $perPage : 25;
        $q = trim((string) $request->get('q', ''));

        $rows = $this->tableReady('cheq_cheque_books')
            ? DB::table('cheq_cheque_books')
                ->leftJoin('accounts', 'cheq_cheque_books.account_id', '=', 'accounts.id')
                ->where('cheq_cheque_books.business_id', $business_id)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('cheq_cheque_books.book_no', 'like', "%{$q}%")
                            ->orWhere('accounts.name', 'like', "%{$q}%")
                            ->orWhere('accounts.account_number', 'like', "%{$q}%")
                            ->orWhere('cheq_cheque_books.status', 'like', "%{$q}%");
                    });
                })
                ->select('cheq_cheque_books.*', 'accounts.name as bank_account_name', 'accounts.account_number')
                ->orderByDesc('cheq_cheque_books.id')
                ->paginate($perPage)
                ->appends($request->query())
            : collect();

        $leaf_summary = [];
        if ($this->tableReady('cheq_cheque_leaves')) {
            $summary = DB::table('cheq_cheque_leaves')
                ->select('cheq_cheque_book_id', 'status', DB::raw('COUNT(*) as total'))
                ->where('business_id', $business_id)
                ->groupBy('cheq_cheque_book_id', 'status')
                ->get();

            foreach ($summary as $item) {
                $leaf_summary[$item->cheq_cheque_book_id][$item->status] = (int) $item->total;
            }
        }

        return view('chequer::cheque_books.index', compact('rows', 'leaf_summary'));
    }

    public function create()
    {
        $accounts = $this->bankAccountsForDropdown();
        return view('chequer::cheque_books.form', ['row' => null, 'accounts' => $accounts]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'book_no' => 'required|string|max:100',
            'start_no' => 'required|integer|min:1',
            'end_no' => 'required|integer|min:1',
            'status' => 'nullable|string|max:20'
        ]);

        if ((int) $data['end_no'] < (int) $data['start_no']) {
            return back()->withInput()->with('status', ['success' => 0, 'msg' => 'End No must be greater than or equal to Start No.']);
        }

        DB::transaction(function () use ($data) {
            $business_id = $this->businessId();
            $now = now();

            $payload = $data;
            $payload['business_id'] = $business_id;
            $payload['next_no'] = $payload['start_no'];
            $payload['status'] = $payload['status'] ?? 'active';
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;

            $book_id = DB::table('cheq_cheque_books')->insertGetId($payload);
            $this->generateLeaves($book_id, $payload['start_no'], $payload['end_no'], $business_id, $now);
        });

        return redirect('/chequer-module/cheque-books')->with('status', ['success' => 1, 'msg' => 'Cheque book saved and cheque leaves generated successfully']);
    }

    public function edit($id)
    {
        $row = DB::table('cheq_cheque_books')->where('business_id', $this->businessId())->where('id', $id)->first();
        abort_if(!$row, 404);
        $accounts = $this->bankAccountsForDropdown();

        return view('chequer::cheque_books.form', compact('row', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'account_id' => 'required|integer',
            'book_no' => 'required|string|max:100',
            'start_no' => 'required|integer|min:1',
            'end_no' => 'required|integer|min:1',
            'next_no' => 'nullable|integer|min:1',
            'status' => 'nullable|string|max:20'
        ]);

        if ((int) $data['end_no'] < (int) $data['start_no']) {
            return back()->withInput()->with('status', ['success' => 0, 'msg' => 'End No must be greater than or equal to Start No.']);
        }

        DB::transaction(function () use ($data, $id) {
            $business_id = $this->businessId();
            $now = now();
            $data['updated_at'] = $now;
            $data['next_no'] = $data['next_no'] ?? $this->firstAvailableLeafNo($id, $business_id) ?? $data['start_no'];

            DB::table('cheq_cheque_books')->where('business_id', $business_id)->where('id', $id)->update($data);
            $this->generateMissingLeaves($id, $data['start_no'], $data['end_no'], $business_id, $now);
        });

        return redirect('/chequer-module/cheque-books')->with('status', ['success' => 1, 'msg' => 'Cheque book updated successfully']);
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $business_id = $this->businessId();
            if ($this->tableReady('cheq_cheque_leaves')) {
                DB::table('cheq_cheque_leaves')->where('business_id', $business_id)->where('cheq_cheque_book_id', $id)->delete();
            }
            DB::table('cheq_cheque_books')->where('business_id', $business_id)->where('id', $id)->delete();
        });

        return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Cheque book deleted successfully']);
    }

    private function generateLeaves($book_id, $start_no, $end_no, $business_id, $now)
    {
        if (!$this->tableReady('cheq_cheque_leaves')) {
            return;
        }

        $batch = [];
        for ($number = (int) $start_no; $number <= (int) $end_no; $number++) {
            $batch[] = [
                'business_id' => $business_id,
                'cheq_cheque_book_id' => $book_id,
                'cheque_no' => (string) $number,
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 500) {
                DB::table('cheq_cheque_leaves')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('cheq_cheque_leaves')->insert($batch);
        }
    }

    private function generateMissingLeaves($book_id, $start_no, $end_no, $business_id, $now)
    {
        if (!$this->tableReady('cheq_cheque_leaves')) {
            return;
        }

        $existing = DB::table('cheq_cheque_leaves')
            ->where('business_id', $business_id)
            ->where('cheq_cheque_book_id', $book_id)
            ->pluck('cheque_no')
            ->map(function ($value) { return (string) $value; })
            ->flip();

        $batch = [];
        for ($number = (int) $start_no; $number <= (int) $end_no; $number++) {
            if (isset($existing[(string) $number])) {
                continue;
            }

            $batch[] = [
                'business_id' => $business_id,
                'cheq_cheque_book_id' => $book_id,
                'cheque_no' => (string) $number,
                'status' => 'available',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($batch)) {
            DB::table('cheq_cheque_leaves')->insert($batch);
        }
    }

    private function firstAvailableLeafNo($book_id, $business_id)
    {
        if (!$this->tableReady('cheq_cheque_leaves')) {
            return null;
        }

        $leaf = DB::table('cheq_cheque_leaves')
            ->where('business_id', $business_id)
            ->where('cheq_cheque_book_id', $book_id)
            ->where('status', 'available')
            ->orderByRaw('CAST(cheque_no AS UNSIGNED) ASC')
            ->first();

        return $leaf ? $leaf->cheque_no : null;
    }
}
