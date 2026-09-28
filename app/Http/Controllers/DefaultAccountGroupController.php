<?php

namespace App\Http\Controllers;

use App\AccountGroup;
use App\AccountType;
use App\Business;
use App\DefaultAccountGroup;
use App\DefaultAccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class DefaultAccountGroupController extends Controller
{
    /**
     * Resolve the business id used for Super Admin default accounts.
     *
     * In /superadmin/settings the normal tenant session key `business.id` may not
     * be available. Older code used only that key, so Account Type dropdowns were
     * empty and Account Group save failed. This method keeps existing behaviour
     * but safely falls back to the logged-in business and then to the first active
     * business / first configured default account type.
     */
    private function resolveDefaultBusinessId(?Request $request = null, $accountTypeId = null)
    {
        $request = $request ?: request();

        $businessId = $request->session()->get('business.id')
            ?: $request->session()->get('user.business_id')
            ?: $request->input('business_id');

        if (!empty($accountTypeId)) {
            $typeBusinessId = DefaultAccountType::where('id', $accountTypeId)->value('business_id');
            if (!empty($typeBusinessId)) {
                return $typeBusinessId;
            }
        }

        if (!empty($businessId)) {
            return $businessId;
        }

        $activeBusinessId = Business::where('is_active', 1)->value('id');
        if (!empty($activeBusinessId)) {
            return $activeBusinessId;
        }

        return DefaultAccountType::whereNull('parent_account_type_id')->value('business_id');
    }

    private function getDefaultAccountTypes($businessId)
    {
        $query = DefaultAccountType::whereNull('parent_account_type_id')
            ->with(['sub_types']);

        if (!empty($businessId)) {
            $query->where('business_id', $businessId);
        }

        $accountTypes = $query->orderBy('name')->get();

        // Safety fallback for central Super Admin pages where session business id
        // is not present but default account types already exist for another id.
        if ($accountTypes->isEmpty()) {
            $accountTypes = DefaultAccountType::whereNull('parent_account_type_id')
                ->with(['sub_types'])
                ->orderBy('name')
                ->get();
        }

        return $accountTypes;
    }

    public function index()
    {
        $businessId = $this->resolveDefaultBusinessId();

        if (request()->ajax()) {
            $defaultAccountGroups = DefaultAccountGroup::leftJoin(
                    'default_account_types as ats',
                    'default_account_groups.account_type_id',
                    '=',
                    'ats.id'
                )
                ->select([
                    'default_account_groups.*',
                    DB::raw('COALESCE(ats.name, "") as account_type_name')
                ]);

            if (!empty($businessId)) {
                $defaultAccountGroups->where('default_account_groups.business_id', $businessId);
            }

            $defaultAccountGroups->groupBy('default_account_groups.id');

            return DataTables::of($defaultAccountGroups)
                ->addColumn(
                    'action',
                    '<button data-href="{{action(\'DefaultAccountGroupController@edit\',[$id])}}" data-container=".default_account_model" class="btn btn-xs btn-primary btn-modal edit_btn"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                    <button data-href="{{action(\'DefaultAccountGroupController@destroy\',[$id])}}" class="btn btn-xs btn-danger account_group_delete"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )
                ->editColumn('show_status', function ($row) {
                    return (int) $row->show_status === 1 ? 'Yes' : 'No';
                })
                ->removeColumn('id')
                ->removeColumn('is_closed')
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function create()
    {
        $businessId = $this->resolveDefaultBusinessId();
        $account_types = $this->getDefaultAccountTypes($businessId);

        return view('default_account.create_account_group')->with(compact('account_types'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'account_type_id' => 'required|integer|exists:default_account_types,id',
            'note' => 'nullable|string',
            'show_status' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first(),
            ]);
        }

        try {
            $input = $request->only(['name', 'account_type_id', 'note', 'show_status']);
            $input['show_status'] = isset($input['show_status']) ? (int) $input['show_status'] : 0;
            $input['business_id'] = $this->resolveDefaultBusinessId($request, $input['account_type_id']);

            DB::beginTransaction();

            $defaultAccountGroup = DefaultAccountGroup::updateOrCreate(
                [
                    'business_id' => $input['business_id'],
                    'name' => $input['name'],
                ],
                $input
            );

            $defaultAccountType = DefaultAccountType::find($defaultAccountGroup->account_type_id);

            // Add / sync the same account group for active tenant businesses.
            $businesses = Business::all();
            foreach ($businesses as $business) {
                $accountType = null;

                if (!empty($defaultAccountType)) {
                    $accountType = AccountType::where('business_id', $business->id)
                        ->where('default_account_type_id', $defaultAccountType->id)
                        ->first();

                    if (empty($accountType)) {
                        $accountType = AccountType::where('business_id', $business->id)
                            ->where('name', $defaultAccountType->name)
                            ->first();
                    }
                }

                AccountGroup::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'default_account_group_id' => $defaultAccountGroup->id,
                    ],
                    [
                        'business_id' => $business->id,
                        'name' => $defaultAccountGroup->name,
                        'account_type_id' => !empty($accountType) ? $accountType->id : null,
                        'note' => $defaultAccountGroup->note,
                        'show_status' => $defaultAccountGroup->show_status,
                        'default_account_group_id' => $defaultAccountGroup->id,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.add_account_group_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('Default account group store failed. File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $account_group = DefaultAccountGroup::findOrFail($id);
        $businessId = $this->resolveDefaultBusinessId(request(), $account_group->account_type_id);
        $account_types = $this->getDefaultAccountTypes($businessId);

        return view('default_account.edit_account_group')->with(compact('account_types', 'account_group'));
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:191',
            'account_type_id' => 'required|integer|exists:default_account_types,id',
            'note' => 'nullable|string',
            'show_status' => 'nullable|in:0,1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'msg' => $validator->errors()->first(),
            ]);
        }

        try {
            DB::beginTransaction();

            $defaultAccountGroup = DefaultAccountGroup::findOrFail($id);
            $defaultAccountGroup->name = $request->input('name');
            $defaultAccountGroup->note = $request->input('note');
            $defaultAccountGroup->account_type_id = $request->input('account_type_id');
            $defaultAccountGroup->show_status = (int) $request->input('show_status', 0);
            $defaultAccountGroup->business_id = $this->resolveDefaultBusinessId($request, $defaultAccountGroup->account_type_id);
            $defaultAccountGroup->save();

            $defaultAccountType = DefaultAccountType::find($defaultAccountGroup->account_type_id);

            $businesses = Business::all();
            foreach ($businesses as $business) {
                $accountType = null;

                if (!empty($defaultAccountType)) {
                    $accountType = AccountType::where('business_id', $business->id)
                        ->where('default_account_type_id', $defaultAccountType->id)
                        ->first();

                    if (empty($accountType)) {
                        $accountType = AccountType::where('business_id', $business->id)
                            ->where('name', $defaultAccountType->name)
                            ->first();
                    }
                }

                AccountGroup::where('default_account_group_id', $id)
                    ->where('business_id', $business->id)
                    ->update([
                        'business_id' => $business->id,
                        'name' => $defaultAccountGroup->name,
                        'account_type_id' => !empty($accountType) ? $accountType->id : null,
                        'note' => $defaultAccountGroup->note,
                        'show_status' => $defaultAccountGroup->show_status,
                        'default_account_group_id' => $defaultAccountGroup->id,
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.update_account_group_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('Default account group update failed. File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $accountGroup = DefaultAccountGroup::findOrFail($id);
            AccountGroup::where('default_account_group_id', $accountGroup->id)->delete();
            $accountGroup->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.delete_account_group_success'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency('Default account group delete failed. File: ' . $e->getFile() . ' Line: ' . $e->getLine() . ' Message: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function getDefaultAccountGroupByType($type_id)
    {
        $business_id = session()->get('business.id') ?: session()->get('user.business_id');

        $default_account_groups = DefaultAccountGroup::where('account_type_id', $type_id)
            ->when(!empty($business_id), function ($query) use ($business_id) {
                $query->where('business_id', $business_id);
            })
            ->get();

        $html = '<option selected="selected" value="">Please Select</option>';
        foreach ($default_account_groups as $account_group) {
            $html .= '<option value="' . e($account_group->id) . '" >' . e($account_group->name) . '</option>';
        }

        return $html;
    }
}
