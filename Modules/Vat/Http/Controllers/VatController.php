<?php

namespace Modules\Vat\Http\Controllers;


use App\Business;
use App\BusinessLocation;
// Separation step 3 (document 5-18): the shared `contacts` table is now
// reached through a VAT-owned model, so this file no longer depends on the
// core App\Contact class when the Contact module is retired for Customers.
// NOTE: SharedContact maps to `contacts`; the existing VatContact entity
// maps to `vat_contacts` and is a different data set.
use Modules\Vat\Entities\SharedContact as Contact;
use App\Transaction;

use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\BusinessUtil;
use App\Utils\Util;
;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

use Modules\Vat\Entities\VatSetting;

use Modules\Vat\Entities\VatInvoiceDetail;
use Modules\Vat\Entities\VatInvoiceDetail2;
use Modules\Vat\Entities\FleetVatInvoice2;
use Modules\Vat\Entities\FleetVatInvoiceDetail2;
use Modules\Vat\Entities\VatInvoice;
use Modules\Vat\Entities\VatInvoice2;
use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;
use App\Product;
use App\Category;
use Modules\Vat\Entities\RouteOperation;


class VatController extends Controller
{
    // Separation step 1 (document 5-18): number and date formatting now
    // comes from the module's own VatFormatter, a faithful transcription of
    // App\Utils\Util. Trait, not a constructor parameter, so the shared
    // controller signature is untouched.
    use \Modules\Vat\Support\FormatsVatNumbers;


    /**
     * MA-002 PERF: request-scoped caches for the VAT list DataTable.
     *
     * The list is a UNION of four sources, and its column callbacks run once
     * per row. Three lookups in those callbacks are pure reference data that
     * cannot change between rows of the same page:
     *
     *   - the business tax rate, re-queried on every row
     *   - product id -> category id
     *   - category id -> name
     *
     * The per-row detail queries (VatInvoiceDetail etc.) are keyed by the
     * row's own id and genuinely differ per row, so they are left alone -
     * caching those would be wrong.
     *
     * These maps fill on demand: a page asks for the ids it actually needs,
     * in one query for the whole page instead of two per row.
     */
    private static ?float $ma002TaxRate = null;
    private static array $ma002ProductCategory = [];
    private static array $ma002CategoryName = [];

    private static function ma002TaxRate()
    {
        if (self::$ma002TaxRate === null) {
            self::$ma002TaxRate = (float) (\App\TaxRate::where('business_id', request()->session()->get('business.id'))->first()->amount ?? 0);
        }

        return self::$ma002TaxRate;
    }

