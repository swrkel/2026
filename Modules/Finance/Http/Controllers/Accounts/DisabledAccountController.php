<?php

namespace Modules\Finance\Http\Controllers\Accounts;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\System;
use Modules\Finance\Utils\FinancePermissionHelper;
use Modules\Superadmin\Entities\ModulePermissionLocation;

class DisabledAccountController extends Controller
{
    public function __construct(private ModuleUtil $moduleUtil)
    {
    }

    public function index(Request $request)
    {
        $businessId = $this->businessId($request);
        $accountAccess = FinancePermissionHelper::can('finance.accounts.view');

        if (! $this->moduleUtil->isSubscribed($businessId)) {
            return $this->moduleUtil->expiredResponse(action('HomeController@index'));
        }

        if (! $accountAccess) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->ajax()) {
            return $this->data($request, $businessId);
        }

        $businessLocations = BusinessLocation::where('business_id', $businessId)
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('finance::account.disabled_accounts', [
            'account_access' => $accountAccess,
            'business_locations' => $businessLocations,
            'disabled_message_color' => System::getProperty('not_enalbed_module_user_color'),
            'disabled_message_font_size' => System::getProperty('not_enalbed_module_user_font_size'),
            'disabled_message' => System::getProperty('not_enalbed_module_user_message'),
        ]);
    }

    private function data(Request $request, int $businessId)
    {
        $draw = max(0, (int) $request->input('draw', 0));

        try {
            $base = DB::table('accounts as DA')
                ->leftJoin('account_types as DAT_SUB', 'DA.account_type_id', '=', 'DAT_SUB.id')
                ->leftJoin('account_types as DAT_PARENT', 'DAT_SUB.parent_account_type_id', '=', 'DAT_PARENT.id')
                ->leftJoin('account_groups as DAG', 'DA.asset_type', '=', 'DAG.id')
                ->leftJoin('users as DU', 'DA.created_by', '=', 'DU.id')
                ->where('DA.business_id', $businessId)
                ->where('DA.disabled', 1)
                ->whereNull('DA.deleted_at');

            $locationId = $this->validLocationId($request->input('location_id'), $businessId);
            if ($locationId !== null) {
                $base->where(function ($locationScope) use ($locationId) {
                    $locationScope->where('DA.location_id', $locationId)
                        ->orWhereNull('DA.location_id')
                        ->orWhere('DA.location_id', 0);
                });
            }

            $recordsTotal = (clone $base)->count('DA.id');

            $search = trim((string) data_get($request->input('search', []), 'value', ''));
            if ($search !== '') {
                $like = '%' . $search . '%';
                $base->where(function ($query) use ($like) {
                    $query->where('DA.name', 'like', $like)
                        ->orWhere('DA.account_number', 'like', $like)
                        ->orWhere('DAT_SUB.name', 'like', $like)
                        ->orWhere('DAT_PARENT.name', 'like', $like)
                        ->orWhere('DAG.name', 'like', $like)
                        ->orWhereRaw("TRIM(CONCAT(COALESCE(DU.surname, ''), ' ', COALESCE(DU.first_name, ''), ' ', COALESCE(DU.last_name, ''))) LIKE ?", [$like]);
                });
            }

            $recordsFiltered = (clone $base)->count('DA.id');
            $start = max(0, (int) $request->input('start', 0));
            $length = min(100, max(10, (int) $request->input('length', 25)));

            [$orderColumn, $orderDirection] = $this->order($request);

            $rows = $base->select([
                    'DA.id',
                    'DA.name',
                    'DA.account_number',
                    'DA.visible',
                    'DA.created_by',
                    'DA.disabled',
                    'DA.is_closed',
                    'DA.location_id',
                    'DAT_SUB.name as account_type_name',
                    'DAT_PARENT.name as parent_account_type_name',
                    'DAG.name as account_group_name',
                    DB::raw("TRIM(CONCAT(COALESCE(DU.surname, ''), ' ', COALESCE(DU.first_name, ''), ' ', COALESCE(DU.last_name, ''))) as added_by"),
                ])
                ->orderByRaw($orderColumn . ' ' . $orderDirection)
                ->offset($start)
                ->limit($length)
                ->get();

            $balances = $this->balancesForPage(
                $rows->pluck('id')->map(fn ($id) => (int) $id)->all(),
                $businessId,
                $locationId
            );

            $canEdit = FinancePermissionHelper::can('finance.accounts.update');
            try {
                $canEdit = $canEdit || (auth()->user() && auth()->user()->can('account.edit'));
            } catch (\Throwable $e) {
                // Keep Finance permission result.
            }
            $data = $rows->map(function ($row) use ($balances, $canEdit) {
                $typeName = trim(($row->parent_account_type_name ?: '') . ' ' . ($row->account_type_name ?: ''));
                $debitNormal = stripos($typeName, 'asset') !== false || stripos($typeName, 'expense') !== false;
                $movement = $balances[(int) $row->id] ?? ['debit' => 0.0, 'credit' => 0.0];
                $balance = $debitNormal
                    ? (float) $movement['debit'] - (float) $movement['credit']
                    : (float) $movement['credit'] - (float) $movement['debit'];

                $name = e($row->name);
                if ((int) $row->is_closed === 1) {
                    $name .= ' <small class="label pull-right bg-red no-print">' . e(__('account.closed')) . '</small>';
                }

                return [
                    'name' => $name,
                    'parent_account_type_name' => $row->parent_account_type_name ?: ($row->account_type_name ?: ''),
                    'account_type_name' => $row->parent_account_type_name ? ($row->account_type_name ?: '') : '',
                    'account_group' => $row->account_group_name ?: '',
                    'account_number' => $row->account_number ?: '',
                    'balance' => '<span class="display_currency" data-currency_symbol="true" data-orig-value="' . round($balance, 4) . '">' . round($balance, 4) . '</span>',
                    'added_by' => (int) $row->created_by === 1 ? 'Default' : ($row->added_by ?: ''),
                    'action' => $this->actionHtml($row, $canEdit),
                    'DT_RowAttr' => ['data-visible' => (int) $row->visible],
                ];
            })->values();

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            Log::error('Finance disabled account list failed', [
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

    private function balancesForPage(array $accountIds, int $businessId, ?int $locationId): array
    {
        if ($accountIds === []) {
            return [];
        }

        $query = DB::table('account_transactions as AT')
            ->join('accounts as BA', function ($join) use ($businessId) {
                $join->on('BA.id', '=', 'AT.account_id')
                    ->where('BA.business_id', '=', $businessId);
            })
            ->leftJoin('transactions as BT', 'BT.id', '=', 'AT.transaction_id')
            ->leftJoin('transaction_payments as BP', 'BP.id', '=', 'AT.transaction_payment_id')
            ->whereIn('AT.account_id', $accountIds)
            ->whereNull('AT.deleted_at')
            ->where(function ($validPayment) {
                $validPayment->whereNull('AT.transaction_payment_id')
                    ->orWhereNotNull('BP.id');
            });

        if ($locationId !== null) {
            $query->where(function ($scope) use ($locationId) {
                $scope->where('BT.location_id', $locationId)
                    ->orWhere(function ($direct) use ($locationId) {
                        $direct->whereNull('AT.transaction_id')
                            ->where(function ($accountLocation) use ($locationId) {
                                $accountLocation->where('BA.location_id', $locationId)
                                    ->orWhereNull('BA.location_id')
                                    ->orWhere('BA.location_id', 0);
                            });
                    });
            });
        } else {
            $allowedLocationIds = $this->allowedLocationIds($businessId);
            if ($allowedLocationIds !== []) {
                $query->where(function ($scope) use ($allowedLocationIds) {
                    $scope->whereIn('BT.location_id', $allowedLocationIds)
                        ->orWhere(function ($direct) use ($allowedLocationIds) {
                            $direct->whereNull('AT.transaction_id')
                                ->where(function ($accountLocation) use ($allowedLocationIds) {
                                    $accountLocation->whereIn('BA.location_id', $allowedLocationIds)
                                        ->orWhereNull('BA.location_id')
                                        ->orWhere('BA.location_id', 0);
                                });
                        });
                });
            }
        }

        return $query->select('AT.account_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'debit' THEN AT.amount ELSE 0 END), 0) as debit_total")
            ->selectRaw("COALESCE(SUM(CASE WHEN AT.type = 'credit' THEN AT.amount ELSE 0 END), 0) as credit_total")
            ->groupBy('AT.account_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->account_id => [
                'debit' => (float) $row->debit_total,
                'credit' => (float) $row->credit_total,
            ]])
            ->all();
    }

    private function actionHtml(object $row, bool $canEdit): string
    {
        $items = [];

        if ($canEdit) {
            $items[] = '<li><a href="' . route('finance.account.edit-form', ['id' => $row->id]) . '" class="btn-modal" data-container=".account_model"><i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</a></li>';
        }

        $items[] = '<li><a href="' . route('finance.list-accounts.live.account_book.show', ['id' => $row->id], false) . '"><i class="fa fa-book"></i> ' . e(__('account.account_book')) . '</a></li>';

        if ((int) $row->is_closed === 0) {
            $items[] = '<li><a href="' . route('finance.account.fund-transfer.form', $row->id) . '" class="btn-modal" data-container=".account_model"><i class="fa fa-exchange"></i> ' . e(__('account.fund_transfer')) . '</a></li>';
            $items[] = '<li><a href="' . route('finance.account.deposit.form', $row->id) . '" class="btn-modal" data-container=".account_model"><i class="fa fa-money"></i> ' . e(__('account.deposit')) . '</a></li>';
            $items[] = '<li><a href="' . route('finance.account.notes', $row->id) . '" class="btn-modal" data-container=".account_model"><i class="fa fa-sticky-note-o"></i> ' . e(__('account.notes')) . '</a></li>';
            $items[] = '<li><button type="button" data-url="' . route('finance.account.close', $row->id) . '" class="btn btn-link close_account"><i class="fa fa-close"></i> ' . e(__('messages.close')) . '</button></li>';
            $items[] = '<li><button type="button" data-url="' . route('finance.account.disabled-status', $row->id) . '" class="btn btn-link disable_status_account"><i class="fa fa-check-circle-o"></i> ' . e(__('messages.enable')) . '</button></li>';
        }

        return '<div class="btn-group"><button type="button" class="btn btn-info btn-xs dropdown-toggle" data-toggle="dropdown">' . e(__('messages.actions')) . ' <span class="caret"></span></button><ul class="dropdown-menu dropdown-menu-right">' . implode('', $items) . '</ul></div>';
    }

    private function order(Request $request): array
    {
        $columns = [
            0 => 'DA.name',
            1 => 'COALESCE(DAT_PARENT.name, DAT_SUB.name)',
            2 => 'DAT_SUB.name',
            3 => 'DAG.name',
            4 => 'DA.account_number',
            5 => 'DA.name',
            6 => 'DU.first_name',
            7 => 'DA.id',
        ];
        $index = (int) data_get($request->input('order', []), '0.column', 0);
        $direction = strtolower((string) data_get($request->input('order', []), '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        return [$columns[$index] ?? 'DA.name', $direction];
    }

    private function allowedLocationIds(int $businessId): array
    {
        try {
            $permission = ModulePermissionLocation::getModulePermissionLocations($businessId, 'accounting_module');
            if (! empty($permission) && ! empty($permission->locations)) {
                return array_values(array_filter(array_map('intval', array_keys($permission->locations))));
            }
        } catch (\Throwable $e) {
            // No explicit location restriction means all current-business locations.
        }

        return [];
    }

    private function validLocationId($locationId, int $businessId): ?int
    {
        if ($locationId === null || $locationId === '' || $locationId === 'all') {
            return null;
        }

        $id = (int) $locationId;

        return $id > 0 && BusinessLocation::where('business_id', $businessId)->where('id', $id)->exists()
            ? $id
            : null;
    }

    private function businessId(Request $request): int
    {
        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }
}
