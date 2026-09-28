<?php

namespace Modules\MPCS\Http\Controllers;

use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Product;
use App\Store;
use App\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Modules\MPCS\Entities\MpcsFormSetting;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\MPCS\Entities\Mpcs15FormDetails;
use Modules\MPCS\Entities\FormF15TransactionData; 
use Modules\MPCS\Entities\FormF15Header; 
use App\Contact;
use App\Transaction;
class F14FormController extends Controller
{ 
    protected $transactionUtil;
    protected $productUtil;
    protected $moduleUtil;
    protected $util;
 
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil, ModuleUtil $moduleUtil, Util $util)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
        $this->moduleUtil = $moduleUtil;
        $this->util = $util;
    }

    public function index(Request $request)
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $businessId = $request->session()->get('user.business_id')
            ?? $request->session()->get('business.id');
        $business = Business::find($businessId);
        $businessLocations = BusinessLocation::forDropdown($businessId, true);
        [$startDate, $endDate] = $this->resolveDateRange($request);

        /*
         * The main ERP profit/loss partial also consumes start_date/end_date and
         * calls a legacy formatter that is not available on this installation.
         * F14 has its own namespaced filter fields; remove legacy GET aliases
         * after reading them so the unrelated report partial cannot intercept
         * this page's filter request.
         */
        $request->query->remove('start_date');
        $request->query->remove('end_date');

        $locationId = $request->filled('f14_location_id')
            ? (int) $request->input('f14_location_id')
            : ($request->filled('location_id') ? (int) $request->input('location_id') : null);
        $quantityPrecision = max(3, (int) ($business->quantity_precision ?? 3));
        $currencyPrecision = (int) ($business->currency_precision ?? 2);

        /*
         * Tenant databases do not all have the same Petro settlement columns.
         * Build the location expression only from columns that exist so F14 can
         * work on both the older schema and the newer schema.
         */
        $hasCreditLocation = Schema::hasColumn('settlement_credit_sale_payments', 'location_id');
        $hasCreditSettlementNo = Schema::hasColumn('settlement_credit_sale_payments', 'settlement_no');
        $hasCustomerReference = Schema::hasColumn('settlement_credit_sale_payments', 'customer_reference');
        $hasBillNumber = Schema::hasColumn('settlement_credit_sale_payments', 'bill_number');
        $hasCreditCreatedAt = Schema::hasColumn('settlement_credit_sale_payments', 'created_at');
        $hasSettlementLocation = Schema::hasColumn('settlements', 'location_id');
        $hasSettlementNumber = Schema::hasColumn('settlements', 'settlement_no');
        $hasSettlementDate = Schema::hasColumn('settlements', 'transaction_date');
        $hasSellLineDeletedAt = Schema::hasColumn('transaction_sell_lines', 'deleted_at');
        $hasPaymentDeletedAt = Schema::hasColumn('transaction_payments', 'deleted_at');
        $joinSettlement = $hasCreditSettlementNo && ($hasSettlementLocation || $hasSettlementDate);

        $locationColumns = [];
        if ($hasCreditLocation) {
            $locationColumns[] = 'scsp.location_id';
        }
        $locationColumns[] = 't.location_id';
        if ($joinSettlement && $hasSettlementLocation) {
            $locationColumns[] = 'st.location_id';
        }
        $locationExpression = count($locationColumns) > 1
            ? 'COALESCE('.implode(', ', $locationColumns).')'
            : $locationColumns[0];
        $customerReferenceExpression = $hasCustomerReference
            ? 'scsp.customer_reference'
            : 'NULL';
        $settlementReferenceExpression = $hasCreditSettlementNo
            ? 'scsp.settlement_no'
            : 'NULL';
        $voucherExpression = $hasBillNumber
            ? "COALESCE(NULLIF(scsp.bill_number, ''), NULLIF(scsp.order_number, ''), t.invoice_no, scsp.id)"
            : "COALESCE(NULLIF(scsp.order_number, ''), t.invoice_no, scsp.id)";
        // IS2188: once a row belongs to a Direct Settlement, the settlement date is
        // the accounting/reporting date. An old order_date must not pin an edited
        // settlement to the wrong MPCS day.
        $settledDateColumns = [];
        if ($joinSettlement && $hasSettlementDate) {
            $settledDateColumns[] = 'st.transaction_date';
        }
        $settledDateColumns[] = 't.transaction_date';
        $settledDateColumns[] = "NULLIF(scsp.order_date, '0000-00-00')";
        if ($hasCreditCreatedAt) {
            $settledDateColumns[] = 'scsp.created_at';
        }
        $settledDateExpression = 'COALESCE('.implode(', ', $settledDateColumns).')';
        $settledAmountExpression = 'COALESCE(NULLIF(scsp.sub_total, 0), NULLIF(scsp.amount, 0), COALESCE(scsp.qty, 0) * COALESCE(scsp.price, 0), t.final_total, 0)';

        /*
         * Keep each settlement credit-sale row as an individual bill.  Do not join
         * all product variations by product_id: that old join duplicated bills and
         * made date-filter results appear incorrect.
         */
        $query = DB::table('settlement_credit_sale_payments as scsp')
            ->leftJoin('transactions as t', function ($join) {
                $join->on('t.credit_sale_id', '=', 'scsp.id')
                    ->orOn('t.id', '=', 'scsp.transaction_id');
            });

        if ($joinSettlement) {
            $query->leftJoin('settlements as st', function ($join) use ($hasSettlementNumber) {
                $join->on('st.business_id', '=', 'scsp.business_id')
                    ->on(function ($settlementKeyJoin) use ($hasSettlementNumber) {
                        $settlementKeyJoin->on('st.id', '=', 'scsp.settlement_no');
                        if ($hasSettlementNumber) {
                            $settlementKeyJoin->orOn('st.settlement_no', '=', 'scsp.settlement_no');
                        }
                    });
            });
        }

        $query->leftJoin('products as p', 'p.id', '=', 'scsp.product_id')
            ->leftJoin('contacts as c', 'c.id', '=', 'scsp.customer_id')
            ->leftJoin('business_locations as bl', function ($join) use ($locationExpression) {
                $join->on('bl.id', '=', DB::raw($locationExpression));
            })
            ->where('scsp.business_id', $businessId)
            ->whereRaw(
                'DATE('.$settledDateExpression.') BETWEEN ? AND ?',
                [$startDate, $endDate]
            )
            ->when($locationId, function ($builder) use ($locationId, $locationExpression) {
                $builder->whereRaw($locationExpression.' = ?', [$locationId]);
            })
            ->select([
                DB::raw("CONCAT('settlement:', scsp.id) as bill_key"),
                DB::raw("'settlement' as source_type"),
                'scsp.id as source_id',
                'scsp.id as line_id',
                'scsp.id as credit_bill_id',
                DB::raw($settledDateExpression.' as settlement_date'),
                DB::raw($settledAmountExpression.' as bill_total'),
                DB::raw($settledAmountExpression.' as line_total'),
                'p.name as description',
                'scsp.qty as balance_qty',
                'scsp.order_date',
                'scsp.price as unit_price',
                't.ref_no as our_ref',
                't.invoice_no',
                'c.name as customer',
                'scsp.order_number as order_no',
                DB::raw($customerReferenceExpression.' as customer_reference'),
                DB::raw($settlementReferenceExpression.' as settlement_reference'),
                DB::raw($voucherExpression.' as voucher_no'),
                DB::raw($locationExpression.' as location_id'),
                'bl.name as location',
                'bl.mobile as tel',
            ])
            ->orderByDesc('settlement_date')
            ->orderByDesc('scsp.id');

        $settledRows = $query->get();

        /*
         * Also include final POS credit invoices that do not already have a
         * settlement-credit row. The correlated exclusion checks both supported
         * links and prevents the same bill appearing from both sources.
         */
        $posQuery = DB::table('transactions as pos_t')
            ->join('transaction_sell_lines as pos_line', 'pos_line.transaction_id', '=', 'pos_t.id')
            ->leftJoin('products as pos_p', 'pos_p.id', '=', 'pos_line.product_id')
            ->leftJoin('contacts as pos_c', 'pos_c.id', '=', 'pos_t.contact_id')
            ->leftJoin('business_locations as pos_bl', 'pos_bl.id', '=', 'pos_t.location_id')
            ->where('pos_t.business_id', $businessId)
            ->where('pos_t.type', 'sell')
            ->where('pos_t.status', 'final')
            ->whereNull('pos_t.deleted_at')
            ->whereDate('pos_t.transaction_date', '>=', $startDate)
            ->whereDate('pos_t.transaction_date', '<=', $endDate)
            ->when($locationId, function ($builder) use ($locationId) {
                $builder->where('pos_t.location_id', $locationId);
            })
            ->when($hasSellLineDeletedAt, function ($builder) {
                $builder->whereNull('pos_line.deleted_at');
            })
            ->where(function ($builder) use ($hasPaymentDeletedAt) {
                $builder->where('pos_t.is_credit_sale', 1)
                    ->orWhereIn('pos_t.payment_status', ['due', 'partial'])
                    ->orWhereExists(function ($paymentQuery) use ($hasPaymentDeletedAt) {
                        $paymentQuery->select(DB::raw(1))
                            ->from('transaction_payments as pos_payment')
                            ->whereColumn('pos_payment.transaction_id', 'pos_t.id')
                            ->whereIn('pos_payment.method', ['credit', 'credit_sale']);
                        if ($hasPaymentDeletedAt) {
                            $paymentQuery->whereNull('pos_payment.deleted_at');
                        }
                    });
            })
            ->whereNotExists(function ($settlementQuery) {
                $settlementQuery->select(DB::raw(1))
                    ->from('settlement_credit_sale_payments as linked_scsp')
                    ->whereColumn('linked_scsp.business_id', 'pos_t.business_id')
                    ->where(function ($linkQuery) {
                        $linkQuery->whereColumn('linked_scsp.transaction_id', 'pos_t.id')
                            ->orWhereColumn('linked_scsp.id', 'pos_t.credit_sale_id');
                    });
            })
            ->whereNotNull('pos_line.product_id')
            ->select([
                DB::raw("CONCAT('transaction:', pos_t.id) as bill_key"),
                DB::raw("'transaction' as source_type"),
                'pos_t.id as source_id',
                'pos_line.id as line_id',
                'pos_t.id as credit_bill_id',
                'pos_t.transaction_date as settlement_date',
                'pos_t.final_total as bill_total',
                DB::raw('COALESCE(NULLIF(pos_line.line_total, 0), COALESCE(pos_line.quantity, 0) * COALESCE(pos_line.unit_price_inc_tax, pos_line.unit_price, 0), 0) as line_total'),
                'pos_p.name as description',
                'pos_line.quantity as balance_qty',
                'pos_t.transaction_date as order_date',
                DB::raw('COALESCE(pos_line.unit_price_inc_tax, pos_line.unit_price, 0) as unit_price'),
                'pos_t.ref_no as our_ref',
                'pos_t.invoice_no',
                'pos_c.name as customer',
                'pos_t.invoice_no as order_no',
                'pos_t.ref_no as customer_reference',
                'pos_t.ref_no as settlement_reference',
                'pos_t.invoice_no as voucher_no',
                'pos_t.location_id',
                'pos_bl.name as location',
                'pos_bl.mobile as tel',
            ])
            ->orderByDesc('pos_t.transaction_date')
            ->orderByDesc('pos_t.id');

        $allRows = $settledRows
            ->concat($posQuery->get())
            ->sort(function ($left, $right) {
                $dateComparison = strcmp((string) $right->settlement_date, (string) $left->settlement_date);
                return $dateComparison !== 0
                    ? $dateComparison
                    : ((int) $right->source_id <=> (int) $left->source_id);
            });

        $creditSales = $allRows
            ->groupBy('bill_key')
            ->map(function ($billRows) use ($quantityPrecision, $currencyPrecision) {
                $billRows = $billRows
                    ->unique(function ($row) {
                        return $row->source_type.':'.$row->line_id;
                    })
                    ->values();

                $bill = clone $billRows->first();
                $bill->settlement_date_display = ! empty($bill->settlement_date)
                    ? Carbon::parse($bill->settlement_date)->format('m/d/Y')
                    : '-';
                $bill->lines = $billRows->map(function ($line) use ($quantityPrecision, $currencyPrecision) {
                    return (object) [
                        'voucher_no' => $line->voucher_no,
                        'balance_qty' => number_format((float) ($line->balance_qty ?? 0), $quantityPrecision, '.', ','),
                        'description' => $line->description,
                        'unit_price' => number_format((float) ($line->unit_price ?? 0), $currencyPrecision, '.', ','),
                        'line_total' => number_format((float) ($line->line_total ?? 0), $currencyPrecision, '.', ','),
                    ];
                });

                $billTotal = (float) ($bill->bill_total ?? 0);
                if ($billTotal == 0.0) {
                    $billTotal = (float) $billRows->sum('line_total');
                }
                $bill->final_total = number_format($billTotal, $currencyPrecision, '.', ',');

                return $bill;
            })
            ->values();

        // One pagination page equals one A4 landscape sheet: three columns
        // by three rows for a maximum of nine standard F14 bills.
        $perPage = 9;
        $totalBills = $creditSales->count();
        $lastPage = max(1, (int) ceil($totalBills / $perPage));
        $currentPage = min(
            max(1, (int) $request->input('f14_page', 1)),
            $lastPage
        );

        $credit_sales = new LengthAwarePaginator(
            $creditSales->forPage($currentPage, $perPage)->values(),
            $totalBills,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->except('f14_page'),
                'pageName' => 'f14_page',
            ]
        );

        // Keep the variable names used by the existing Blade view.
        $business_locations = $businessLocations;
        $start_date = $startDate;
        $end_date = $endDate;
        $location_id = $locationId;
        $business_name = $business->name ?? $request->session()->get('business.name') ?? '';
        $date_range_display = Carbon::parse($startDate)->format('m/d/Y')
            .' ~ '.Carbon::parse($endDate)->format('m/d/Y');

        return view('mpcs::forms.F14_form', compact(
            'credit_sales',
            'business_locations',
            'start_date',
            'end_date',
            'location_id',
            'business_name',
            'date_range_display'
        ));
    }

    /**
     * Accept the explicit hidden dates or the visible date-range control.
     * Invalid input falls back safely instead of redirecting to an exception page.
     */
    protected function resolveDateRange(Request $request): array
    {
        $start = $request->input('f14_start_date', $request->input('start_date'));
        $end = $request->input('f14_end_date', $request->input('end_date'));

        $dateRange = $request->input('f14_date_range', $request->input('date_range'));
        if ((! $start || ! $end) && ! empty($dateRange)) {
            $parts = preg_split('/\s+(?:~|-)\s+/', trim((string) $dateRange), 2);
            if (count($parts) === 2) {
                [$start, $end] = $parts;
            }
        }

        $parse = function ($value, Carbon $fallback): Carbon {
            if (! $value) {
                return $fallback;
            }

            foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'] as $format) {
                try {
                    $parsed = Carbon::createFromFormat($format, trim((string) $value));
                    if ($parsed !== false) {
                        return $parsed->startOfDay();
                    }
                } catch (\Throwable $e) {
                    // Try the next accepted format.
                }
            }

            try {
                return Carbon::parse($value)->startOfDay();
            } catch (\Throwable $e) {
                return $fallback;
            }
        };

        $defaultStart = Carbon::today();
        $defaultEnd = Carbon::today();
        $startDate = $parse($start, $defaultStart);
        $endDate = $parse($end, $defaultEnd);

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')];
    }
    
 
}
