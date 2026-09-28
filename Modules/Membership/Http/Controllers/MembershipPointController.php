<?php
namespace Modules\Membership\Http\Controllers;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\Contact;
use App\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Membership\Entities\MembershipBusinessType;
use Modules\Membership\Entities\MembershipMember;
use Modules\Membership\Entities\MembershipPoint;
use Modules\Membership\Entities\MembershipPointSetting;
use Modules\Membership\Entities\MembershipBusinessName;
use Yajra\DataTables\Facades\DataTables;
use App\Services\MembershipPointBalanceCalculator;

class MembershipPointController extends Controller
{
    protected $businessUtil;

    public function __construct(
    ) {
    }

    public function getListPoints(Request $request)
    {

        $business_id = request()->session()->get('user.business_id');
        if ($request->ajax()) {
            $memberPoints = MembershipPoint::where('membership_points.business_id', $business_id)
                ->leftJoin('membership_members', 'membership_points.member_id', '=', 'membership_members.id')
                ->leftJoin('contacts', function($join) use ($business_id) {
                    $join->on('membership_points.member_id', '=', 'contacts.id')
                         ->where('contacts.business_id', '=', $business_id)
                         ->where('contacts.type', '=', 'customer');
                })
                ->leftJoin('membership_business_types', 'membership_points.business_type_id', '=', 'membership_business_types.id')
                ->leftJoin('users', 'membership_points.created_by', '=', 'users.id')
                ->select(
                    'membership_points.id',
                    'membership_points.date',
                    'membership_points.form_number as add_point_form_no',
                    DB::raw('COALESCE(membership_members.member_name, contacts.name) as member_name'),
                    DB::raw('COALESCE(membership_members.member_number, contacts.contact_id, CAST(contacts.id AS CHAR)) as member_code'),
                    'membership_business_types.business_type',
                    'membership_points.bill_number',
                    'membership_points.business_name',
                    'membership_points.amount',
                    DB::raw('(membership_points.point_balance - membership_points.earned_points + membership_points.redeemed_points) as current_points'),
                    'membership_points.earned_points',
                    'membership_points.redeemed_points',
                    'membership_points.point_balance',
                    'membership_points.payment_details',
                    'users.username as added_by'
                )->get()
                ->map(function ($item) {
                    // Format payment details
                    if ($item->payment_details) {
                        if (is_array($item->payment_details)) {
                            $item->payment_details = implode(', ', $item->payment_details);
                        } elseif (is_string($item->payment_details)) {
                            $decoded = json_decode($item->payment_details, true);
                            if (is_array($decoded)) {
                                $item->payment_details = implode(', ', $decoded);
                            } else {
                                $item->payment_details = $item->payment_details;
                            }
                        }
                    } else {
                        $item->payment_details = '-';
                    }
                    return $item;
                });

            return DataTables::of($memberPoints)
               ->addColumn('action', function ($row) {
                    $html = '<button data-href="' . action(
                        '\Modules\Membership\Http\Controllers\MembershipPointController@viewPoint',
                        [$row->id]
                    ) . '" data-container=".point_activity_modal" class="btn btn-xs btn-info btn-modal">
                        <i class="glyphicon glyphicon-eye-open"></i> ' . __("messages.view") . '
                    </button> ';
                    
                    $html .= '<button data-href="' . action(
                        '\Modules\Membership\Http\Controllers\MembershipPointController@editPointForm',
                        [$row->id]
                    ) . '" data-container=".point_activity_modal" class="btn btn-xs btn-primary btn-modal">
                        <i class="glyphicon glyphicon-edit"></i> ' . __("messages.edit") . '
                    </button> ';
                    
                    $deleteUrl = url('/membership/delete-point/' . $row->id);
                    $html .= '<button data-href="' . $deleteUrl . '" class="btn btn-xs btn-danger delete_point_activity_btn">
                        <i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '
                    </button>';
                    
                    return $html;
                })

                ->rawColumns(['action'])
                ->make(true);
        }
        return view('membership::partials.list_points');
    }

