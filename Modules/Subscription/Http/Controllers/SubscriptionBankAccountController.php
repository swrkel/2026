<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Entities\SubscriptionBankAccount;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionBankAccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $userId = auth()->id();

            $bank_accounts = SubscriptionBankAccount::with('user')
                ->select('subscription_bank_accounts.*')
                // Only bank accounts created by current user
                ->when(!auth()->user()->can('superadmin'), function ($query) use ($userId) {
                    $query->where('created_by', $userId);
                });

            return DataTables::of($bank_accounts)
                ->addColumn('action', function ($row) {
                    $action = '';
                    
                    $action .= '<button type="button" class="btn btn-xs btn-primary edit-bank-account" 
                        data-href="' . action([self::class, 'edit'], $row->id) . '" 
                        title="' . __('messages.edit') . '">
                        <i class="fa fa-edit"></i>
                    </button>';
                    
                    $action .= '&nbsp;<button type="button" class="btn btn-xs btn-danger delete-bank-account" 
                        data-href="' . action([self::class, 'destroy'], $row->id) . '" 
                        title="' . __('messages.delete') . '">
                        <i class="fa fa-trash"></i>
                    </button>';
                    
                    return $action;
                })
                ->editColumn('created_by', function ($row) {
                    return $row->user ? $row->user->username : __('messages.not_available');
                })
                ->editColumn('status', function ($row) {
                    $status = $row->status == 'enabled' ? 'success' : 'danger';
                    return '<span class="label label-' . $status . '">' . 
                           __('subscription::lang.' . $row->status) . 
                           '</span>';
                })
                ->editColumn('date', function ($row) {
                    return !empty($row->created_at) ? 
                        $row->created_at->format('Y-m-d H:i:s') : '';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('subscription::settings.tabs.bank_accounts');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('subscription::settings.modals.bank_account_form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('business.id');

        try {
            $request->validate([
                'template_name' => 'required|string|max:255',
                'ac_name'       => 'required|string|max:255',
                'ac_no'         => 'required|string|max:255',
                'bank'          => 'required|string|max:255',
                'branch'        => 'required|string|max:255',
                'status'        => 'required|in:enabled,disabled'
            ]);

            SubscriptionBankAccount::create([
                'business_id'   => $business_id,
                'template_name' => $request->template_name,
                'ac_name'       => $request->ac_name,
                'ac_no'         => $request->ac_no,
                'bank'          => $request->bank,
                'branch'        => $request->branch,
                'status'        => $request->status,
                'created_by'    => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('messages.saved_successfully')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.validation_error'),
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $account = SubscriptionBankAccount::findOrFail($id);
            
            // Check if user has permission to edit
            if (auth()->id() != $account->created_by && !auth()->user()->can('superadmin')) {
                abort(403, 'Unauthorized action.');
            }
            
            return view('subscription::settings.modals.bank_account_form', compact('account'));
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, __('messages.not_found'));
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'template_name' => 'required|string|max:255',
                'ac_name'       => 'required|string|max:255',
                'ac_no'         => 'required|string|max:255',
                'bank'          => 'required|string|max:255',
                'branch'        => 'required|string|max:255',
                'status'        => 'required|in:enabled,disabled'
            ]);

            $account = SubscriptionBankAccount::findOrFail($id);
            
            // Check if user has permission to update
            if (auth()->id() != $account->created_by && !auth()->user()->can('superadmin')) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.unauthorized_action')
                ], 403);
            }

            $account->update([
                'template_name' => $request->template_name,
                'ac_name'       => $request->ac_name,
                'ac_no'         => $request->ac_no,
                'bank'          => $request->bank,
                'branch'        => $request->branch,
                'status'        => $request->status
            ]);

            return response()->json([
                'success' => true,
                'msg' => __('messages.updated_successfully')
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.validation_error'),
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.not_found')
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $account = SubscriptionBankAccount::findOrFail($id);
            
            // Check if user has permission to delete
            if (auth()->id() != $account->created_by && !auth()->user()->can('superadmin')) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.unauthorized_action')
                ], 403);
            }

            $account->delete();

            return response()->json([
                'success' => true,
                'msg' => __('messages.deleted_successfully')
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.not_found')
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }
}