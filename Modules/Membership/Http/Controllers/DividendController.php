<?php

namespace Modules\Membership\Http\Controllers;

use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Membership\Entities\MembershipDividend;
use Modules\Membership\Entities\MembershipMember;
use Modules\Membership\Entities\MembershipSetting;
use Modules\Membership\Entities\MembershipStatus;
use Yajra\DataTables\Facades\DataTables;

class DividendController extends Controller
{
    protected $commonUtil;

    public function __construct(Util $commonUtil)
    {
        $this->commonUtil = $commonUtil;
    }

    /**
     * Show the Add Dividends form
     */
    public function addDividends(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Get regions for filter
        $regions = MembershipSetting::where('business_id', $business_id)
            ->pluck('region', 'id');
        
        // Get membership statuses for filter
        $membershipStatuses = MembershipStatus::where('business_id', $business_id)
            ->pluck('status_name', 'id');
        
        // Get business name
        $business = \App\Business::where('id', $business_id)->first();
        $business_name = $business ? $business->name : '';
       
        $business_user = auth()->user()->user_full_name ?? auth()->user()->username ?? '';
        $auth_user_name = auth()->user()->user_full_name ?? auth()->user()->username ?? '';
        // Get all members for the dropdown (as fallback if AJAX doesn't work)
        $allMembers = MembershipMember::where('business_id', $business_id)
            ->select('id', 'member_name', 'member_number', 'membership_code')
            ->orderBy('member_name')
            ->get()
            ->map(function($member) {
                return [
                    'id' => $member->id,
                    'text' => $member->member_name . ' (' . ($member->member_number ?? $member->membership_code ?? '') . ')'
                ];
            });
        
        return view('membership::partials.add_dividends', compact('business_user', 'auth_user_name', 'regions', 'membershipStatuses', 'business_name', 'allMembers'));
    }

    /**
     * Get filtered members for Add Dividends page
     */
    public function getFilteredMembers(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $util = new \App\Utils\Util();
        $dividend_date = !empty($request->dividend_date) ? $util->uf_date($request->dividend_date) : null;
        $reference_number = $request->reference_number ?? null;
        if ($reference_number === '') $reference_number = null;
        
        $query = MembershipMember::where('business_id', $business_id)
            ->with(['membershipSetting', 'membershipType', 'membershipStatus']);

        // Filter by date joined range
        if ($request->has('date_joined_start') && $request->date_joined_start) {
            $query->whereDate('date_joined', '>=', $request->date_joined_start);
        }
        if ($request->has('date_joined_end') && $request->date_joined_end) {
            $query->whereDate('date_joined', '<=', $request->date_joined_end);
        }
        
        // Filter by region
        if ($request->has('region_id') && $request->region_id) {
            $query->where('membership_setting_id', $request->region_id);
        }
        
        // Filter by member
        if ($request->has('member_id') && $request->member_id) {
            $query->where('id', $request->member_id);
        }
        
        // Filter by membership status
        if ($request->has('membership_status_id') && $request->membership_status_id && $request->membership_status_id !== 'all') {
            $query->where('membership_status_id', $request->membership_status_id);
        }
        
        // Filter by date (dividend date)
        if ($request->has('date') && $request->date) {
            // This is for the dividend date filter if needed
        }
        
        // Filter by form_no (reference number) - this would need to be stored somewhere
        // For now, we'll skip this as it's not in the members table
        
        $members = $query->orderBy('member_name')->get();
        
        $util = new \App\Utils\Util();
        $dateFormat = session('business.date_format', 'd/m/Y');
        
        return response()->json([
            'success' => true,
            'members' => $members->map(function ($member) use ($dateFormat, $business_id, $dividend_date, $reference_number) {
                $dividend = MembershipDividend::where('member_id', $member->id)
                    ->where('business_id', $business_id)
                    ->where('dividend_date', $dividend_date)
                    ->where(function ($q) use ($reference_number) {
                        if (empty($reference_number)) {
                            $q->whereNull('reference_number')->orWhere('reference_number', '');
                        } else {
                            $q->where('reference_number', $reference_number);
                        }
                    })
                    ->with(['checkedBy', 'approvedBy'])
                    ->first();

                return [
                    'id' => $member->id,
                    'member_number' => $member->member_number,
                    'member_name' => $member->member_name,
                    'region' => $member->membershipSetting->region ?? '-',
                    'date_joined' => $member->date_joined ? \Carbon\Carbon::parse($member->date_joined)->format($dateFormat) : '-',
                    'no_of_shares' => $member->no_of_shares ?? 0,
                    'total_share_value' => $member->total_share_value ?? 0,
                    'membership_status' => $member->membershipStatus->status_name ?? '-',
                    'dividend_amount' => $dividend ? $dividend->dividend_amount : '',
                    'checked_by_name' => $dividend && $dividend->checkedBy ? ($dividend->checkedBy->user_full_name ?? $dividend->checkedBy->username) : '',
                    'approved_by_name' => $dividend && $dividend->approvedBy ? ($dividend->approvedBy->user_full_name ?? $dividend->approvedBy->username) : '',
                ];
            })
        ]);
    }

