<?php
namespace App\Http\Controllers;

use App\Account;
use App\AccountType;
use App\Models\PurchaseReturnAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class PurchaseSettingsController extends Controller
{
    public function index()
    {
        if (! auth()->user()->can('purchase_settings.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get current assets accounts - CORRECTED
        $current_assets_type = AccountType::where('business_id', $business_id)
            ->where('name', 'Current Assets')
            ->first();

        if ($current_assets_type) {
            $accounts = Account::where('business_id', $business_id)
                ->where('account_type_id', $current_assets_type->id)
                ->pluck('name', 'id');
        } else {
            $accounts = [];
        }

        return view('purchase_settings.index')->with(compact('accounts'));
    }

    public function create()
    {
        if (! auth()->user()->can('purchase_settings.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get current assets accounts
        $current_assets_type = AccountType::where('business_id', $business_id)
            ->where('name', 'Current Assets')
            ->first();

        if ($current_assets_type) {
            $accounts = Account::where('business_id', $business_id)
                ->where('account_type_id', $current_assets_type->id)
                ->pluck('name', 'id');
        } else {
            $accounts = [];
        }

        return view('purchase_settings.create')->with(compact('accounts'));
    }

    public function getPurchaseReturnAccounts()
    {
        if (! auth()->user()->can('purchase_settings.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $purchase_return_accounts = PurchaseReturnAccount::where('business_id', $business_id)
            ->with(['account', 'created_user'])
            ->get();

        return DataTables::of($purchase_return_accounts)
            ->addColumn('action', function ($row) {
                $html = '<div class="btn-group">
                    <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                . __("messages.actions") .
                '<span class="caret"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-right" role="menu">
                        <li><a href="#" data-href="' . action("PurchaseSettingsController@edit", [$row->id]) . '" class="edit-purchase-return-account-btn"><i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '</a></li>
                        <li><a href="#" data-href="' . action("PurchaseSettingsController@destroy", [$row->id]) . '" class="delete-purchase-return-account-btn"><i class="fa fa-trash"></i> ' . __("messages.delete") . '</a></li>
                    </ul>
                </div>';
                return $html;
            })
            ->editColumn('account.name', function ($row) {
                return $row->account->name;
            })
            ->editColumn('created_user', function ($row) {
                return $row->created_user->user_full_name;
            })
            ->editColumn('created_at', function ($row) {
                return $row->created_at->format('Y-m-d H:i:s');
            })
            ->removeColumn('id')
            ->rawColumns(['action'])
            ->make(true);
    }

    public function showCreateForm()
    {
        if (! auth()->user()->can('purchase_settings.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        // Get current assets accounts
        $current_assets_type = AccountType::where('business_id', $business_id)
            ->where('name', 'Current Assets')
            ->first();

        if ($current_assets_type) {
            $accounts = Account::where('business_id', $business_id)
                ->where('account_type_id', $current_assets_type->id)
                ->pluck('name', 'id');
        } else {
            $accounts = [];
        }

        return view('purchase_settings.create')->with(compact('accounts'));
    }

    public function store(Request $request)
    {
        if (! auth()->user()->can('purchase_settings.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input                = $request->only(['account_id']);
            $input['business_id'] = $business_id;
            $input['created_by']  = $request->session()->get('user.id');

            // Check if already exists
            $exists = PurchaseReturnAccount::where('business_id', $business_id)
                ->where('account_id', $input['account_id'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('purchase.account_already_added'),
                ]);
            }

            // Validate account is current asset
            $current_assets_type = AccountType::where('business_id', $business_id)
                ->where('name', 'Current Assets')
                ->first();

            if ($current_assets_type) {
                $is_current_asset = Account::where('id', $input['account_id'])
                    ->where('account_type_id', $current_assets_type->id)
                    ->exists();

                if (! $is_current_asset) {
                    return response()->json([
                        'success' => false,
                        'msg'     => __('purchase.account_not_current_asset'),
                    ]);
                }
            }

            PurchaseReturnAccount::create($input);

            $output = [
                'success' => true,
                'msg'     => __("purchase.purchase_return_account_added_success"),
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __("messages.something_went_wrong"),
            ];
        }

        return $output;
    }

    public function edit($id)
    {
        if (! auth()->user()->can('purchase_settings.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id             = request()->session()->get('user.business_id');
        $purchase_return_account = PurchaseReturnAccount::where('business_id', $business_id)
            ->findOrFail($id);

        // CORRECTED QUERY
        $current_assets_type = AccountType::where('business_id', $business_id)
            ->where('name', 'Current Assets')
            ->first();

        if ($current_assets_type) {
            $accounts = Account::where('business_id', $business_id)
                ->where('account_type_id', $current_assets_type->id)
                ->pluck('name', 'id');
        } else {
            $accounts = [];
        }

        return view('purchase_settings.edit')->with(compact('purchase_return_account', 'accounts'));
    }

    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('purchase_settings.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            $input = $request->only(['account_id']);

            // Check if already exists (excluding current record)
            $exists = PurchaseReturnAccount::where('business_id', $business_id)
                ->where('account_id', $input['account_id'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'msg'     => __('purchase.account_already_added'),
                ]);
            }

            // Validate account is current asset
            $current_assets_type = AccountType::where('business_id', $business_id)
                ->where('name', 'Current Assets')
                ->first();

            if ($current_assets_type) {
                $is_current_asset = Account::where('id', $input['account_id'])
                    ->where('account_type_id', $current_assets_type->id)
                    ->exists();

                if (! $is_current_asset) {
                    return response()->json([
                        'success' => false,
                        'msg'     => __('purchase.account_not_current_asset'),
                    ]);
                }
            }

            $purchase_return_account = PurchaseReturnAccount::where('business_id', $business_id)
                ->findOrFail($id);
            $purchase_return_account->update($input);

            $output = [
                'success' => true,
                'msg'     => __("purchase.purchase_return_account_updated_success"),
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __("messages.something_went_wrong"),
            ];
        }

        return $output;
    }

    public function destroy($id)
    {
        if (! auth()->user()->can('purchase_settings.delete')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id             = request()->session()->get('user.business_id');
            $purchase_return_account = PurchaseReturnAccount::where('business_id', $business_id)
                ->findOrFail($id);
            $purchase_return_account->delete();

            $output = [
                'success' => true,
                'msg'     => __("purchase.purchase_return_account_deleted_success"),
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg'     => __("messages.something_went_wrong"),
            ];
        }

        return $output;
    }
}