    // public function addPointForm()
    // {
    //     $business_id             = request()->session()->get('user.business_id');
    //     $membershipMembers       = MembershipMember::where('business_id', $business_id)->get(['id', 'member_name', 'member_number']);
    //     $membershipBusinessTypes = MembershipBusinessType::where('business_id', $business_id)
    //         ->orWhere('business_id', 0)
    //         ->pluck('business_type', 'id');
    //     $formNumber    = MembershipPoint::where('business_id', $business_id)->count() + 1;
    //     $currentPoints = MembershipPoint::where('business_id', $business_id)
    //         ->orderBy('date', 'desc')
    //         ->orderBy('form_number', 'desc')
    //         ->value('point_balance') ?? 0;
    //     $business_details   = Business::find($business_id);
    //     $currency_precision = ! empty($business_details->currency_precision) ? $business_details->currency_precision : 2;

    //     return view('membership::partials.add_point',
    //         compact(
    //             'membershipMembers',
    //             'membershipBusinessTypes',
    //             'formNumber',
    //             'currentPoints',
    //             'currency_precision'
    //         )
    //     );
    // }

    public function addPointForm()
    {
        if (!auth()->check()) {
            abort(403);
        }

        $business_id = request()->session()->get('user.business_id');
        $today       = now()->toDateString();

        /* -------------------------------
        Members (Membership + Customers)
        -------------------------------- */
        $membershipMembers = MembershipMember::where('business_id', $business_id)
            ->select('id', 'member_name as name', 'member_number')
            ->get()
            ->map(function($member) {
                $member->member_number = $member->member_number ?? '';
                return $member;
            });

        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->select('id', 'name', 'contact_id as member_number')
            ->get()
            ->map(function($contact) {
                $contact->member_number = $contact->member_number ?? $contact->id ?? '';
                return $contact;
            });

        $allMembers = $membershipMembers->concat($customers);

        /* -------------------------------
        Business Types
        -------------------------------- */
        $membershipBusinessTypes = MembershipBusinessType::where('business_id', $business_id)
            ->orWhere('business_id', 0)
            ->pluck('business_type', 'id');

        /* -------------------------------
        DAILY Form Number (Task d)
        -------------------------------- */
        $formNumber = MembershipPoint::where('business_id', $business_id)
            ->whereDate('date', $today)
            ->max('form_number');

        $formNumber = $formNumber ? $formNumber + 1 : 1;

        /* -------------------------------
        Currency Precision
        -------------------------------- */
        $business_details   = Business::find($business_id);
        $currency_precision = !empty($business_details->currency_precision)
            ? $business_details->currency_precision
            : 2;

        /* -------------------------------
        Auto-loaded Sales (Task n)
        -------------------------------- */
        $sales = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->whereDate('transaction_date', $today)
            ->get();

        return view('membership::partials.add_point', compact(
            'allMembers',
            'membershipBusinessTypes',
            'formNumber',
            'currency_precision',
            'sales'
        ));
    }

