<?php

namespace Modules\Finance\Http\Controllers\FixedAssets;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Modules\Finance\Entities\Account;
use Modules\Finance\Entities\AccountTransaction;
use Modules\Finance\Entities\FixedAsset;
use Modules\Finance\Entities\User;
use Modules\Finance\Utils\FinancePermissionHelper;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $this->businessId($request);
        $accountAccess = FinancePermissionHelper::can('finance.accounts.view');

        if (! $accountAccess) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            return $this->data($request, $businessId);
        }

        // Filter options are loaded after the DataTable request so the first
        // screen and first page of records are not blocked by three extra queries.
        return view('finance::fixed_assets.index', [
            'accountAccess' => $accountAccess,
            'locations' => collect(),
            'names' => collect(),
            'users' => collect(),
        ]);
    }

    public function filterOptions(Request $request)
    {
        $businessId = $this->businessId($request);
        if (! FinancePermissionHelper::can('finance.accounts.view')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            return response()->json([
                'locations' => DB::table('fixed_assets')
                    ->where('business_id', $businessId)
                    ->whereNotNull('asset_location')
                    ->where('asset_location', '!=', '')
                    ->distinct()
                    ->orderBy('asset_location')
                    ->pluck('asset_location')
                    ->values(),
                'names' => DB::table('fixed_assets')
                    ->where('business_id', $businessId)
                    ->whereNotNull('asset_name')
                    ->where('asset_name', '!=', '')
                    ->distinct()
                    ->orderBy('asset_name')
                    ->pluck('asset_name')
                    ->values(),
                'users' => User::where('business_id', $businessId)
                    ->orderBy('username')
                    ->get(['id', 'username'])
                    ->map(fn ($user) => ['id' => (int) $user->id, 'name' => (string) $user->username])
                    ->values(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Finance fixed asset filter options failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'locations' => [],
                'names' => [],
                'users' => [],
            ]);
        }
    }

    public function create(Request $request)
    {
        $businessId = $this->businessId($request);
        $this->authorizeAccess();

        return view('finance::fixed_assets.create', [
            'accounts' => $this->fixedAssetAccounts($businessId),
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $this->businessId($request);
        $this->authorizeAccess();
        $validated = $this->validatedData($request, $businessId);

        try {
            DB::transaction(function () use ($validated, $businessId) {
                $asset = FixedAsset::create(array_merge($validated, [
                    'business_id' => $businessId,
                    'created_by' => Auth::id(),
                ]));
                $this->replaceAccountingEntries($asset, $businessId);
            }, 3);

            return back()->with('status', ['success' => 1, 'msg' => __('messages.success')]);
        } catch (\Throwable $e) {
            Log::error('Finance fixed asset create failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function edit(Request $request, int $id)
    {
        $businessId = $this->businessId($request);
        $this->authorizeAccess();
        $fixedAsset = FixedAsset::where('business_id', $businessId)->findOrFail($id);

        return view('finance::fixed_assets.edit', [
            'accounts' => $this->fixedAssetAccounts($businessId),
            'fixed_asset' => $fixedAsset,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $businessId = $this->businessId($request);
        $this->authorizeAccess();
        $validated = $this->validatedData($request, $businessId);

        try {
            DB::transaction(function () use ($validated, $businessId, $id) {
                $asset = FixedAsset::where('business_id', $businessId)
                    ->lockForUpdate()
                    ->findOrFail($id);
                $asset->update($validated);
                $this->replaceAccountingEntries($asset, $businessId);
            }, 3);

            return back()->with('status', ['success' => 1, 'msg' => __('messages.success')]);
        } catch (\Throwable $e) {
            Log::error('Finance fixed asset update failed', [
                'business_id' => $businessId,
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return back()->withInput()->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $businessId = $this->businessId($request);
        $this->authorizeAccess();

        try {
            DB::transaction(function () use ($businessId, $id) {
                $asset = FixedAsset::where('business_id', $businessId)
                    ->lockForUpdate()
                    ->findOrFail($id);
                AccountTransaction::where('business_id', $businessId)
                    ->where('fixed_asset_id', $asset->id)
                    ->delete();
                $asset->delete();
            }, 3);

            return ['success' => 1, 'msg' => __('messages.success')];
        } catch (\Throwable $e) {
            Log::error('Finance fixed asset delete failed', [
                'business_id' => $businessId,
                'id' => $id,
                'message' => $e->getMessage(),
            ]);

            return ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }
    }

    private function data(Request $request, int $businessId)
    {
        $draw = max(0, (int) $request->input('draw', 0));

        try {
            $base = DB::table('fixed_assets as FA')
                ->leftJoin('accounts as FAA', function ($join) use ($businessId) {
                    $join->on('FAA.id', '=', 'FA.account_id')
                        ->where('FAA.business_id', '=', $businessId);
                })
                ->leftJoin('users as FAU', 'FA.created_by', '=', 'FAU.id')
                ->where('FA.business_id', $businessId);

            $recordsTotal = (clone $base)->count('FA.id');

            if ($startDate = $this->safeDate($request->input('start_date'))) {
                $base->where('FA.date_of_operation', '>=', $startDate . ' 00:00:00');
            }
            if ($endDate = $this->safeDate($request->input('end_date'))) {
                $base->where('FA.date_of_operation', '<=', $endDate . ' 23:59:59');
            }
            if ($request->filled('asset_name')) {
                $base->where('FA.asset_name', (string) $request->input('asset_name'));
            }
            if ($request->filled('created_by')) {
                $base->where('FA.created_by', (int) $request->input('created_by'));
            }
            if ($request->filled('location_id')) {
                $base->where('FA.asset_location', (string) $request->input('location_id'));
            }

            $search = trim((string) data_get($request->input('search', []), 'value', ''));
            if ($search !== '') {
                $like = '%' . $search . '%';
                $base->where(function ($query) use ($like) {
                    $query->where('FA.asset_name', 'like', $like)
                        ->orWhere('FA.asset_location', 'like', $like)
                        ->orWhere('FAA.name', 'like', $like)
                        ->orWhere('FAA.account_number', 'like', $like)
                        ->orWhere('FAU.username', 'like', $like);
                });
            }

            $recordsFiltered = (clone $base)->count('FA.id');
            $start = max(0, (int) $request->input('start', 0));
            $length = min(100, max(10, (int) $request->input('length', 25)));
            [$orderColumn, $orderDirection] = $this->order($request);

            $rows = $base->select([
                    'FA.id',
                    'FA.date_of_operation',
                    'FA.asset_name',
                    'FA.asset_location',
                    'FA.amount',
                    'FAA.name as account_name',
                    'FAA.account_number as account_no',
                    'FAU.username as created_by_name',
                ])
                ->orderByRaw($orderColumn . ' ' . $orderDirection)
                ->offset($start)
                ->limit($length)
                ->get()
                ->map(function ($row) {
                    $amount = round((float) $row->amount, 4);

                    return [
                        'date_of_operation' => $this->displayDate($row->date_of_operation),
                        'account_name' => $row->account_name ?: '',
                        'asset_name' => $row->asset_name ?: '',
                        'asset_location' => $row->asset_location ?: '',
                        'account_no' => $row->account_no ?: '',
                        'amount' => '<span class="display_currency" data-currency_symbol="true" data-orig-value="' . $amount . '">' . $amount . '</span>',
                        'action' => $this->actionHtml((int) $row->id),
                    ];
                })
                ->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('Finance fixed assets DataTable failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('messages.something_went_wrong'),
            ]);
        }
    }

    private function actionHtml(int $id): string
    {
        return '<div class="btn-group">'
            . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown">'
            . e(__('messages.actions')) . ' <span class="caret"></span></button>'
            . '<ul class="dropdown-menu dropdown-menu-right">'
            . '<li><a href="' . route('finance.fixed-assets.edit', $id) . '" class="fixed_asset_edit"><i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</a></li>'
            . '<li><a href="#" data-href="' . route('finance.fixed-assets.destroy', $id) . '" class="delete_fixed_asset"><i class="glyphicon glyphicon-trash"></i> ' . e(__('messages.delete')) . '</a></li>'
            . '</ul></div>';
    }

    private function order(Request $request): array
    {
        $columns = [
            0 => 'FA.date_of_operation',
            1 => 'FAA.name',
            2 => 'FA.asset_name',
            3 => 'FA.asset_location',
            4 => 'FAA.account_number',
            5 => 'FA.amount',
            6 => 'FA.id',
        ];
        $index = (int) data_get($request->input('order', []), '0.column', 0);
        $direction = strtolower((string) data_get($request->input('order', []), '0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        return [$columns[$index] ?? 'FA.date_of_operation', $direction];
    }

    private function validatedData(Request $request, int $businessId): array
    {
        $data = $request->validate([
            'date_of_operation' => ['required', 'date'],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($query) => $query->where('business_id', $businessId)),
            ],
            'asset_name' => ['required', 'string', 'max:255'],
            'asset_location' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);
        $data['date_of_operation'] = Carbon::parse($data['date_of_operation'])->format('Y-m-d H:i:s');
        $data['amount'] = round((float) $data['amount'], 4);

        return $data;
    }

    private function replaceAccountingEntries(FixedAsset $asset, int $businessId): void
    {
        AccountTransaction::where('business_id', $businessId)
            ->where('fixed_asset_id', $asset->id)
            ->delete();

        $equityId = Account::where('business_id', $businessId)
            ->whereIn('name', ['Opening Balance Equity Account', 'Opening Balance Equity'])
            ->where(function ($query) {
                $query->where('is_closed', 0)->orWhereNull('is_closed');
            })
            ->value('id');

        if (! $equityId) {
            throw new \RuntimeException('Opening Balance Equity Account is not configured.');
        }

        $common = [
            'business_id' => $businessId,
            'amount' => (float) $asset->amount,
            'operation_date' => $asset->date_of_operation,
            'fixed_asset_id' => $asset->id,
            'created_by' => Auth::id(),
            'note' => 'Fixed Asset: ' . $asset->asset_name,
            'skip_duplicate_check' => true,
        ];

        AccountTransaction::createAccountTransaction(array_merge($common, [
            'account_id' => $asset->account_id,
            'type' => 'debit',
        ]));
        AccountTransaction::createAccountTransaction(array_merge($common, [
            'account_id' => $equityId,
            'type' => 'credit',
        ]));
    }

    private function fixedAssetAccounts(int $businessId)
    {
        return Account::query()
            ->leftJoin('account_types as FAT', 'accounts.account_type_id', '=', 'FAT.id')
            ->where('accounts.business_id', $businessId)
            ->where(function ($query) {
                $query->where('accounts.is_closed', 0)->orWhereNull('accounts.is_closed');
            })
            ->whereNull('accounts.deleted_at')
            ->where(function ($query) {
                $query->where('FAT.name', 'Fixed Assets')
                    ->orWhere('FAT.name', 'Fixed Asset')
                    ->orWhere('FAT.name', 'like', '%Fixed Asset%');
            })
            ->orderBy('accounts.name')
            ->pluck('accounts.name', 'accounts.id');
    }

    private function authorizeAccess(): void
    {
        if (! FinancePermissionHelper::can('finance.accounts.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }

    private function safeDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function displayDate($value): string
    {
        try {
            return Carbon::parse($value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
