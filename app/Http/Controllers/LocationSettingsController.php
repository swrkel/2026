<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Printer;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\UserStorePermission;

use Illuminate\Http\Request;

class LocationSettingsController extends Controller
{
    /**
    * All class instance.
    *
    */
    protected $printReceiptOnInvoice;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->printReceiptOnInvoice = ['1' => 'Yes', '0' => 'No'];
        $this->receiptPrinterType = ['browser' => 'Browser Based Printing', 'printer' => 'Use Configured Receipt Printer'];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($location_id)
    {
        //Check for locations access permission
        $user = auth()->user();
        $business_id = request()->session()->get('user.business_id');
        $has_permission = false;

        if ($user->can('business_settings.access') && $user->can_access_this_location($location_id)) {
            $has_permission = true;
        } elseif (!empty($business_id) && !empty($user)) {
            $has_permission = UserStorePermission::join('stores', 'stores.id', '=', 'user_store_permissions.store_id')
                ->where('user_store_permissions.business_id', $business_id)
                ->where('user_store_permissions.user_id', $user->id)
                ->where('stores.location_id', $location_id)
                ->where('user_store_permissions.offline_sync_manage', 1)
                ->exists();
        }

        if (!$has_permission) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $location = BusinessLocation::where('business_id', $business_id)
                        ->findorfail($location_id);

        $printers = Printer::forDropdown($business_id);

        $printReceiptOnInvoice = $this->printReceiptOnInvoice;
        $receiptPrinterType = $this->receiptPrinterType;

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
                            ->get()
                            ->pluck('name', 'id');
        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
                            ->get()
                            ->pluck('name', 'id');

        return view('location_settings.index')
            ->with(compact('location', 'printReceiptOnInvoice', 'receiptPrinterType', 'printers', 'invoice_layouts', 'invoice_schemes'));
    }

    /**
     * Update the settings
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updateSettings($location_id, Request $request)
    {
        try {
            //Check for locations access permission
            $user = auth()->user();
            $business_id = request()->session()->get('user.business_id');
            $has_permission = false;

            if ($user->can('business_settings.access') && $user->can_access_this_location($location_id)) {
                $has_permission = true;
            } elseif (!empty($business_id) && !empty($user)) {
                $has_permission = UserStorePermission::join('stores', 'stores.id', '=', 'user_store_permissions.store_id')
                    ->where('user_store_permissions.business_id', $business_id)
                    ->where('user_store_permissions.user_id', $user->id)
                    ->where('stores.location_id', $location_id)
                    ->where('user_store_permissions.offline_sync_manage', 1)
                    ->exists();
            }

            if (!$has_permission) {
                abort(403, 'Unauthorized action.');
            }
            
            $input = $request->only(['print_receipt_on_invoice', 'receipt_printer_type', 'printer_id', 'invoice_layout_id', 'invoice_scheme_id']);
            // Only allow updating auto sync interval if user has permission
            $can_manage_interval = $has_permission;
            if ($can_manage_interval) {
                $interval = $request->input('auto_synchronization_intervals');
                if (!is_null($interval)) {
                    $interval = max(1, min(1440, (int)$interval));
                    $input['auto_synchronization_intervals'] = $interval;
                }
            }

            //Auto set to browser in demo.
            if (config('app.env') == 'demo') {
                $input['receipt_printer_type'] = 'browser';
            }

            $location = BusinessLocation::where('business_id', $business_id)
                            ->findorfail($location_id);

            $location->fill($input);
            $location->update();

            $output = ['success' => 1,
                        'msg' => __("receipt.receipt_settings_updated")
                    ];
        } catch (\Exception $e) {
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                throw $e;
            }
            $output = ['success' => 0,
                        'msg' => __("messages.something_went_wrong")
                    ];
        }

        return back()->with('status', $output);
    }

    public function getBusinessLocationSettings()
    {
        $business_id = request()->session()->get('user.business_id');
        $locationId = request()->get('location_id');

        $query = BusinessLocation::where('business_id', $business_id);
        if (!empty($locationId)) {
            $query->where('id', $locationId);
        }
        $businessLocation = $query->first();

        $interval = 5;
        if (!empty($businessLocation) && !empty($businessLocation->auto_synchronization_intervals)) {
            $interval = (int) $businessLocation->auto_synchronization_intervals;
        }

        return response()->json([
            'auto_synchronization_intervals' => $interval
        ]);
    }
}