    public function editPointForm($id)
    {

        Log::info("Editing Membership Point ID: $id");
        $business_id = request()->session()->get('user.business_id');

        $point = MembershipPoint::where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        $calculator = new MembershipPointBalanceCalculator();
        $currentBalance = $calculator->calculateCurrentBalance(
            $point->member_id, 
            $business_id, 
            $id
        );

        $membershipMembers = MembershipMember::where('business_id', $business_id)
            ->select('id', 'member_name as name', 'member_number')
            ->get()
            ->map(function($member) {
                $member->member_number = $member->member_number ?? '';
                return $member;
            });

        $customers = Contact::where('business_id', $business_id)
            ->where('type', 'customer')
            ->select('id', 'name', 'contact_id as member_number')
            ->get()
            ->map(function($contact) {
                $contact->member_number = $contact->member_number ?? $contact->id ?? '';
                return $contact;
            });

        $allMembers = $membershipMembers->concat($customers);

        $currentMemberNumber = '';
        $membershipMember = MembershipMember::where('id', $point->member_id)->first();
        if ($membershipMember) {
            $currentMemberNumber = $membershipMember->member_number ?? '';
        } else {
            $contact = Contact::where('id', $point->member_id)
                ->where('business_id', $business_id)
                ->where('type', 'customer')
                ->first();
            if ($contact) {
                $currentMemberNumber = $contact->contact_id ?? $contact->id ?? '';
            }
        }

        $membershipBusinessTypes = MembershipBusinessType::where('business_id', $business_id)
            ->orWhere('business_id', 0)
            ->pluck('business_type', 'id');

        $businessNames = [];
        $selectedBusinessNameId = null;
        if ($point->business_type_id) {
            $businessNames = MembershipBusinessName::where('business_id', $business_id)
                ->where('membership_business_type_id', $point->business_type_id)
                ->select('id', 'business_name')
                ->get();
            
            if ($point->business_name) {
                $selectedBusinessName = $businessNames->firstWhere('business_name', $point->business_name);
                if ($selectedBusinessName) {
                    $selectedBusinessNameId = $selectedBusinessName->id;
                }
            }
        }

        $business_details   = Business::find($business_id);
        $currency_precision = $business_details->currency_precision ?? 2;

        return view(
            'membership::partials.edit_point',
            compact(
                'point',
                'allMembers',
                'membershipBusinessTypes',
                'currency_precision',
                'currentMemberNumber',
                'businessNames',
                'selectedBusinessNameId',
                'currentBalance'
            )
        );
    }