    /**
     * Store dividends
     */
    public function storeDividends(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        
        // Convert dividend_date from business format to MySQL format
        $util = new \App\Utils\Util();
        $dividend_date_input = $request->dividend_date;
        $dividend_date = !empty($dividend_date_input) ? $util->uf_date($dividend_date_input) : null;
        
        $request->merge(['dividend_date' => $dividend_date]);
        
        $request->validate([
            'dividend_date' => 'required|date',
            'dividends' => 'required|array',
            'dividends.*.member_id' => 'required|exists:membership_members,id',
            'dividends.*.amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();
            
            $reference_number = $request->reference_number ?? null;
            if ($reference_number === '') $reference_number = null; // normalize empty string to null
            $notes = $request->notes ?? null;

            foreach ($request->dividends as $dividendData) {
                if (!empty($dividendData['amount']) && $dividendData['amount'] > 0) {
                    $lookup = [
                        'business_id' => $business_id,
                        'member_id' => $dividendData['member_id'],
                        'dividend_date' => $dividend_date,
                        'reference_number' => $reference_number,
                    ];

                    MembershipDividend::updateOrCreate($lookup, [
                        'dividend_amount' => $dividendData['amount'],
                        'notes' => $notes,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'msg' => __('membership::lang.dividends_added_successfully'),
            ]);
        } catch (\ValidationException $e) {
            DB::rollBack();
            Log::error("DividendController storeDividends - Validation Error: " . json_encode($e->errors()));
            
            return response()->json([
                'success' => false,
                'msg' => __('messages.validation_error'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DividendController storeDividends - Error: " . $e->getMessage());
            Log::error("DividendController storeDividends - Stack: " . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Mark dividends as checked
     */
    public function markAsChecked(Request $request)
    {
        if (!auth()->user()->can('checked_by')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $util = new \App\Utils\Util();
        $dividend_date = !empty($request->dividend_date) ? $util->uf_date($request->dividend_date) : null;
        $reference_number = $request->reference_number ?? null;
        if ($reference_number === '') $reference_number = null;

        if (empty($dividend_date)) {
            return response()->json([
                'success' => false,
                'msg' => 'Invalid dividend date',
            ]);
        }

        try {
            DB::beginTransaction();

            // Auto-save dividends if provided in the request
            if (!empty($request->dividends) && is_array($request->dividends)) {
                $notes = $request->notes ?? null;
                foreach ($request->dividends as $dividendData) {
                    if (!empty($dividendData['amount']) && $dividendData['amount'] > 0) {
                        $lookup = [
                            'business_id' => $business_id,
                            'member_id' => $dividendData['member_id'],
                            'dividend_date' => $dividend_date,
                            'reference_number' => $reference_number,
                        ];

                        MembershipDividend::updateOrCreate($lookup, [
                            'dividend_amount' => $dividendData['amount'],
                            'notes' => $notes,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            $batchQuery = MembershipDividend::where('business_id', $business_id)
                ->where('dividend_date', $dividend_date)
                ->where(function($q) use ($reference_number) {
                    if (empty($reference_number)) {
                        $q->whereNull('reference_number')->orWhere('reference_number', '');
                    } else {
                        $q->where('reference_number', $reference_number);
                    }
                });

            $batchCount = (clone $batchQuery)->count();
            if ($batchCount === 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'msg' => 'No saved dividend rows found for this Date/Form No. Please save dividends first.'
                ], 422);
            }

            if ((clone $batchQuery)->where('is_approved', true)->exists()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'msg' => 'This dividend batch is already approved and cannot be checked again.'
                ], 422);
            }

            $updatedRows = (clone $batchQuery)->where('is_checked', false)->update([
                'is_checked' => true,
                'checked_by' => auth()->id(),
                'checked_at' => \Carbon\Carbon::now(),
            ]);

            if ($updatedRows === 0 && !(clone $batchQuery)->where('is_checked', true)->exists()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'msg' => 'Unable to mark this batch as checked. Please refresh and try again.'
                ], 422);
            }

            $checkedDividend = (clone $batchQuery)->with('checkedBy')->first();
            $checkedByName = $checkedDividend && $checkedDividend->checkedBy
                ? ($checkedDividend->checkedBy->user_full_name ?? $checkedDividend->checkedBy->username ?? '')
                : (auth()->user()->user_full_name ?? auth()->user()->username);

            DB::commit();

            return response()->json([
                'success' => true,
                'msg' => 'Marked as checked successfully',
                'checked_by' => $checkedByName,
                'auth_user_name' => $checkedByName
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("DividendController markAsChecked - Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong') . ': ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark dividends as approved
     */
    public function markAsApproved(Request $request)
    {
        if (!auth()->user()->can('approved_by')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $util = new \App\Utils\Util();
        $dividend_date = !empty($request->dividend_date) ? $util->uf_date($request->dividend_date) : null;
        $reference_number = $request->reference_number ?? null;
        if ($reference_number === '') $reference_number = null;

        if (!$dividend_date) {
            return response()->json(['success' => false, 'msg' => 'Please select a valid dividend date.']);
        }

        $batchQuery = MembershipDividend::where('business_id', $business_id)
            ->where('dividend_date', $dividend_date)
            ->where(function($q) use ($reference_number) {
                if (empty($reference_number)) {
                    $q->whereNull('reference_number')->orWhere('reference_number', '');
                } else {
                    $q->where('reference_number', $reference_number);
                }
            });

        $batchCount = (clone $batchQuery)->count();
        if ($batchCount === 0) {
            return response()->json([
                'success' => false,
                'msg' => 'No saved dividend rows found for this Date/Form No. Please save and check dividends first.'
            ], 422);
        }

        if (!(clone $batchQuery)->where('is_checked', true)->exists()) {
            return response()->json([
                'success' => false,
                'msg' => 'This dividend batch must be marked as checked before approval.'
            ], 422);
        }

        $updatedRows = (clone $batchQuery)->where('is_approved', false)->update([
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => \Carbon\Carbon::now(),
            ]);

        return response()->json([
            'success' => true,
            'msg' => $updatedRows > 0 ? 'Marked as approved successfully' : 'Batch is already approved.',
            'approved_by' => auth()->user()->user_full_name ?? auth()->user()->username,
            'auth_user_name' => auth()->user()->user_full_name ?? auth()->user()->username
        ]);
    }

    /**
     * Check locking status of a dividend batch
     */
    public function checkLockStatus(Request $request)
    {
        $user = auth()->user();
        if (
            ! $user->can('add_dividends')
            && ! $user->can('checked_by')
            && ! $user->can('approved_by')
            && ! $user->can('edit_dividends')
            && ! $user->can('membership.add_dividends_page')
            && ! $user->can('membership.list_dividends_page')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $util = new \App\Utils\Util();
        $dividend_date = !empty($request->dividend_date) ? $util->uf_date($request->dividend_date) : null;
        $reference_number = $request->reference_number ?? null;
        if ($reference_number === '') $reference_number = null;

        if (!$dividend_date) {
            return response()->json(['success' => false, 'msg' => 'Please select a valid dividend date.']);
        }

        $batchQuery = MembershipDividend::where('business_id', $business_id)
            ->where('dividend_date', $dividend_date)
            ->where(function($q) use ($reference_number) {
                if (empty($reference_number)) {
                    $q->whereNull('reference_number')->orWhere('reference_number', '');
                } else {
                    $q->where('reference_number', $reference_number);
                }
            });
        $checkedDividend = (clone $batchQuery)->where('is_checked', true)->with('checkedBy')->first();
        $approvedDividend = (clone $batchQuery)->where('is_approved', true)->with('approvedBy')->first();

        return response()->json([
            'success' => true,
            'is_checked' => !empty($checkedDividend),
            'checked_by' => !empty($checkedDividend) ? ($checkedDividend->checkedBy->user_full_name ?? $checkedDividend->checkedBy->username ?? '') : '',
            'is_approved' => !empty($approvedDividend),
            'approved_by' => !empty($approvedDividend) ? ($approvedDividend->approvedBy->user_full_name ?? $approvedDividend->approvedBy->username ?? '') : '',
        ]);
    }

    /**
     * Show List Dividends page
     */
    public function listDividends()
    {
        return view('membership::partials.list_dividends');
    }

    /**
     * Get Last Dividends data
     */
    public function getLastDividends()
    {
        $business_id = request()->session()->get('user.business_id');

        if (empty($business_id)) {
            return response()->json([
                'success' => false,
                'msg' => 'No business context',
            ], 400);
        }

        try {
            // Get the most recent dividend date
            $lastDividendDate = MembershipDividend::where('business_id', $business_id)
                ->max('dividend_date');

            if (!$lastDividendDate) {
                return response()->json([
                    'success' => true,
                    'dividends' => [],
                    'dividend_date' => null,
                ]);
            }

            // Get all dividends for that date
            $dividends = MembershipDividend::where('business_id', $business_id)
                ->where('dividend_date', $lastDividendDate)
                ->with(['member.membershipSetting', 'createdBy'])
                ->orderBy('member_id')
                ->get();

            return response()->json([
                'success' => true,
                'dividends' => $dividends->map(function ($dividend) {
                    return [
                        'id' => $dividend->id,
                        'member_number' => $dividend->member->member_number ?? '-',
                        'member_name' => $dividend->member->member_name ?? '-',
                        'region' => $dividend->member->membershipSetting->region ?? '-',
                        'dividend_amount' => number_format($dividend->dividend_amount, 2),
                        'reference_number' => $dividend->reference_number ?? '-',
                        'created_by' => $dividend->createdBy->user_full_name ?? '-',
                        'created_at' => $dividend->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'dividend_date' => $lastDividendDate,
                'total_amount' => number_format($dividends->sum('dividend_amount'), 2),
            ]);
        } catch (\Exception $e) {
            Log::error('DividendController getLastDividends - Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => 'Failed to retrieve last dividends: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get List of Issued Dividends (DataTable)
     */
    public function getIssuedDividends()
    {
        $business_id = request()->session()->get('user.business_id');

        if (empty($business_id)) {
            return response()->json([
                'success' => false,
                'msg' => 'No business context',
            ], 400);
        }

        try {
            $dividends = MembershipDividend::where('business_id', $business_id)
                ->with(['member.membershipSetting', 'createdBy'])
                ->select('membership_dividends.*');

            return DataTables::of($dividends)
                ->addColumn('member_number', function ($row) {
                    return $row->member->member_number ?? '-';
                })
                ->addColumn('member_name', function ($row) {
                    return $row->member->member_name ?? '-';
                })
                ->addColumn('region', function ($row) {
                    return $row->member->membershipSetting->region ?? '-';
                })
                ->addColumn('dividend_amount', function ($row) {
                    return number_format($row->dividend_amount, 2);
                })
                ->editColumn('reference_number', function ($row) {
                    return $row->reference_number ? $row->reference_number : '-';
                })
                ->addColumn('created_by', function ($row) {
                    return $row->createdBy->user_full_name ?? '-';
                })
                ->editColumn('dividend_date', function ($row) {
                    return $row->dividend_date ? \Carbon\Carbon::parse($row->dividend_date)->format('Y-m-d') : '-';
                })
                ->editColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->make(true);
        } catch (\Exception $e) {
            Log::error('DividendController getIssuedDividends - Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'msg' => 'Failed to retrieve issued dividends: ' . $e->getMessage(),
            ], 500);
        }
    }
}

