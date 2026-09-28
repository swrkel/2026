<?php

namespace Modules\MPCS\Http\Controllers;

use App\BusinessLocation;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\User;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;
use Modules\MPCS\Entities\F10FormHeader;
use Modules\MPCS\Entities\F10FormDetail;
use Modules\MPCS\Entities\F10OpeningNumber;
use Modules\MPCS\Entities\F10Manager;

class F10FormController extends Controller
{
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;

    public function __construct(ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    public function index(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('f10_form')) {
            abort(403, 'Unauthorized action.');
        }

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $default_location = is_array($business_locations) ? key($business_locations) : current(array_keys($business_locations->toArray()));

        $settings = MpcsFormSetting::where('business_id', $business_id)->first();
        $opening_numbers = F10OpeningNumber::where('business_id', $business_id)->first();
        $managers = F10Manager::where('business_id', $business_id)->get();

        /*
         * MA-002 (IS-1915): the LOGGED-IN user's name, for the
         * "Received ... from Mr./Mrs./Miss ___" line.
         *
         * Passed as a default so the line fills itself for whoever is signed
         * in, rather than waiting for a manager to be picked from a dropdown.
         *
         * A NOTE ON "only the Manager may enter values":
         * mpcs_f10_managers has no user_id column - a manager there is a NAME,
         * not a linked user account. So there is no reliable way to tell from
         * the logged-in user whether they are one of those managers. See the
         * parcel notes; that part needs a decision before it can be built.
         */
        $loggedInUserName = trim(
            (string) (auth()->user()->first_name ?? '') . ' ' . (string) (auth()->user()->last_name ?? '')
        );

        if ($loggedInUserName === '') {
            $loggedInUserName = (string) (auth()->user()->username ?? '');
        }
        $cashiers = User::whereIn('id', DB::table('mpcs_f10_headers') ->select('created_by') ->distinct())->pluck('username', 'id');
        $f10_numbers = \Modules\MPCS\Entities\F10FormHeader::where('business_id', $business_id)->pluck('form_no')->unique();
        
        // Get current F10 number
        $last_form = F10FormHeader::where('business_id', $business_id)
                        ->orderBy('id', 'desc')
                        ->first();
        
        if ($last_form) {
            $current_f10_number = intval($last_form->form_no) + 1;
        } else {
            // first receipt -> start from opening number
            $opening = F10OpeningNumber::where('business_id', $business_id)->first();
            $current_f10_number = $opening ? $opening->f10_number : 1;
        }

        return view('mpcs::forms.f10_form')->with(compact(
            'business_locations',
            'default_location',
            'settings',
            'opening_numbers',
            'managers',
            'cashiers',
            'f10_numbers',
            'current_f10_number',
            'loggedInUserName'
        ));
    }