    /**
     * Resolve category NAMES for a set of product ids, caching as it goes.
     * Only ids not already known are queried.
     */
    private static function ma002CategoryNamesForProducts(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter($productIds)));
        if ($productIds === []) {
            return [];
        }

        $unknown = array_values(array_diff($productIds, array_keys(self::$ma002ProductCategory)));
        if ($unknown !== []) {
            foreach (Product::whereIn('id', $unknown)->pluck('category_id', 'id')->toArray() as $pid => $cid) {
                self::$ma002ProductCategory[$pid] = $cid;
            }
            // Remember misses too, so a missing product is not re-queried.
            foreach ($unknown as $pid) {
                if (! array_key_exists($pid, self::$ma002ProductCategory)) {
                    self::$ma002ProductCategory[$pid] = null;
                }
            }
        }

        $categoryIds = [];
        foreach ($productIds as $pid) {
            $cid = self::$ma002ProductCategory[$pid] ?? null;
            if (! empty($cid)) {
                $categoryIds[] = $cid;
            }
        }
        $categoryIds = array_values(array_unique($categoryIds));
        if ($categoryIds === []) {
            return [];
        }

        $unknownCats = array_values(array_diff($categoryIds, array_keys(self::$ma002CategoryName)));
        if ($unknownCats !== []) {
            foreach (Category::whereIn('id', $unknownCats)->pluck('name', 'id')->toArray() as $cid => $name) {
                self::$ma002CategoryName[$cid] = $name;
            }
            foreach ($unknownCats as $cid) {
                if (! array_key_exists($cid, self::$ma002CategoryName)) {
                    self::$ma002CategoryName[$cid] = null;
                }
            }
        }

        $names = [];
        foreach ($categoryIds as $cid) {
            $n = self::$ma002CategoryName[$cid] ?? null;
            if ($n !== null && $n !== '') {
                $names[] = $n;
            }
        }

        return $names;
    }

    protected $commonUtil;
    protected $moduleUtil;
    protected $productUtil;
    protected $transactionUtil;
    protected $businessUtil;
    /**
     * Constructor
     *
     * @param Util $commonUtil
     * @return void
     */
    public function __construct(Util $commonUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil, ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {

        $this->commonUtil = $commonUtil;
        $this->moduleUtil = $moduleUtil;
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->businessUtil = $businessUtil;

    }


    /**
     * IS2313: Build VAT Report rows directly from saved business transactions.
     *
     * VAT may be stored in one of three places depending on the source page:
     *   - transactions.tax_amount
     *   - purchase/sell line item_tax values
     *   - an is_vat/tax_id flag where the header amount has not yet been
     *     regenerated.
     *
     * The report must therefore not depend on transactions.tax_id or on a
     * particular transaction status. Draft/deleted rows are still excluded by
     * the Transaction model's normal scope, while legitimate Purchase, POS,
     * Expense and settlement postings remain visible.
     */
    /**
     * IS2313: unified VAT Report source.
     *
     * Core Purchase / Expense / legacy Sales / Petro PD / Petro Direct / SW
     * settlements post into `transactions`. The standalone POS module saves
     * final sales into `pos_sales`, so those rows must be included explicitly
     * or POS VAT will never appear in VAT Report / VAT Report Ledger.
     *
     * The returned query exposes one stable set of aliases so the screen,
     * filters and print all use exactly the same rows.
     */
    private function vatReportTransactionQuery(int $businessId)
    {
        $core = DB::table('transactions')
            ->leftJoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
            ->where('transactions.business_id', $businessId)
            ->whereIn('transactions.type', ['sell', 'purchase', 'expense']);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $core->whereNull('transactions.deleted_at');
        }

        if (Schema::hasColumn('transactions', 'status')) {
            // A saved Purchase entry can legitimately be Pending/Ordered,
            // while Sales/Expenses/settlements normally use Final/Received.
            // IS2322 asks for every saved VAT-bearing source entry, so exclude
            // only clearly non-posted/cancelled states instead of whitelisting
            // two statuses and silently dropping valid Purchase rows.
            $core->where(function ($statusQuery) {
                $statusQuery->whereNull('transactions.status')
                    ->orWhereNotIn('transactions.status', ['draft', 'cancelled', 'canceled', 'void', 'rejected']);
            });
        }

        $purchaseLineTax = '0';
        if (Schema::hasTable('purchase_lines')
            && Schema::hasColumn('purchase_lines', 'transaction_id')
            && Schema::hasColumn('purchase_lines', 'item_tax')
            && Schema::hasColumn('purchase_lines', 'quantity')) {
            $purchaseTaxSub = DB::table('purchase_lines')
                ->selectRaw('transaction_id, SUM(COALESCE(item_tax, 0) * COALESCE(quantity, 0)) as line_tax')
                ->groupBy('transaction_id');

            $core->leftJoinSub($purchaseTaxSub, 'vat_purchase_line_tax', function ($join) {
                $join->on('transactions.id', '=', 'vat_purchase_line_tax.transaction_id');
            });

            $purchaseLineTax = 'COALESCE(vat_purchase_line_tax.line_tax, 0)';
        }

        $sellLineTax = '0';
        if (Schema::hasTable('transaction_sell_lines')
            && Schema::hasColumn('transaction_sell_lines', 'transaction_id')
            && Schema::hasColumn('transaction_sell_lines', 'item_tax')
            && Schema::hasColumn('transaction_sell_lines', 'quantity')) {
            $sellTaxSub = DB::table('transaction_sell_lines')
                ->selectRaw('transaction_id, SUM(COALESCE(item_tax, 0) * COALESCE(quantity, 0)) as line_tax')
                ->groupBy('transaction_id');

            $core->leftJoinSub($sellTaxSub, 'vat_sell_line_tax', function ($join) {
                $join->on('transactions.id', '=', 'vat_sell_line_tax.transaction_id');
            });

            $sellLineTax = 'COALESCE(vat_sell_line_tax.line_tax, 0)';
        }

        $defaultRate = 0.0;
        if (Schema::hasTable('tax_rates')
            && Schema::hasColumn('tax_rates', 'business_id')
            && Schema::hasColumn('tax_rates', 'amount')) {
            $defaultRate = max(0, (float) DB::table('tax_rates')
                ->where('business_id', $businessId)
                ->where('amount', '>', 0)
                ->orderBy('id')
                ->value('amount'));
        }
        $rateSql = number_format($defaultRate, 6, '.', '');

        if (Schema::hasTable('tax_rates') && Schema::hasColumn('transactions', 'tax_id')) {
            $core->leftJoin('tax_rates as vat_report_tax_rate', 'vat_report_tax_rate.id', '=', 'transactions.tax_id');
            $rateSql = 'COALESCE(vat_report_tax_rate.amount, ' . $rateSql . ')';
        }

        $isVatCondition = Schema::hasColumn('transactions', 'is_vat')
            ? 'COALESCE(transactions.is_vat, 0) = 1'
            : '0 = 1';

        $taxIdCondition = Schema::hasColumn('transactions', 'tax_id')
            ? 'transactions.tax_id IS NOT NULL'
            : '0 = 1';

        $headerTax = Schema::hasColumn('transactions', 'tax_amount')
            ? 'COALESCE(transactions.tax_amount, 0)'
            : '0';

        $finalTotal = Schema::hasColumn('transactions', 'final_total')
            ? 'COALESCE(transactions.final_total, 0)'
            : '0';

        $derivedTax = '(CASE
            WHEN ' . $rateSql . ' > 0 AND ' . $finalTotal . ' > 0
            THEN ' . $finalTotal . ' - (' . $finalTotal . ' / (1 + (' . $rateSql . ' / 100)))
            ELSE 0
        END)';

        $effectiveTax = '(CASE
            WHEN ' . $headerTax . ' > 0
                THEN ' . $headerTax . '
            WHEN transactions.type = "purchase" AND ' . $purchaseLineTax . ' > 0
                THEN ' . $purchaseLineTax . '
            WHEN transactions.type = "sell" AND ' . $sellLineTax . ' > 0
                THEN ' . $sellLineTax . '
            WHEN (' . $isVatCondition . ' OR ' . $taxIdCondition . ')
                THEN ' . $derivedTax . '
            ELSE 0
        END)';

        $invoiceNo = Schema::hasColumn('transactions', 'invoice_no')
            ? 'transactions.invoice_no'
            : 'NULL';
        $refNo = Schema::hasColumn('transactions', 'ref_no')
            ? 'transactions.ref_no'
            : 'NULL';
        $locationId = Schema::hasColumn('transactions', 'location_id')
            ? 'transactions.location_id'
            : 'NULL';
        $contactId = Schema::hasColumn('transactions', 'contact_id')
            ? 'transactions.contact_id'
            : 'NULL';
        $isSettlement = Schema::hasColumn('transactions', 'is_settlement')
            ? 'COALESCE(transactions.is_settlement, 0)'
            : '0';

        $core->select([
                'transactions.id',
                'transactions.transaction_date',
                'transactions.type',
                DB::raw($invoiceNo . ' as invoice_no'),
                DB::raw($refNo . ' as ref_no'),
                DB::raw($contactId . ' as contact_id'),
                DB::raw($locationId . ' as location_id'),
                DB::raw($finalTotal . ' as final_total'),
                DB::raw('COALESCE(contacts.name, "") as contact_name'),
                DB::raw($isSettlement . ' as is_settlement'),
                DB::raw('NULL as deletedBy'),
                DB::raw('"core" as vat_source'),
                DB::raw($effectiveTax . ' as vat_report_tax_amount'),
                DB::raw($headerTax . ' as tax_amount'),
            ])
            ->whereRaw($effectiveTax . ' > 0');

        /*
         * POS-New is intentionally standalone. Its normal checkout writes
         * `pos_sales` / `pos_sale_lines` rather than core `transactions`.
         * Include those final VAT-bearing sales when the tables/columns exist.
         *
         * `whereNotExists` prevents a duplicate if a future/older deployment
         * also mirrors the same POS invoice into core transactions.
         */
        if (Schema::hasTable('pos_sales')
            && Schema::hasColumn('pos_sales', 'business_id')
            && Schema::hasColumn('pos_sales', 'id')) {

            $posDate = Schema::hasColumn('pos_sales', 'sale_date')
                ? 'COALESCE(pos_sales.sale_date, pos_sales.created_at)'
                : 'pos_sales.created_at';
            $posInvoice = Schema::hasColumn('pos_sales', 'invoice_no')
                ? 'pos_sales.invoice_no'
                : (Schema::hasColumn('pos_sales', 'sale_no') ? 'pos_sales.sale_no' : 'CONCAT("POS-", pos_sales.id)');
            $posCustomerId = Schema::hasColumn('pos_sales', 'customer_id')
                ? 'pos_sales.customer_id'
                : 'NULL';
            $posLocationId = Schema::hasColumn('pos_sales', 'business_location_id')
                ? 'pos_sales.business_location_id'
                : 'NULL';
            $posTotal = Schema::hasColumn('pos_sales', 'total_amount')
                ? 'COALESCE(pos_sales.total_amount, 0)'
                : '0';
            $posCustomerName = Schema::hasColumn('pos_sales', 'customer_name')
                ? 'COALESCE(pos_sales.customer_name, "")'
                : '""';
            $posHeaderTax = Schema::hasColumn('pos_sales', 'tax_amount')
                ? 'COALESCE(pos_sales.tax_amount, 0)'
                : '0';

            $posLineTax = '0';
            $pos = DB::table('pos_sales')
                ->where('pos_sales.business_id', $businessId);

            if (Schema::hasTable('pos_sale_lines')
                && Schema::hasColumn('pos_sale_lines', 'tax_amount')) {
                $saleIdColumn = Schema::hasColumn('pos_sale_lines', 'sale_id')
                    ? 'sale_id'
                    : (Schema::hasColumn('pos_sale_lines', 'pos_sale_id') ? 'pos_sale_id' : null);

                if (! empty($saleIdColumn)) {
                    $posTaxSub = DB::table('pos_sale_lines')
                        ->selectRaw($saleIdColumn . ' as sale_id, SUM(COALESCE(tax_amount, 0)) as line_tax')
                        ->groupBy($saleIdColumn);

                    $pos->leftJoinSub($posTaxSub, 'vat_pos_line_tax', function ($join) {
                        $join->on('pos_sales.id', '=', 'vat_pos_line_tax.sale_id');
                    });

                    $posLineTax = 'COALESCE(vat_pos_line_tax.line_tax, 0)';
                }
            }

            $posEffectiveTax = '(CASE
                WHEN ' . $posHeaderTax . ' > 0 THEN ' . $posHeaderTax . '
                WHEN ' . $posLineTax . ' > 0 THEN ' . $posLineTax . '
                ELSE 0
            END)';

            if (Schema::hasColumn('pos_sales', 'status')) {
                $pos->where(function ($statusQuery) {
                    $statusQuery->where('pos_sales.status', 'final')
                        ->orWhereNull('pos_sales.status');
                });
            }

            if (Schema::hasColumn('transactions', 'invoice_no')
                && (Schema::hasColumn('pos_sales', 'invoice_no') || Schema::hasColumn('pos_sales', 'sale_no'))) {
                $pos->whereNotExists(function ($duplicate) use ($businessId, $posInvoice) {
                    $duplicate->select(DB::raw(1))
                        ->from('transactions as vat_pos_core_tx')
                        ->whereRaw('vat_pos_core_tx.invoice_no = ' . $posInvoice)
                        ->where('vat_pos_core_tx.business_id', $businessId)
                        ->where('vat_pos_core_tx.type', 'sell');
                });
            }

            $pos->select([
                    'pos_sales.id',
                    DB::raw($posDate . ' as transaction_date'),
                    DB::raw('"sell" as type'),
                    DB::raw($posInvoice . ' as invoice_no'),
                    DB::raw($posInvoice . ' as ref_no'),
                    DB::raw($posCustomerId . ' as contact_id'),
                    DB::raw($posLocationId . ' as location_id'),
                    DB::raw($posTotal . ' as final_total'),
                    DB::raw($posCustomerName . ' as contact_name'),
                    DB::raw('0 as is_settlement'),
                    DB::raw('NULL as deletedBy'),
                    DB::raw('"pos" as vat_source'),
                    DB::raw($posEffectiveTax . ' as vat_report_tax_amount'),
                    DB::raw($posEffectiveTax . ' as tax_amount'),
                ])
                ->whereRaw($posEffectiveTax . ' > 0');

            $core->unionAll($pos);
        }

        return DB::query()->fromSub($core, 'vat_report_rows');
    }


    public function updateVats(Request $request)
    {
        $business_id = (int) $request->session()->get('user.business_id');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $transaction_types = array_values(array_intersect(
            (array) $request->input('transaction_types', []),
            ['sell', 'purchase', 'expense']
        ));

        try {
            if (empty($start_date) || empty($end_date) || strtotime($start_date) > strtotime($end_date)) {
                return [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            if (empty($transaction_types)) {
                return [
                    'success' => false,
                    'msg' => __('lang_v1.please_select'),
                ];
            }

            $effective_date = $this->vatTaxCalculation()->effectiveDate($business_id);

            if (! empty($effective_date) && strtotime($start_date) < strtotime($effective_date)) {
                $output = [
                    'success' => false,
                    'msg' => __('superadmin::lang.you_can_only_generate_invoices_from')
                        . $this->vatFormatter()->format_date($effective_date)
                ];

                return $output;
            }

            /*
             * IS2313:
             * - always stay inside the logged-in business;
             * - honour the exact requested date window;
             * - only regenerate the transaction types supported by VAT Report.
             *
             * The old query missed both business_id and end_date, which could
             * update unrelated rows and did not match the modal selections.
             */
            $transactions = Transaction::where('business_id', $business_id)
                ->whereIn('transactions.type', $transaction_types)
                ->whereDate('transaction_date', '>=', $start_date)
                ->whereDate('transaction_date', '<=', $end_date)
                ->get();

            foreach ($transactions as $transaction) {
                $this->transactionUtil->calculateAndUpdateVAT($transaction);
            }

            $output = [
                'success' => true,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Throwable $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }

    public function updateSingleVats(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $transaction_id = $request->transaction_id;

        try {
            $transaction = Transaction::findOrFail($transaction_id);

            $this->transactionUtil->calculateAndUpdateVAT($transaction);

            $output = [
                'success' => true,
                'msg' => __('petro::lang.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;

    }

    public function getCustomerVatSchedule(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (request()->ajax()) {
            // IS2309: use the saved invoice date and provide a safe legacy
            // fallback for rows created by older versions where that field was
            // left null. The same resolved date is used for display, filtering
            // and ordering so the Invoice Date column cannot appear blank while
            // the row is otherwise valid.
            $vatInvoiceDateSql = 'COALESCE(vat_invoices.date, DATE(vat_invoices.created_at))';
            $vatInvoice2DateSql = 'COALESCE(vat_invoices_2.date, vat_invoices_2.supplied_on, DATE(vat_invoices_2.created_at))';
            $fleetVatInvoice2DateSql = 'COALESCE(fleet_vat_invoices_2.date, DATE(fleet_vat_invoices_2.created_at))';
            $statementDateSql = 'COALESCE(vat_customer_statements.print_date, DATE(vat_customer_statements.created_at))';

            $vat_invoice_schedule = VatInvoice::leftjoin('contacts', 'contacts.id', 'vat_invoices.customer_id')
                ->where('vat_invoices.business_id', $business_id)
                ->select('vat_invoices.id', 'vat_invoices.customer_id as contact_id', DB::raw($vatInvoiceDateSql . ' as date'), 'vat_invoices.customer_bill_no as invoice_no', 'contacts.vat_number', 'contacts.name as contact_name', 'vat_invoices.total_amount as tax_base', 'vat_invoices.tax_amount as tax_amount', DB::raw('"vat_invoice" as type'))
                ->where('vat_invoices.tax_amount', '>', 0)
                ->orderByRaw($vatInvoiceDateSql . ' ASC');

            $vat_invoice_statement = VatCustomerStatement::leftjoin('contacts', 'contacts.id', 'vat_customer_statements.customer_id')
                ->where('vat_customer_statements.business_id', $business_id)
                ->select('vat_customer_statements.id', 'vat_customer_statements.customer_id as contact_id', DB::raw($statementDateSql . ' as date'), 'vat_customer_statements.statement_no as invoice_no', 'contacts.vat_number', 'contacts.name as contact_name', DB::raw('"0" as tax_base'), DB::raw('"0" as tax_amount'), DB::raw('"vat_customer_statement" as type'))
                ->orderByRaw($statementDateSql . ' ASC');

            $vat_invoice2_schedule = VatInvoice2::leftjoin('contacts', 'contacts.id', 'vat_invoices_2.customer_id')
                ->where('vat_invoices_2.business_id', $business_id)
                ->select('vat_invoices_2.id', 'vat_invoices_2.customer_id as contact_id', DB::raw($vatInvoice2DateSql . ' as date'), 'vat_invoices_2.customer_bill_no as invoice_no', 'contacts.vat_number', 'contacts.name as contact_name', 'vat_invoices_2.total_amount as tax_base', 'vat_invoices_2.tax_amount as tax_amount', DB::raw('"vat_invoice2" as type'))
                ->where('vat_invoices_2.tax_amount', '>', 0)
                ->orderByRaw($vatInvoice2DateSql . ' ASC');

            $fleet_vat_invoice2_schedule = FleetVatInvoice2::leftjoin('contacts', 'contacts.id', 'fleet_vat_invoices_2.customer_id')
                ->where('fleet_vat_invoices_2.business_id', $business_id)
                ->select('fleet_vat_invoices_2.id', 'fleet_vat_invoices_2.customer_id as contact_id', DB::raw($fleetVatInvoice2DateSql . ' as date'), 'fleet_vat_invoices_2.customer_bill_no as invoice_no', 'contacts.vat_number', 'contacts.name as contact_name', 'fleet_vat_invoices_2.total_amount as tax_base', 'fleet_vat_invoices_2.tax_amount as tax_amount', DB::raw('"fleet_vat_invoice2" as type'))
                ->where('fleet_vat_invoices_2.tax_amount', '>', 0)
                ->orderByRaw($fleetVatInvoice2DateSql . ' ASC');



            if (!empty(request()->start_date) && !empty(request()->end_date)) {

                $start_date = request()->start_date ?? date('Y-m-01');

                $end = request()->end_date ?? date('Y-m-t');

                // vat effective date
                $effective_date = $this->vatTaxCalculation()->effectiveDate((int) $business_id);


                if (strtotime($start_date) < strtotime($effective_date)) {
                    $start_date = $effective_date;
                }

                $vat_invoice_schedule->whereRaw('DATE(' . $vatInvoiceDateSql . ') >= ? AND DATE(' . $vatInvoiceDateSql . ') <= ?', [$start_date, $end]);
                $vat_invoice2_schedule->whereRaw('DATE(' . $vatInvoice2DateSql . ') >= ? AND DATE(' . $vatInvoice2DateSql . ') <= ?', [$start_date, $end]);
                $fleet_vat_invoice2_schedule->whereRaw('DATE(' . $fleetVatInvoice2DateSql . ') >= ? AND DATE(' . $fleetVatInvoice2DateSql . ') <= ?', [$start_date, $end]);
                $vat_invoice_statement->whereRaw('DATE(' . $statementDateSql . ') >= ? AND DATE(' . $statementDateSql . ') <= ?', [$start_date, $end]);
            }

            if (!empty(request()->get('contact_id'))) {
                $vat_invoice_schedule->where('customer_id', request()->get('contact_id'));
                $vat_invoice2_schedule->where('customer_id', request()->get('contact_id'));
                $fleet_vat_invoice2_schedule->where('customer_id', request()->get('contact_id'));
                $vat_invoice_statement->where('customer_id', request()->get('contact_id'));
            }



            $merged_data = $vat_invoice_schedule->union($vat_invoice2_schedule)->union($fleet_vat_invoice2_schedule)->union($vat_invoice_statement)->orderBy('date', 'ASC');


            return Datatables::of($merged_data->get())

                ->removeColumn('id')

                ->addColumn('customer_name', function ($row) {
                    return $row->contact_name ?? null;
                })
                ->addColumn('customer', function ($row) {
                    return $row->contact_name ?? null;
                })

                ->editColumn('vat_number', function ($row) {
                    /*
                     * S664 (items 4 & 5): guarded against a NULL vat_number.
                     *
                     * contacts.vat_number is nullable and is null for most
                     * contacts in live data (27 of 38 in the supplied database).
                     * These schedules leftjoin contacts, so the value arrives as
                     * null, and explode('-', null) is DEPRECATED in PHP 8 - the
                     * notice is printed into the response body ahead of the JSON,
                     * which DataTables then rejects and renders as an empty
                     * table. That is why the schedules showed no details even
                     * though the underlying invoices exist.
                     *
                     * Same failure mode as the F9C settings table in IS2014.
                     */
                    $vatNumber = (string) ($row->vat_number ?? '');

                    return $vatNumber === '' ? '' : explode('-', $vatNumber)[0];
                })

                ->addColumn('product_name', function ($row) {
                    if ($row->type == 'vat_invoice') {
                        $products = VatInvoiceDetail::where('issue_bill_id', $row->id)->pluck('product_id')->toArray() ?? [];
                    } elseif ($row->type == 'fleet_vat_invoice2') {
                        $products = FleetVatInvoiceDetail2::where('issue_bill_id', $row->id)->pluck('product_id')->toArray() ?? [];

                        $cats = RouteOperation::whereIn('id', $products)->pluck('invoice_no')->toArray() ?? [];

                        return implode('<br>', $cats);

                    } elseif ($row->type == 'vat_customer_statement') {
                        $products = VatCustomerStatementDetail::where('statement_id', $row->id)->pluck('product_id')->toArray() ?? [];
                    } else {
                        $products = VatInvoiceDetail2::where('issue_bill_id', $row->id)->pluck('product_id')->toArray() ?? [];
                    }

                    // MA-002 PERF: cached reference lookup, see ma002CategoryNamesForProducts().
                    $cats = self::ma002CategoryNamesForProducts($products);

                    return implode('<br>', $cats);
                })

                ->editColumn(

                    'tax_base',
                    function ($row) {
                        if ($row->type == 'vat_customer_statement') {
                            $row->tax_base = VatCustomerStatementDetail::where('statement_id', $row->id)->sum('invoice_amount') ?? 0;

                            $total = $row->tax_base;
                            $tax_rate = self::ma002TaxRate();
                            $pre_tax = $total / (1 + ($tax_rate / 100));
                            $row->tax_amount = ($tax_rate / 100) * $pre_tax;
                        }

                        $row->tax_base -= $row->tax_amount;

                        return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->tax_base . '">' . $this->vatFormatter()->num_uf($this->vatFormatter()->num_f($row->tax_base)) . '</span>';
                    }
                )

                ->editColumn(

                    'tax_amount',
                    function ($row) {
                        if ($row->type == 'vat_customer_statement') {

                            $total = VatCustomerStatementDetail::where('statement_id', $row->id)->sum('invoice_amount') ?? 0;
                            $tax_rate = self::ma002TaxRate();
                            $pre_tax = $total / (1 + ($tax_rate / 100));
                            $row->tax_amount = ($tax_rate / 100) * $pre_tax;
                        }

                        return '<span class="display_currency tax-amount" data-currency_symbol="true" data-orig-value="' . $row->tax_amount . '">' . $this->vatFormatter()->num_uf($this->vatFormatter()->num_f($row->tax_amount)) . '</span>';
                    }
                )

                ->editColumn('date', function ($row) {
                    return $this->vatFormatter()->format_date($row->date);
                })

                ->rawColumns(['final_total', 'tax_amount', 'tax_base', 'product_name'])

                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        $contacts = Contact::contactDropdown($business_id, false, false);


        return view('vat::vat_schedule.reports')->with(compact(

            'business_locations',

            'contacts'

        ));

    }

    public function getSupplierVatSchedule(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (request()->ajax()) {

            // IS2309: purchase VAT can be stored on purchase_lines while the
            // transaction header has no tax_id. Aggregate one row per purchase
            // invoice from the recorded line values, and only fall back to the
            // header tax amount when it is already populated. This prevents both
            // missing purchases and duplicated header VAT across multiple lines.
            $purchaseLineBaseSql = '(COALESCE(purchase_lines.purchase_price, 0) * COALESCE(purchase_lines.quantity, 0))';
            $purchaseLineTaxSql = '(COALESCE(purchase_lines.item_tax, 0) * COALESCE(purchase_lines.quantity, 0))';

            $sales_invoices = Transaction::leftjoin('purchase_lines', 'transactions.id', '=', 'purchase_lines.transaction_id')
                ->leftjoin('contacts', 'contacts.id', '=', 'transactions.contact_id')
                ->leftjoin('products', 'products.id', '=', 'purchase_lines.product_id')
                ->leftjoin('categories', 'products.category_id', '=', 'categories.id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase')
                ->where(function ($query) use ($purchaseLineTaxSql) {
                    $query->where('transactions.tax_amount', '>', 0)
                        ->orWhereRaw($purchaseLineTaxSql . ' > 0');
                })
                ->select(
                    'transactions.id',
                    'transactions.contact_id',
                    'transactions.transaction_date as date',
                    'transactions.invoice_no',
                    'contacts.vat_number',
                    'contacts.name as contact_name'
                )
                ->selectRaw('GROUP_CONCAT(DISTINCT COALESCE(categories.name, products.name) SEPARATOR ", ") as product_name')
                ->selectRaw('CASE WHEN SUM(' . $purchaseLineBaseSql . ') > 0 THEN SUM(' . $purchaseLineBaseSql . ') ELSE COALESCE(transactions.total_before_tax, transactions.final_total - COALESCE(transactions.tax_amount, 0), 0) END as tax_base')
                ->selectRaw('CASE WHEN COALESCE(transactions.tax_amount, 0) > 0 THEN transactions.tax_amount ELSE SUM(' . $purchaseLineTaxSql . ') END as tax_amount')
                ->groupBy(
                    'transactions.id',
                    'transactions.contact_id',
                    'transactions.transaction_date',
                    'transactions.invoice_no',
                    'contacts.vat_number',
                    'contacts.name',
                    'transactions.total_before_tax',
                    'transactions.final_total',
                    'transactions.tax_amount'
                );


            if (!empty(request()->start_date) && !empty(request()->end_date)) {

                $start_date = request()->start_date ?? date('Y-m-01');

                $end = request()->end_date ?? date('Y-m-t');

                $effective_date = $this->vatTaxCalculation()->effectiveDate((int) $business_id);


                if (strtotime($start_date) < strtotime($effective_date)) {
                    $start_date = $effective_date;
                }

                $sales_invoices->whereDate('transactions.transaction_date', '>=', $start_date)->whereDate('transactions.transaction_date', '<=', $end);
            }

            if (!empty(request()->get('contact_id'))) {
                $sales_invoices->where('transactions.contact_id', request()->get('contact_id'));
            }



            $merged_data = $sales_invoices->orderBy('date', 'ASC');


            return Datatables::of($merged_data->get())

                ->removeColumn('id')

                ->editColumn('vat_number', function ($row) {
                    /*
                     * S664 (items 4 & 5): guarded against a NULL vat_number.
                     *
                     * contacts.vat_number is nullable and is null for most
                     * contacts in live data (27 of 38 in the supplied database).
                     * These schedules leftjoin contacts, so the value arrives as
                     * null, and explode('-', null) is DEPRECATED in PHP 8 - the
                     * notice is printed into the response body ahead of the JSON,
                     * which DataTables then rejects and renders as an empty
                     * table. That is why the schedules showed no details even
                     * though the underlying invoices exist.
                     *
                     * Same failure mode as the F9C settings table in IS2014.
                     */
                    $vatNumber = (string) ($row->vat_number ?? '');

                    return $vatNumber === '' ? '' : explode('-', $vatNumber)[0];
                })

                ->editColumn(

                    'tax_base',
                    function ($row) {
                        return '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="' . $row->tax_base . '">' . $this->vatFormatter()->num_uf($this->vatFormatter()->num_f($row->tax_base)) . '</span>';
                    }
                )

                ->editColumn(

                    'tax_amount',
                    function ($row) {
                        return '<span class="display_currency tax-amount" data-currency_symbol="true" data-orig-value="' . $row->tax_amount . '">' . $this->vatFormatter()->num_uf($this->vatFormatter()->num_f($row->tax_amount)) . '</span>';
                    }
                )

                ->editColumn('date', function ($row) {
                    return $this->vatFormatter()->format_date($row->date);
                })

                ->rawColumns(['final_total', 'tax_amount', 'tax_base'])

                ->make(true);
        }

    }


    /**
     * Resolve the active business consistently for VAT Report endpoints.
     *
     * Different parts of this multi-business application populate either
     * `business.id` or `user.business_id`. IS2322 was still using only the
     * latter in the table/print endpoints, which could silently build the VAT
     * query with business id 0/null and return an empty report.
     */
    private function vatReportBusinessId(Request $request): int
    {
        return (int) (
            $request->session()->get('business.id')
            ?? $request->session()->get('user.business_id')
            ?? optional(auth()->user())->business_id
            ?? 0
        );
    }

    /**
     * Apply the exact same filters to the VAT table, cards and print view.
     */
    private function applyVatReportFilters($query, Request $request, int $businessId)
    {
        if (! empty($request->get('contact_id'))) {
            $query->where('vat_report_rows.contact_id', $request->get('contact_id'));
        }

        if (! empty($request->get('reference_type'))) {
            $query->where('vat_report_rows.type', $request->get('reference_type'));
        }

        if (! empty($request->get('location_id'))) {
            $query->where('vat_report_rows.location_id', $request->get('location_id'));
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        if (! empty($startDate) && ! empty($endDate)) {
            $effectiveDate = $this->vatTaxCalculation()->effectiveDate($businessId);
            if (! empty($effectiveDate) && strtotime($startDate) < strtotime($effectiveDate)) {
                $startDate = $effectiveDate;
            }

            $query->whereDate('vat_report_rows.transaction_date', '>=', $startDate)
                ->whereDate('vat_report_rows.transaction_date', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Calculate the report cards from the same source rows shown in the table.
     *
     * This matters for the new Purchase module because product VAT is stored on
     * purchase_lines, and for POS-New because sales are stored in pos_sales.
     * The old summary service reads VAT-owned tables, so the cards could say
     * zero even while the actual Purchase/POS/Expense transaction carries VAT.
     */
    private function vatReportTotals(Request $request, int $businessId): array
    {
        $source = $this->applyVatReportFilters(
            $this->vatReportTransactionQuery($businessId),
            $request,
            $businessId
        );

        $totals = DB::query()
            ->fromSub($source, 'vat_report_totals')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "purchase" THEN vat_report_tax_amount ELSE 0 END), 0) as input_tax')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "sell" THEN vat_report_tax_amount ELSE 0 END), 0) as output_tax')
            ->selectRaw('COALESCE(SUM(CASE WHEN type = "expense" THEN vat_report_tax_amount ELSE 0 END), 0) as expense_tax')
            ->first();

        return [
            'input_tax' => round((float) ($totals->input_tax ?? 0), 2),
            'output_tax' => round((float) ($totals->output_tax ?? 0), 2),
            'expense_tax' => round((float) ($totals->expense_tax ?? 0), 2),
        ];
    }

    /**
     * VAT-owned tax summary used by the VAT Report page.
     */
    public function getVatReportSummary(Request $request)
    {
        $businessId = $this->vatReportBusinessId($request);

        try {
            $totals = $this->vatReportTotals($request, $businessId);
            $inputTaxDetails = ['total_tax' => $totals['input_tax']];
            $outputTaxDetails = ['total_tax' => $totals['output_tax']];
            $expenseTaxDetails = ['total_tax' => $totals['expense_tax']];

            return response()->json([
                'success' => true,
                'input_tax' => view('report.partials.tax_details', ['tax_details' => $inputTaxDetails])->render(),
                'output_tax' => view('report.partials.tax_details', ['tax_details' => $outputTaxDetails])->render(),
                'expense_tax' => view('report.partials.tax_details', ['tax_details' => $expenseTaxDetails])->render(),
                'tax_diff' => $totals['output_tax'] - $totals['input_tax'] - $totals['expense_tax'],
            ]);
        } catch (\Throwable $e) {
            Log::error('VAT report summary failed', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'input_tax' => '<span class="display_currency" data-currency_symbol="true">0</span>',
                'output_tax' => '<span class="display_currency" data-currency_symbol="true">0</span>',
                'expense_tax' => '<span class="display_currency" data-currency_symbol="true">0</span>',
                'tax_diff' => 0,
            ]);
        }
    }

    public function getVatReport(Request $request)
    {
        $businessId = $this->vatReportBusinessId($request);

        if ($request->ajax()) {
            $rows = $this->applyVatReportFilters(
                $this->vatReportTransactionQuery($businessId),
                $request,
                $businessId
            );

            return Datatables::of($rows)
                ->addColumn('action', function ($row) {
                    $html = '<div class="btn-group">'
                        . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                        . __('messages.actions')
                        . '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>'
                        . '<ul class="dropdown-menu dropdown-menu-left" role="menu">';

                    /*
                     * Action links are best-effort only. A missing optional
                     * module/route must never make the entire VAT DataTable AJAX
                     * request fail and hide otherwise valid VAT rows.
                     */
                    try {
                        if (($row->vat_source ?? 'core') === 'pos') {
                            if (\Illuminate\Support\Facades\Route::has('pos.sales.receipt')) {
                                $html .= '<li><a href="' . route('pos.sales.receipt', [$row->id]) . '" target="_blank"><i class="fa fa-print"></i> '
                                    . __('messages.print') . '</a></li>';
                            }
                        } elseif ($row->type === 'purchase') {
                            $html .= '<li><a href="#" data-href="' . action('PurchaseController@show', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-eye"></i> '
                                . __('messages.view') . '</a></li>';
                            $html .= '<li><a href="#" class="print-invoice" data-href="' . action('PurchaseController@printInvoice', [$row->id]) . '"><i class="fa fa-print"></i> '
                                . __('messages.print') . '</a></li>';
                        } elseif ($row->type === 'expense') {
                            $html .= '<li><a href="' . action('ExpenseController@edit', [$row->id]) . '"><i class="fa fa-eye"></i> '
                                . __('messages.view') . '</a></li>';
                        } elseif ($row->type === 'sell') {
                            if ((int) $row->is_settlement === 1 && Schema::hasTable('settlements')) {
                                $settlement = DB::table('settlements')
                                    ->where('settlement_no', $row->invoice_no)
                                    ->first();

                                if (! empty($settlement)) {
                                    $html .= '<li><a data-href="' . action('\\Modules\\Petro\\Http\\Controllers\\SettlementController@show', [$settlement->id]) . '" class="btn-modal" data-container=".settlement_modal"><i class="fa fa-eye"></i> '
                                        . __('messages.view') . '</a></li>';
                                    $html .= '<li><a data-href="' . action('\\Modules\\Petro\\Http\\Controllers\\SettlementController@print', [$settlement->id]) . '" class="print_settlement_button"><i class="fa fa-print"></i> '
                                        . __('petro::lang.print') . '</a></li>';
                                }
                            } else {
                                $html .= '<li><a href="#" data-href="' . action('SellController@show', [$row->id]) . '" class="btn-modal" data-container=".view_modal"><i class="fa fa-external-link"></i> '
                                    . __('messages.view') . '</a></li>';
                                $html .= '<li><a href="#" class="print-invoice" data-href="' . route('sell.printInvoice', [$row->id]) . '"><i class="fa fa-print"></i> '
                                    . __('messages.print') . '</a></li>';
                            }
                        }
                    } catch (\Throwable $actionError) {
                        Log::warning('VAT report action link unavailable', [
                            'row_id' => $row->id ?? null,
                            'type' => $row->type ?? null,
                            'source' => $row->vat_source ?? null,
                            'message' => $actionError->getMessage(),
                        ]);
                    }

                    $html .= '</ul></div>';
                    return $html;
                })
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final-total" data-currency_symbol="true" data-orig-value="{{empty($deletedBy) ? $final_total : 0}}">{{@num_format($final_total)}}</span>'
                )
                ->editColumn('tax_amount', function ($row) {
                    $taxAmount = (float) ($row->vat_report_tax_amount ?? $row->tax_amount ?? 0);
                    return '<span class="display_currency tax-amount" data-currency_symbol="true" data-orig-value="'
                        . $taxAmount . '">' . $this->vatFormatter()->num_f($taxAmount) . '</span>';
                })
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('ref_no', function ($row) {
                    return ! empty($row->invoice_no) ? $row->invoice_no : $row->ref_no;
                })
                ->rawColumns(['final_total', 'action', 'tax_amount', 'ref_no'])
                ->make(true);
        }

        $business_locations = BusinessLocation::forDropdown($businessId, true);
        $contacts = Contact::contactDropdown($businessId, false, false);
        $reports = [
            'sell' => __('vat::lang.sale'),
            'purchase' => __('vat::lang.purchase'),
            'expense' => __('vat::lang.expense'),
        ];

        return view('vat::vat.reports')->with(compact(
            'business_locations',
            'contacts',
            'reports'
        ));
    }

    public function printVatReport(Request $request)
    {
        $businessId = $this->vatReportBusinessId($request);
        $locationId = $request->get('location_id');

        $location_details = ! empty($locationId)
            ? BusinessLocation::where('business_id', $businessId)->find($locationId)
            : BusinessLocation::where('business_id', $businessId)->first();

        $query = $this->applyVatReportFilters(
            $this->vatReportTransactionQuery($businessId),
            $request,
            $businessId
        );

        $totals = $this->vatReportTotals($request, $businessId);
        $expenses = $query->get();
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');
        $input_tax = $totals['input_tax'];
        $output_tax = $totals['output_tax'];
        $expense_tax = $totals['expense_tax'];

        return view('vat::vat.print')->with(compact(
            'location_details',
            'expenses',
            'start_date',
            'end_date',
            'input_tax',
            'output_tax',
            'expense_tax'
        ));
    }


    /**
     * Separation step 2 (document 5-18): VAT effective date and tax totals now
     * come from the module instead of App\\Utils\\TransactionUtil.
     *
     * __getVatEffectiveDate() read vat_settings - a VAT-owned table - and the
     * three tax calculators are VAT arithmetic, so both belong here. Resolved
     * through the container on each use rather than injected, so this change
     * does not alter the constructor signature that 35 controllers share.
     */
    private function vatTaxCalculation(): \Modules\Vat\Services\VatTaxCalculationService
    {
        return app(\Modules\Vat\Services\VatTaxCalculationService::class);
    }
}
