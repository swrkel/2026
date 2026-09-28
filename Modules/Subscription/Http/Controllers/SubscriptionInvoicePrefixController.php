<?php

namespace Modules\Subscription\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Entities\SubscriptionInvoicePrefix;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SubscriptionInvoicePrefixController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $business_id = session()->get('business.id');

            $prefixes = SubscriptionInvoicePrefix::with('user')->orderBy('created_at', 'desc');
            if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
                $prefixes->where('business_id', $business_id);
            }

            return DataTables::of($prefixes)
                ->addColumn('action', function ($row) {
                    $action = '';

                    $action .= '<button type="button" class="btn btn-xs btn-primary edit-invoice-prefix" 
                    data-href="' . action([self::class, 'edit'], $row->id) . '" 
                    title="' . __('messages.edit') . '">
                    <i class="fa fa-edit"></i>
                </button>';

                    $action .= '&nbsp;<button type="button" class="btn btn-xs btn-danger delete-invoice-prefix" 
                    data-href="' . action([self::class, 'destroy'], $row->id) . '" 
                    title="' . __('messages.delete') . '">
                    <i class="fa fa-trash"></i>
                </button>';

                    return $action;
                })
                ->addColumn('next_invoice_no', function ($row) {
                    $nextNumber = str_pad($row->current_number, 6, '0', STR_PAD_LEFT);
                    return $row->prefix . $nextNumber;
                })
                ->editColumn('user', function ($row) {
                    return $row->user ? $row->user->username : __('messages.not_available');
                })
                ->editColumn('current_number', function ($row) {
                    return $row->current_number;
                })
                ->editColumn('date', function ($row) {
                    return !empty($row->created_at) ? $row->created_at->format('Y-m-d H:i:s') : '';
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('subscription::settings.tabs.invoice_prefix');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $business_id = request()->session()->get('business.id');
        $users = User::where('status', 'active')
            ->where('business_id', $business_id)
            ->orderBy('username')
            ->get(['id', 'username', 'email']);

        return view('subscription::settings.modals.invoice_prefix_form', compact('users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $business_id = session()->get('business.id');

        try {
            $request->validate([
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'prefix' => 'required|string|max:20',
                'current_number' => 'required|integer|min:1'
            ]);

            // Check users already assigned
            $existingUsersQuery = SubscriptionInvoicePrefix::whereIn('user_id', $request->user_ids);
            if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
                $existingUsersQuery->where('business_id', $business_id);
            }
            $existingUsers = $existingUsersQuery->pluck('user_id')->toArray();

            if (!empty($existingUsers)) {
                return response()->json([
                    'success' => false,
                    'msg' => 'Some users already have a prefix assigned'
                ], 422);
            }

            // Create for multiple users
            foreach ($request->user_ids as $user_id) {
                $data = [
                    'user_id' => $user_id,
                    'prefix' => $request->prefix,
                    'current_number' => $request->current_number,
                ];
                if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
                    $data['business_id'] = $business_id;
                }
                if (Schema::hasColumn('subscription_invoice_prefixes', 'created_by')) {
                    $data['created_by'] = auth()->id();
                }

                SubscriptionInvoicePrefix::create($data);
            }

            return response()->json([
                'success' => true,
                'msg' => __('messages.saved_successfully')
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice prefix create error', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }
    // public function store(Request $request)
    // {
    //     $business_id = request()->session()->get('business.id');
    //     try {
    //         $request->validate([
    //             'user_id' => 'required|integer|exists:users,id',
    //             'prefix' => [
    //                 'required',
    //                 'string',
    //                 'max:20',
    //                 Rule::unique('subscription_invoice_prefixes', 'prefix')
    //                     ->where(function ($query) use ($business_id) {
    //                         return $query->where('business_id', $business_id);
    //                     })
    //             ],
    //             'current_number' => 'required|integer|min:1'
    //         ]);

    //         SubscriptionInvoicePrefix::create([
    //             'user_id' => $request->user_id,
    //             'business_id' => $business_id,
    //             'prefix' => $request->prefix,
    //             'current_number' => $request->current_number,
    //             'created_by' => auth()->id(),
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'msg' => __('messages.saved_successfully')
    //         ]);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'success' => false,
    //             'msg' => __('messages.validation_error'),
    //             'errors' => $e->errors()
    //         ], 422);
    //     } catch (\Exception $e) {
    //         Log::error('Invoice prefix create error: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'msg' => __('messages.something_went_wrong')
    //         ], 500);
    //     }
    // }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        try {
            $prefix = SubscriptionInvoicePrefix::with('user')->findOrFail($id);
            $business_id = session()->get('business.id');
            $users = User::where('status', 'active')
                ->where('business_id', $business_id)
                ->orderBy('username')
                ->get(['id', 'username', 'email']);

            if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id') && (int) $prefix->business_id !== (int) $business_id) {
                abort(403, 'Unauthorized action.');
            }

            return view('subscription::settings.modals.invoice_prefix_form', compact('prefix', 'users'));
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
            $business_id = session()->get('business.id');

            $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
                'prefix' => [
                    'required',
                    'string',
                    'max:20',
                    Rule::unique('subscription_invoice_prefixes', 'prefix')
                        ->where(function ($query) use ($business_id) {
                            if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id')) {
                                return $query->where('business_id', $business_id);
                            }

                            return $query;
                        })
                        ->ignore($id)
                ],
                'current_number' => 'required|integer|min:1'
            ]);

            $prefix = SubscriptionInvoicePrefix::findOrFail($id);
            if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id') && (int) $prefix->business_id !== (int) $business_id) {
                abort(403, 'Unauthorized action.');
            }

            $prefix->update([
                'user_id' => $request->user_ids[0],
                'prefix' => $request->prefix,
                'current_number' => $request->current_number
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
            Log::error('Invoice prefix update error: ' . $e->getMessage());
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
            $prefix = SubscriptionInvoicePrefix::findOrFail($id);
            $business_id = session()->get('business.id');

            if (Schema::hasColumn('subscription_invoice_prefixes', 'business_id') && (int) $prefix->business_id !== (int) $business_id) {
                abort(403, 'Unauthorized action.');
            }

            $prefix->delete();

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
            Log::error('Invoice prefix delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get next invoice number for a user
     */
    public function getNextInvoiceNumber($userId)
    {
        try {
            $prefix = SubscriptionInvoicePrefix::where('user_id', $userId)
                ->when(Schema::hasColumn('subscription_invoice_prefixes', 'business_id'), function ($query) {
                    $query->where('business_id', session()->get('business.id'));
                })
                ->first();

            if (!$prefix) {
                return response()->json([
                    'success' => false,
                    'msg' => __('subscription::lang.no_prefix_found_for_user')
                ], 404);
            }

            $invoiceNumber = $prefix->prefix . str_pad($prefix->current_number, 6, '0', STR_PAD_LEFT);

            // Increment the current number for next use
            $prefix->increment('current_number');

            return response()->json([
                'success' => true,
                'invoice_number' => $invoiceNumber,
                'prefix' => $prefix->prefix,
                'current_number' => $prefix->current_number - 1 // Return the used number
            ]);
        } catch (\Exception $e) {
            Log::error('Get next invoice number error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get invoice prefix for current user
     */
    public function getCurrentUserPrefix()
    {
        $prefix = SubscriptionInvoicePrefix::where('user_id', auth()->id())
            ->when(Schema::hasColumn('subscription_invoice_prefixes', 'business_id'), function ($query) {
                $query->where('business_id', session()->get('business.id'));
            })
            ->first();

        if (!$prefix) {
            return response()->json([
                'success' => false,
                'msg' => __('subscription::lang.no_prefix_found_for_user')
            ], 404);
        }

        return response()->json([
            'success' => true,
            'prefix' => $prefix->prefix,
            'current_number' => $prefix->current_number,
            'next_invoice_no' => $prefix->prefix . str_pad($prefix->current_number, 6, '0', STR_PAD_LEFT),
        ]);
    }
}
