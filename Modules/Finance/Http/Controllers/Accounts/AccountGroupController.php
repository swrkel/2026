<?php

namespace Modules\Finance\Http\Controllers\Accounts;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Finance\Entities\AccountGroup;
use Modules\Finance\Entities\AccountType;
use Yajra\DataTables\Facades\DataTables;

class AccountGroupController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->data($request);
        }

        $this->authorizeAccess();

        return redirect()->route('finance.list-accounts.live', [
            'finance_tab' => 'account_groups',
        ]);
    }

    public function data(Request $request)
    {
        $this->authorizeAccess();
        $businessId = $this->businessId($request);

        $query = AccountGroup::query()
            ->leftJoin('account_types as ats', 'account_groups.account_type_id', '=', 'ats.id')
            ->where('account_groups.business_id', $businessId)
            ->select([
                'account_groups.id',
                'account_groups.name',
                'account_groups.note',
                'account_groups.default_account_group_id',
                'ats.name as account_type_name',
            ]);

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                if (! empty($row->default_account_group_id)) {
                    return '<span class="badge badge-danger">' . e(__('account.contact_superadmin')) . '</span>';
                }

                $editUrl = route('finance.list-accounts.live.account-groups.edit', ['id' => $row->id]);
                $deleteUrl = route('finance.list-accounts.live.account-groups.destroy', ['id' => $row->id]);

                return '<button type="button" data-href="' . e($editUrl) . '" data-container="#account_groups_modal" class="btn btn-xs btn-primary finance-account-modal-trigger edit_btn"><i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</button> '
                    . '<button type="button" data-href="' . e($deleteUrl) . '" class="btn btn-xs btn-danger account_group_delete"><i class="glyphicon glyphicon-trash"></i> ' . e(__('messages.delete')) . '</button>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Return Finance-owned account-group options for the Add Account modal.
     */
    public function optionsByType(int $typeId)
    {
        $this->authorizeAccess();
        $businessId = $this->businessId();

        $typeIds = [$typeId];
        $childTypeIds = AccountType::query()
            ->where('business_id', $businessId)
            ->where('parent_account_type_id', $typeId)
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->all();

        $typeIds = array_values(array_unique(array_merge($typeIds, $childTypeIds)));

        $accountGroups = AccountGroup::query()
            ->where('business_id', $businessId)
            ->whereIn('account_type_id', $typeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'reg_cheque']);

        $html = '<option selected="selected" value="">' . e(__('messages.please_select')) . '</option>';
        foreach ($accountGroups as $accountGroup) {
            $html .= '<option data-show-cheque="' . e((string) ($accountGroup->reg_cheque ?? 'N'))
                . '" value="' . (int) $accountGroup->id . '">' . e($accountGroup->name) . '</option>';
        }

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function create()
    {
        $this->authorizeAccess();
        $accountTypes = $this->topLevelTypes();

        return view('finance::account_groups.create', [
            'account_types' => $accountTypes,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_type_id' => ['required', 'integer'],
            'note' => ['nullable', 'string'],
            'checkbox_need_chequer_item' => ['nullable'],
        ]);

        try {
            $validated['business_id'] = $this->businessId($request);
            $validated['reg_cheque'] = $request->boolean('checkbox_need_chequer_item') ? 1 : 0;
            unset($validated['checkbox_need_chequer_item']);

            AccountGroup::create($validated);

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.add_account_group_success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Finance account group create failed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 422);
        }
    }

    public function edit(int $id)
    {
        $this->authorizeAccess();
        $businessId = $this->businessId();

        $accountGroup = AccountGroup::query()
            ->where('business_id', $businessId)
            ->findOrFail($id);

        return view('finance::account_groups.edit', [
            'account_group' => $accountGroup,
            'account_types' => $this->topLevelTypes(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeAccess();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_type_id' => ['required', 'integer'],
            'note' => ['nullable', 'string'],
            'checkbox_need_chequer_item' => ['nullable'],
        ]);

        try {
            $businessId = $this->businessId($request);
            $accountGroup = AccountGroup::query()
                ->where('business_id', $businessId)
                ->findOrFail($id);

            $validated['reg_cheque'] = $request->boolean('checkbox_need_chequer_item') ? 1 : 0;
            unset($validated['checkbox_need_chequer_item']);
            $accountGroup->update($validated);

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.update_account_group_success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Finance account group update failed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 422);
        }
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeAccess();

        try {
            AccountGroup::query()
                ->where('business_id', $this->businessId($request))
                ->whereKey($id)
                ->delete();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.delete_account_group_success'),
            ]);
        } catch (\Throwable $exception) {
            Log::error('Finance account group delete failed', [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ], 422);
        }
    }

    private function topLevelTypes()
    {
        return AccountType::query()
            ->where('business_id', $this->businessId())
            ->whereNull('parent_account_type_id')
            ->with(['sub_types' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
    }

    private function authorizeAccess(): void
    {
        if (! auth()->check() || ! auth()->user()->can('account.access')) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function businessId(?Request $request = null): int
    {
        $request = $request ?: request();

        return (int) ($request->session()->get('user.business_id')
            ?: $request->session()->get('business.id'));
    }
}
