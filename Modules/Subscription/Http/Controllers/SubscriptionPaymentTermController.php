<?php

namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Subscription\Entities\SubscriptionPaymentTerm;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionPaymentTermController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (request()->ajax()) {
            $userId = auth()->id();
            $payment_terms = SubscriptionPaymentTerm::with('user')
                ->select('subscription_payment_terms.*')
                // Only payment terms created by current user
                ->when(!auth()->user()->can('superadmin'), function ($query) use ($userId) {
                    $query->where('created_by', $userId);
                });

            return DataTables::of($payment_terms)
                ->addColumn('action', function ($row) {
                    $action = '';
                    
                    $action .= '<button type="button" class="btn btn-xs btn-primary edit-payment-term" 
                        data-href="' . action([self::class, 'edit'], $row->id) . '" 
                        title="' . __('messages.edit') . '">
                        <i class="fa fa-edit"></i>
                    </button>';
                    
                    $action .= '&nbsp;<button type="button" class="btn btn-xs btn-info view-terms" 
                        data-terms="' . htmlspecialchars($row->terms) . '" 
                        data-title="' . htmlspecialchars($row->name) . '" 
                        title="' . __('subscription::lang.view_terms') . '">
                        <i class="fa fa-eye"></i>
                    </button>';
                    
                    $action .= '&nbsp;<button type="button" class="btn btn-xs btn-danger delete-payment-term" 
                        data-href="' . action([self::class, 'destroy'], $row->id) . '" 
                        title="' . __('messages.delete') . '">
                        <i class="fa fa-trash"></i>
                    </button>';
                    
                    return $action;
                })
                ->addColumn('payment_terms', function ($row) {
                    // Truncate terms for preview
                    $truncated = strlen($row->terms) > 100 ? 
                        substr($row->terms, 0, 100) . '...' : 
                        $row->terms;
                    return htmlspecialchars($truncated);
                })
                ->editColumn('status', function ($row) {
                    $status = $row->status == 'enabled' ? 'success' : 'danger';
                    return '<span class="label label-' . $status . '">' . 
                           __('subscription::lang.' . $row->status) . 
                           '</span>';
                })
                ->editColumn('created_by', function ($row) {
                    return $row->user ? $row->user->username : __('messages.not_available');
                })
                ->editColumn('date', function ($row) {
                    return !empty($row->created_at) ? 
                        $row->created_at->format('Y-m-d H:i:s') : '';
                })
                ->rawColumns(['action', 'status'])
                ->make(true);
        }

        return view('subscription::settings.tabs.payment_terms');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('subscription::settings.modals.payment_term_form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('business.id');
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'terms' => 'required|string',
                'status' => 'required|in:enabled,disabled'
            ]);

            SubscriptionPaymentTerm::create([
                'business_id' => $business_id,
                'name' => $request->name,
                'terms' => $request->terms,
                'status' => $request->status,
                'created_by' => auth()->id()
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
            Log::error('Payment term create error: ' . $e->getMessage());
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
            $payment_term = SubscriptionPaymentTerm::findOrFail($id);
            
            // Check if user has permission to edit
            if (auth()->id() != $payment_term->created_by && !auth()->user()->can('superadmin')) {
                abort(403, 'Unauthorized action.');
            }
            
            return view('subscription::settings.modals.payment_term_form', compact('payment_term'));
            
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
                'name' => 'required|string|max:255',
                'terms' => 'required|string',
                'status' => 'required|in:enabled,disabled'
            ]);

            $payment_term = SubscriptionPaymentTerm::findOrFail($id);
            
            // Check if user has permission to update
            if (auth()->id() != $payment_term->created_by && !auth()->user()->can('superadmin')) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.unauthorized_action')
                ], 403);
            }

            $payment_term->update([
                'name' => $request->name,
                'terms' => $request->terms,
                'status' => $request->status
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
            Log::error('Payment term update error: ' . $e->getMessage());
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
            $payment_term = SubscriptionPaymentTerm::findOrFail($id);
            
            // Check if user has permission to delete
            if (auth()->id() != $payment_term->created_by && !auth()->user()->can('superadmin')) {
                return response()->json([
                    'success' => false,
                    'msg' => __('messages.unauthorized_action')
                ], 403);
            }

            $payment_term->delete();

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
            Log::error('Payment term delete error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get active payment terms for dropdown
     */
    public function getActivePaymentTerms()
    {
        try {
            $payment_terms = SubscriptionPaymentTerm::where('status', 'enabled')
                ->orderBy('name')
                ->get(['id', 'name', 'terms']);

            return response()->json([
                'success' => true,
                'data' => $payment_terms
            ]);

        } catch (\Exception $e) {
            Log::error('Get active payment terms error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }

    /**
     * Get payment term by ID
     */
    public function getPaymentTerm($id)
    {
        try {
            $payment_term = SubscriptionPaymentTerm::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $payment_term
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'msg' => __('messages.not_found')
            ], 404);

        } catch (\Exception $e) {
            Log::error('Get payment term error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ], 500);
        }
    }
}