    public function updatePoint(Request $request, $id)
    {

        $business_id = $request->session()->get('user.business_id');

        $point = MembershipPoint::where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        $request->validate([
            'member_id' => 'required',
            'amount'    => 'required',
        ]);

        DB::beginTransaction();
        try {
            $currentPoints  = (float) str_replace(',', '', $request->input('current_points'));
            $earnedPoints   = $request->input('earned_points', 0);
            $redeemedPoints = $request->input('redeemed_points', 0);
            $pointBalance   = $currentPoints + $earnedPoints - $redeemedPoints;

            // Get business name text from business_name_id
            $businessName = $request->input('business_name');
            if ($request->input('business_name_id')) {
                $businessNameRecord = MembershipBusinessName::find($request->input('business_name_id'));
                $businessName = $businessNameRecord ? $businessNameRecord->business_name : $request->input('business_name');
            }

            $point->update([
                'date'             => \Carbon::parse($request->input('date'))->format('Y-m-d'),
                'member_id'        => $request->input('member_id'),
                'business_type_id' => $request->input('membership_business_type_id'),
                'bill_number'      => $request->input('bill_number'),
                'amount'           => $request->input('amount'),
                'earned_points'    => $earnedPoints,
                'redeemed_points'  => $redeemedPoints,
                'point_balance'    => $pointBalance,
                'payment_details'  => $request->input('payment_details')
                    ? json_encode($request->input('payment_details'))
                    : null,
                'business_name'    => $businessName,
            ]);

            DB::commit();
            return redirect()
                ->action([MembershipPointController::class, 'getListPoints'])
                ->with('status', __('membership::lang.membership_point_updated_successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:{$e->getFile()} Line:{$e->getLine()} Message:{$e->getMessage()}");
            return redirect()->back()->with('status', __('messages.something_went_wrong'));
        }
    }

    public function addPoint(Request $request)
    {

        $request->validate([
            'member_id' => 'required',
            'amount'    => 'required',
        ]);

        DB::beginTransaction();

        try {
            $currentPoints    = (float) str_replace(',', '', $request->input('current_points'));
            $earnedPoints     = $request->input('earned_points', 0);
            $redeemedPoints   = $request->input('redeemed_points', 0);
            $pointBalance     = $currentPoints + $earnedPoints - $redeemedPoints;
            $business_id      = $request->session()->get('user.business_id');
            $date             = \Carbon::parse($request->input('date'))->format('Y-m-d');
            
            // Check if member_id is from MembershipMember or Contact
            $membershipMember = MembershipMember::where('id', $request->input('member_id'))->first();
            $contact_id = null;
            
            if ($membershipMember) {
                $contact_id = $membershipMember->contact_id;
            } else {
                // Check if it's a Contact directly
                $contact = Contact::where('id', $request->input('member_id'))
                    ->where('business_id', $business_id)
                    ->where('type', 'customer')
                    ->first();
                if ($contact) {
                    $contact_id = $contact->id;
                }
            }

            // Get business name text from business_name_id
            $businessName = null;
            if ($request->input('business_name_id')) {
                $businessNameRecord = MembershipBusinessName::find($request->input('business_name_id'));
                $businessName = $businessNameRecord ? $businessNameRecord->business_name : null;
            }

            $membershipPoint = MembershipPoint::create([
                'form_number'      => $request->input('form_number'),
                'date'             => $date,
                'member_id'        => $request->input('member_id'),
                'business_type_id' => $request->input('membership_business_type_id'),
                'business_id'      => $request->session()->get('user.business_id'),
                'bill_number'      => $request->input('bill_number'),
                'amount'           => $request->input('amount'),
                'earned_points'    => $earnedPoints,
                'redeemed_points'  => $redeemedPoints,
                'point_balance'    => $pointBalance,
                'payment_details'  => $request->input('payment_details') ? json_encode($request->input('payment_details')) : null,
                'bank_name'        => $request->input('bank_name'),
                'cheque_number'    => $request->input('cheque_number'),
                'cheque_date'      => $request->input('cheque_date'),
                'business_name'    => $businessName,
                'created_by'       => Auth::user()->id,
            ]);

            \Log::info("MembershipPointController addPoint - Created point", [
                'point_id' => $membershipPoint->id,
                'member_id' => $request->input('member_id'),
                'business_id' => $business_id,
                'point_balance' => $pointBalance,
                'earned_points' => $earnedPoints,
                'redeemed_points' => $redeemedPoints,
                'tenant_id' => tenant() ? tenant()->id : 'none'
            ]);

            // Only create transaction if we have a contact_id
            if ($contact_id) {
                $transaction = Transaction::create(
                    [
                        'type'             => 'points_redeemed',
                        'status'           => 'final',
                        'business_id'      => $business_id,
                        'transaction_date' => $date,
                        'amount'           => $request->input('amount'),
                        'payment_status'   => 'paid',
                        'contact_id'       => $contact_id,
                        'ref_no'           => $request->input('bill_number'),
                        'created_by'       => Auth::user()->id,
                        'rp_earned'        => $earnedPoints,
                        'rp_redeemed'      => $redeemedPoints,
                        'rp_point_id'      => $membershipPoint->id,
                    ]
                );
            } else {
                $transaction = null;
            }

            // Only create account transaction if we have a transaction
            if ($transaction) {
                $pointAccount = Account::where('business_id', $business_id)
                    ->where('name', 'Points Redeemed')
                    ->first();
                if ($pointAccount) {
                    $acc_tran = [
                        'account_id'     => $pointAccount->id,
                        'type'           => 'debit',
                        'business_id'    => $business_id,
                        'amount'         => $earnedPoints - $redeemedPoints,
                        'operation_date' => $date,
                        'created_by'     => Auth::user()->id,
                        'traction_id'    => $transaction->id,
                    ];
                    AccountTransaction::create($acc_tran);
                }
            }
            DB::commit();
            return redirect()->action([MembershipPointController::class, 'getListPoints'])->with('status', __('membership::lang.membership_point_added_successfully'));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return redirect()->back()->with('status', 'Error: ' . $e->getMessage());
        }
    }

    // public function getRewardPercent(Request $request)
    // {
    //     $businessTypeId = $request->membership_business_type_id;

    //     $setting = MembershipPointSetting::where('membership_business_type_id', $businessTypeId)
    //         ->first();

    //     return response()->json([
    //         'reward_point_percent' => $setting->reward_point_percent ?? 0,
    //     ]);
    // }

    public function getMemberPoints(Request $request)
    {

        $business_id = $request->session()->get('user.business_id');
        $member_id   = $request->member_id;

        if (!$member_id) {
            return response()->json(['points' => 0]);
        }

        $points = MembershipPoint::where('business_id', $business_id)
            ->where('member_id', $member_id)
            ->orderBy('date', 'desc')
            ->orderBy('form_number', 'desc')
            ->value('point_balance');

        \Log::info("MembershipPointController getMemberPoints", [
            'business_id' => $business_id,
            'member_id' => $member_id,
            'points' => $points ?? 0,
            'tenant_id' => tenant() ? tenant()->id : 'none'
        ]);

        return response()->json([
            'points' => $points ?? 0
        ]);
    }

    public function getMemberBills(Request $request)
    {

        $business_id = $request->session()->get('user.business_id');
        $member_id   = $request->member_id;

        if (!$member_id) {
            return response()->json([]);
        }

        $contact_id = null;

        // Check if member_id is from Contact table directly
        $contact = Contact::where('business_id', $business_id)
            ->where('id', $member_id)
            ->where('type', 'customer')
            ->first();

        if ($contact) {
            $contact_id = $contact->id;
        } else {
            // Check if it's from MembershipMember table
            $membershipMember = MembershipMember::where('business_id', $business_id)
                ->where('id', $member_id)
                ->first();
            
            if ($membershipMember && $membershipMember->contact_id) {
                $contact_id = $membershipMember->contact_id;
            }
        }

        if (!$contact_id) {
            return response()->json([]);
        }

        $transactions = Transaction::where('business_id', $business_id)
            ->where('contact_id', $contact_id)
            ->where('type', 'sell')
            ->orderBy('transaction_date', 'desc')
            ->get(['invoice_no', 'final_total']);

        return response()->json(
            $transactions->map(function ($t) {
                return [
                    'invoice_no' => $t->invoice_no,
                    'final_total' => $t->final_total
                ];
            })
        );
    }

    public function getRewardPercent(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $type_id     = $request->membership_business_type_id;

        \Log::info('getRewardPercent called', [
            'business_id' => $business_id,
            'type_id' => $type_id
        ]);

        if (!$type_id) {
            return response()->json([
                'reward_point_percent' => 0,
                'message' => 'No business type ID provided'
            ]);
        }

        // Get reward percent from MembershipPointSetting
        $pointSetting = MembershipPointSetting::where('business_id', $business_id)
            ->where('membership_business_type_id', $type_id)
            ->first();

        \Log::info('Point Setting result', [
            'found' => $pointSetting ? 'yes' : 'no',
            'setting' => $pointSetting
        ]);

        return response()->json([
            'reward_point_percent' => $pointSetting->reward_point_percent ?? 0,
            'point_setting_exists' => $pointSetting ? true : false,
            'business_type_id' => $type_id,
            'message' => $pointSetting ? 'Point setting found' : 'No point setting found for this business type. Please create one in Membership Settings → Point Settings tab.'
        ]);
    }

    public function viewPoint($id)
    {

        $business_id = request()->session()->get('user.business_id');
        $point = MembershipPoint::where('id', $id)
            ->where('business_id', $business_id)
            ->firstOrFail();

        // Get member details - try both membership_members and contacts
        $member = null;
        if ($point->member_id) {
            // First try membership_members
            $member = MembershipMember::where('id', $point->member_id)->first();
            // If not found, try contacts
            if (!$member) {
                $member = \App\Contact::where('id', $point->member_id)
                    ->where('business_id', $business_id)
                    ->where('type', 'customer')
                    ->first();
            }
        }

        // Get business type
        $businessType = null;
        if ($point->business_type_id) {
            $businessType = MembershipBusinessType::find($point->business_type_id);
        }

        // Business name is stored directly in the point record, not as a foreign key
        $businessName = $point->business_name;

        // Get created by user
        $createdBy = null;
        if ($point->created_by) {
            $createdBy = \App\User::find($point->created_by);
        }

        return view('membership::partials.point_activity_view', compact('point', 'member', 'businessType', 'businessName', 'createdBy'));
    }

    public function destroyPoint($id)
    {

        $business_id = request()->session()->get('user.business_id');
        
        try {
            $point = MembershipPoint::where('id', $id)
                ->where('business_id', $business_id)
                ->firstOrFail();

            // Delete associated transaction if exists
            $transaction = Transaction::where('rp_point_id', $point->id)->first();
            if ($transaction) {
                // Delete associated account transaction if exists
                AccountTransaction::where('transaction_id', $transaction->id)->delete();
                $transaction->delete();
            }

            $point->delete();

            return response()->json([
                'success' => true,
                'msg' => __('membership::lang.point_activity_deleted_successfully')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting point activity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    public function getBusinessNamesByType(Request $request)
    {

        $business_id = $request->session()->get('user.business_id');
        $type_id     = $request->membership_business_type_id;

        \Log::info("MembershipPointController getBusinessNamesByType", [
            'business_id' => $business_id,
            'type_id' => $type_id,
            'tenant_id' => tenant() ? tenant()->id : 'none'
        ]);

        if (!$type_id) {
            return response()->json([]);
        }

        $businessNames = MembershipBusinessName::where('business_id', $business_id)
            ->where('membership_business_type_id', $type_id)
            ->select('id', 'business_name')
            ->get();

        \Log::info("MembershipPointController getBusinessNamesByType - Found business names", [
            'count' => $businessNames->count(),
            'business_names' => $businessNames->toArray()
        ]);

        return response()->json($businessNames);
    }

    public function getPointBalanceForEdit(Request $request)
    {
        try {
            $business_id = $request->session()->get('user.business_id');
            $member_id = $request->member_id;
            $exclude_point_id = $request->exclude_point_id;
            $redeemed_points = $request->redeemed_points ?? 0;

            if (!$member_id) {
                return response()->json([
                    'balance' => 0,
                    'error' => 'Member ID is required'
                ], 400);
            }

            if (!$business_id) {
                return response()->json([
                    'balance' => 0,
                    'error' => 'Business ID not found in session'
                ], 400);
            }

            $calculator = new MembershipPointBalanceCalculator();
            $balance = $calculator->calculateCurrentBalance($member_id, $business_id, $exclude_point_id);
            
            $updatedBalance = $balance - $redeemed_points;

            return response()->json([
                'balance' => number_format($updatedBalance, 2, '.', ''),
                'success' => true
            ]);
            
        } catch (\InvalidArgumentException $e) {
            \Log::warning('Invalid argument in getPointBalanceForEdit', [
                'error' => $e->getMessage(),
                'member_id' => $request->member_id ?? null,
                'business_id' => $request->session()->get('user.business_id') ?? null
            ]);
            
            return response()->json([
                'balance' => 0,
                'error' => 'Invalid input parameters',
                'success' => false
            ], 400);
            
        } catch (\Exception $e) {
            \Log::error('Error in getPointBalanceForEdit', [
                'error' => $e->getMessage(),
                'member_id' => $request->member_id ?? null,
                'business_id' => $request->session()->get('user.business_id') ?? null,
                'exclude_point_id' => $request->exclude_point_id ?? null
            ]);
            
            return response()->json([
                'balance' => 0,
                'error' => 'An error occurred while calculating balance',
                'success' => false
            ], 500);
        }
    }
}