    public function storeReceipt(Request $request)
{
    try {

        $business_id = request()->session()->get('user.business_id');
        $user_id = auth()->user()->id;

        DB::beginTransaction();

        // Get last F10 number
        $last_form = F10FormHeader::where('business_id', $business_id)
                        ->orderBy('id', 'desc')
                        ->first();

        if ($last_form) {
            $next_f10_number = intval($last_form->form_no) + 1;
        } else {
            // first receipt -> start from opening number
            $opening = F10OpeningNumber::where('business_id', $business_id)->first();
            $next_f10_number = $opening ? $opening->f10_number : 1;
        }

        /*
         * IS2015: honour the date chosen on the form.
         *
         * form_date was hardcoded to now(), so a receipt could only ever carry
         * the moment it was saved. The field is now a picker (see
         * f10_form.blade.php) and its value arrives as d/m/Y H:i.
         *
         * Parsed strictly against that format first. A value that cannot be read
         * falls back to now() rather than failing the save - losing the receipt
         * over a malformed date would be worse than dating it today, and the
         * picker makes a malformed value unlikely in the first place.
         */
        $form_date = now();

        if (! empty($request->form_date)) {
            try {
                /*
                 * IS2037: the field is now a native datetime-local input, which
                 * posts Y-m-d\TH:i. Tried first so the common case is exact
                 * rather than relying on the loose parse below; the older
                 * d/m/Y H:i form is still accepted for any request in flight
                 * from a cached page.
                 */
                $raw = trim($request->form_date);

                $form_date = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $raw);
            } catch (\Throwable $e) {
                try {
                    $form_date = \Carbon\Carbon::createFromFormat('d/m/Y H:i', trim($request->form_date));
                } catch (\Throwable $e2) {
                    try {
                        $form_date = \Carbon\Carbon::parse(trim($request->form_date));
                    } catch (\Throwable $e3) {
                        $form_date = now();
                    }
                }
            }
        }

        // Save receipt
        $receipt = F10FormHeader::create([
            'business_id' => $business_id,
            'location_id' => $request->location_id,
            'manager_id' => $request->manager_id,
            'form_no' => $next_f10_number,
            'form_date' => $form_date,
            'status' => 'active',
            'total_amount' => $request->total_amount ? (float)str_replace(',', '', $request->total_amount) : 0,
            'cash_amount' => $request->cash_amount ? (float)str_replace(',', '', $request->cash_amount) : 0,
            'bank_amount' => $request->bank_amount ? (float)str_replace(',', '', $request->bank_amount) : 0,
            'cheque_amount' => $request->cheque_amount ? (float)str_replace(',', '', $request->cheque_amount) : 0,
            'card_amount' => $request->card_amount ? (float)str_replace(',', '', $request->card_amount) : 0,
            'created_by' => $user_id
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'msg' => 'Receipt saved successfully',
            // MA-002 (IS-1915 #2): the id, so the caller can open the printable
            // receipt. form_no is not unique enough to address one row.
            'id' => $receipt->id ?? null,
            'form_no' => $next_f10_number
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        Log::emergency(
            "File:" . $e->getFile() .
            " Line:" . $e->getLine() .
            " Message:" . $e->getMessage()
        );

        return response()->json([
            'success' => false,
            'msg' => 'Something went wrong'
        ]);
    }
}

    /**
     * MA-002 (IS-1915 #1): the endpoint the F10 form calls on load.
     *
     * f10_form.blade.php has always requested it:
     *
     *     url: '/mpcs/F10/get-current-f10-number'
     *
     * but no route or method existed, so every visit to the form produced
     *
     *     Failed. The route mpcs/F10/get-current-f10-number could not be found
     *
     * The NUMBER itself was never missing - index() works it out and passes
     * $current_f10_number to the view. Only the ajax refresh was absent, so
     * the form showed the number it was rendered with and then reported a
     * failure trying to confirm it.
     *
     * This reuses exactly the same calculation as index() rather than writing
     * a second one, so the two can never disagree about the next number.
     *
     * Returns { success, current_f10_number } - the shape the view already
     * expects:
     *     if (result.success && result.current_f10_number) { ... }
     */
    public function getCurrentF10Number()
    {
        $business_id = request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id');

        if (empty($business_id)) {
            return response()->json([
                'success' => false,
                'msg' => 'The business session is not available.',
            ]);
        }

        $last_form = F10FormHeader::where('business_id', $business_id)
            ->orderBy('id', 'desc')
            ->first();

        if ($last_form) {
            $current_f10_number = intval($last_form->form_no) + 1;
        } else {
            // First receipt - start from the configured opening number.
            $opening = F10OpeningNumber::where('business_id', $business_id)->first();
            $current_f10_number = $opening ? $opening->f10_number : 1;
        }

        return response()->json([
            'success' => true,
            'current_f10_number' => $current_f10_number,
        ]);
    }

    /**
     * MA-002 (IS-1915 #2): print one F10 receipt as a document.
     *
     * The form previously called window.print() on itself, so the printout was
     * the data-entry screen - input boxes and buttons included. This renders a
     * standalone receipt instead, with the four amount breakdowns.
     */
    public function printReceipt($id)
    {
        $business_id = request()->session()->get('user.business_id')
            ?: request()->session()->get('business.id');

        $header = F10FormHeader::where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        $business = DB::table('business')->where('id', $business_id)->first();

        $location = ! empty($header->location_id)
            ? DB::table('business_locations')->where('id', $header->location_id)->first()
            : null;

        $manager = ! empty($header->manager_id)
            ? F10Manager::where('id', $header->manager_id)->first()
            : null;

        $preparedBy = ! empty($header->created_by)
            ? DB::table('users')->where('id', $header->created_by)->value('username')
            : null;

        return view('mpcs::forms.partials.print_f10_receipt', compact(
            'header',
            'business',
            'location',
            'manager',
            'preparedBy'
        ));
    }

    public function storeOpeningNumbers(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('f10_form')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $existing = F10OpeningNumber::where('business_id', $business_id)->first();
            if ($existing) {
                return redirect()->back()
                    ->with('active_f10_tab', 'f10_opening_numbers_tab')
                    ->with('status', ['success' => 0, 'msg' => 'Opening numbers already exist and cannot be edited.']);
            }

            $opening_date = $this->parseOpeningDate($request->opening_date);

            F10OpeningNumber::create([
                'business_id' => $business_id,
                'opening_date' => $opening_date,
                'f10_number' => $request->f10_number,
                'document_no' => $request->document_no,
                'currency_prefix' => $request->currency_prefix,
                'created_by' => auth()->user()->id
            ]);

            return redirect()->back()
                ->with('active_f10_tab', 'f10_opening_numbers_tab')
                ->with('status', ['success' => 1, 'msg' => 'Opening Numbers saved successfully.']);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Keep F10 opening date display reliable after save.
     * The date-picker may submit different formats depending on business settings,
     * so store a normalized Y-m-d value instead of allowing a null/invalid date.
     */
    private function parseOpeningDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            $parsed_date = $this->productUtil->uf_date($date);
            if (!empty($parsed_date)) {
                return $parsed_date;
            }
        } catch (\Exception $e) {
            // fallback below
        }

        foreach (['d/m/Y', 'm/d/Y', 'Y-m-d', 'd-m-Y', 'm-d-Y'] as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                // try next format
            }
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function storeManager(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('f10_form')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $request->validate(['manager_name' => 'required|string|max:255']);

            // Split comma-separated manager names and save as individual records
            $managerNames = array_map('trim', explode(',', $request->manager_name));
            $savedCount = 0;
            $duplicateCount = 0;

            foreach ($managerNames as $managerName) {
                // Skip empty names
                if (empty($managerName)) {
                    continue;
                }

                // Check if manager already exists for this business
                $existingManager = F10Manager::where('business_id', $business_id)
                    ->where('manager_name', $managerName)
                    ->first();

                if (!$existingManager) {
                    F10Manager::create([
                        'business_id' => $business_id,
                        'manager_name' => $managerName,
                        'status' => 'Active',
                        'created_by' => auth()->user()->id
                    ]);
                    $savedCount++;
                } else {
                    $duplicateCount++;
                }
            }

            $message = "Manager(s) saved successfully.";
            if ($savedCount > 0) {
                $message .= " Added: {$savedCount} new manager(s).";
            }
            if ($duplicateCount > 0) {
                $message .= " Skipped: {$duplicateCount} duplicate manager(s).";
            }

            return redirect()->back()->with('status', ['success' => 1, 'msg' => $message]);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function toggleManagerStatus($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!auth()->user()->can('f10_form')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $manager = F10Manager::where('business_id', $business_id)->findOrFail($id);
            $manager->status = $manager->status == 'Active' ? 'Inactive' : 'Active';
            $manager->save();

            return redirect()->back()->with('status', ['success' => 1, 'msg' => 'Manager status updated successfully.']);
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    public function getF10FormList(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if ($request->ajax()) {
            $header = F10FormHeader::where('mpcs_f10_headers.business_id', $business_id)
                ->leftJoin('business_locations', 'mpcs_f10_headers.location_id', '=', 'business_locations.id')
                ->leftJoin('mpcs_f10_managers', 'mpcs_f10_headers.manager_id', '=', 'mpcs_f10_managers.id')
                ->leftJoin('users', 'mpcs_f10_headers.created_by', '=', 'users.id')
                ->select('mpcs_f10_headers.*', 'business_locations.name as location_name', 'mpcs_f10_managers.manager_name', 'users.username as added_by');

            if (!empty($request->start_date) && !empty($request->end_date)) {
                $header->whereDate('mpcs_f10_headers.form_date', '>=', $request->start_date)
                       ->whereDate('mpcs_f10_headers.form_date', '<=', $request->end_date);
            }

            if (!empty($request->location_id)) {
                $header->where('mpcs_f10_headers.location_id', $request->location_id);
            }

            if (!empty($request->cashier_id)) {
                $header->where('mpcs_f10_headers.created_by', $request->cashier_id);
            }

            if (!empty($request->form_no)) {
                $header->where('mpcs_f10_headers.form_no', $request->form_no);
            }

            if (!empty($request->manager_id)) {
                $header->where('mpcs_f10_headers.manager_id', $request->manager_id);
            }


            return Datatables::of($header)
                ->addIndexColumn()
                ->removeColumn('id')
                ->addColumn('action', function ($row) {
                    /*
                     * IS2015: View and Print are BOTH rendered here already - the
                     * reported "nothing appears" was the menu being CLIPPED by
                     * the scrollable table wrapper, not a missing item. The
                     * f10-action-group / f10-action-menu classes are the hooks
                     * f10_list_receipts.blade.php uses to lift the open menu out
                     * to <body> so no ancestor overflow can cut it off.
                     */
                    return '<div class="btn-group f10-action-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown">
                            ' . __("messages.actions") . ' <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left f10-action-menu">
                            <li><a href="#" class="view_f10_form" data-id="' . $row->id . '"><i class="fa fa-eye"></i> ' . __("messages.view") . '</a></li>
                            <li><a href="#" class="print_f10_form" data-id="' . $row->id . '"><i class="fa fa-print"></i> ' . __("messages.print") . '</a></li>
                        </ul>
                    </div>';
                })
                ->editColumn('form_date', function ($row) {
                    return $this->productUtil->format_date($row->form_date, false);
                })
                ->rawColumns(['action'])
                ->make(true);
        }
    }

    public function getF10FormDetails($id)
    {
        $business_id = request()->session()->get('user.business_id');

        try {
            $header = F10FormHeader::where('mpcs_f10_headers.business_id', $business_id)
                ->where('mpcs_f10_headers.id', $id)
                ->leftJoin('business_locations', 'mpcs_f10_headers.location_id', '=', 'business_locations.id')
                ->leftJoin('mpcs_f10_managers', 'mpcs_f10_headers.manager_id', '=', 'mpcs_f10_managers.id')
                ->leftJoin('users', 'mpcs_f10_headers.created_by', '=', 'users.id')
                ->select(
                    'mpcs_f10_headers.*', 
                    'business_locations.name as location_name', 
                    'mpcs_f10_managers.manager_name',
                    'users.username as added_by'
                )
                ->first();

            if (!$header) {
                return response()->json(['success' => false, 'msg' => 'Form not found']);
            }

            $details = F10FormDetail::where('header_id', $id)->get();

            return response()->json([
                'success' => true,
                'header' => $header,
                'details' => $details
            ]);

        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'Something went wrong']);
        }
    }
}
