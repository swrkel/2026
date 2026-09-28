<?php
namespace Modules\Vat\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatSetting;
use Modules\Vat\Services\VatCustomerListService;
use Modules\Vat\Services\VatTaxLedgerService;

class VatReportController extends Controller
{
    protected $commonUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $businessUtil;
    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    /*
     | S-667: ContactUtil removed from this controller entirely.
     |
     | Its only use here was the two tax-ledger calls, now served by
     | VatTaxLedgerService inside this module. Leaving the parameter in place
     | would have kept the module coupled to App\Utils for no reason, and the
     | next person reading the constructor would reasonably assume it was needed.
     */
    public function __construct(Util $commonUtil, BusinessUtil $businessUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil, protected VatTaxLedgerService $taxLedger, protected VatCustomerListService $customerList)
    {

        $this->commonUtil      = $commonUtil;
        $this->productUtil     = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil    = $businessUtil;

    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        /*
         | S-667: the module's own customer list.
         |
         | This used App\Contact::customersDropdown(), which is core code and
         | reads only the shared `contacts` table.
         |
         | VatCustomerListService reads vat_contacts - the module's own records,
         | which carry the vat_no a VAT report is actually keyed on - and adds the
         | customers from `contacts` when the Customers module is enabled, so a
         | business keeping its customers there can still run the report. Neither
         | table is reached through core code.
         */
        $customers = $this->customerList->customers((int) $business_id, false, true);
        return view('vat::vat_report.index', compact('customers'));
    }

    public function getLedger(Request $request)
    {
        if (! auth()->user()->can('supplier.view') && ! auth()->user()->can('customer.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business = Business::findOrFail($business_id);

        $common_settings     = $business->common_settings ?? [];
        $vat_common_settings = $common_settings['vat_settings'] ?? [];

        // Normalize data (critical)
        $fuel_products = array_values(
            array_filter($vat_common_settings['fuel_products'] ?? [])
        );

        $fuel_products_qty = $vat_common_settings['fuel_products_qty'] ?? [];

        $start_date = request()->start_date;
        $end_date   = request()->end_date;

        $subscription    = Subscription::active_subscription($business_id);
        $pacakge_details = $subscription->package_details;

        $vat_settings = VatSetting::where('business_id', $business_id)->where('status', 1)->first();

        $start_date = $request->get('start_date');

        if (! empty($vat_settings)) {
            if (! empty($pacakge_details['vat_effective_date'])) {
                if (strtotime($vat_settings->effective_date) > strtotime($pacakge_details['vat_effective_date'])) {
                    $pacakge_details['vat_effective_date'] = $vat_settings->effective_date;
                }

            } else {
                $pacakge_details['vat_effective_date'] = $vat_settings->effective_date;
            }

        }

        $effective_date = ! empty($pacakge_details['vat_effective_date']) ? $pacakge_details['vat_effective_date'] : $start_date;

        if (strtotime($start_date) < strtotime($effective_date)) {
            $start_date = $effective_date;
        }

        $tax_type = request()->tax_type;

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::where('business_id', $business_id)->first();

        /*
         | S-667: the module's own tax ledger, with the selected tax type applied.
         |
         | This called App\Utils\ContactUtil::getCustomerTaxBf() and
         | getCustomerTaxLedger(). Two problems with that:
         |
         |   1. it made this module depend on core code, which has to go before
         |      App\Utils can be retired;
         |   2. a method named getCUSTOMERTaxLedger was being asked for purchase
         |      and expense tax. Input tax comes from suppliers. The name masked
         |      the fact that its query only ever returned sales, so the report's
         |      Input and Expense selections showed sales figures.
         |
         | VatTaxLedgerService lives in this module, imports nothing from App, and
         | takes the tax type as part of its contract rather than leaving a shared
         | helper to guess at it.
         */
        $ledger_details['beginning_balance'] = $this->taxLedger->broughtForward(
            (int) $business_id,
            (string) $start_date,
            (string) $effective_date,
            $tax_type
        );

        $ledger_transactions = $this->taxLedger->ledger(
            (int) $business_id,
            (string) $start_date,
            (string) $end_date,
            (string) $effective_date,
            $tax_type
        );

        $filtered_transactions = [];
        foreach ($ledger_transactions as $transaction) {

            if (! empty($transaction->product_id)
                && in_array($transaction->product_id, $fuel_products)) {

                $max_qty = $fuel_products_qty[$transaction->product_id] ?? null;

                if (! is_null($max_qty) && $transaction->quantity > $max_qty) {
                    // Skip transactions that exceed the maximum quantity limit
                    continue;
                }
            }

            $filtered_transactions[] = $transaction;
        }

        return view('vat::vat_report.report_details')
            ->with(compact('ledger_details', 'business_details', 'location_details', 'start_date', 'end_date'))
            ->with('ledger_transactions', $filtered_transactions);

    }

}
