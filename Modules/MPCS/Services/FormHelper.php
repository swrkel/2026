<?php
namespace Modules\MPCS\Services;

use App\Transaction;
use App\VariationLocationDetails;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\MPCS\Entities\FormF22Detail;

class FormHelper
{
    public static function getActiveCategoryIds($business_id)
    {
        $selection = DB::table('mpcs_f15_category_selections')
            ->where('business_id', $business_id)
            ->orderBy('created_at', 'desc')
            ->first();
        if ($selection) {
            $ids = json_decode($selection->category_ids, true);
            if (is_array($ids) && !empty($ids)) {
                return $ids;
            }
        }
        return null;
    }

    public static function getResolvedCategoryIdsForFiltering($business_id, $category_ids)
    {
        if (empty($category_ids)) {
            return [];
        }
        $categories = DB::table('categories')
            ->where('business_id', $business_id)
            ->select('id', 'parent_id')
            ->get();

        $resolved = [];
        foreach ($category_ids as $id) {
            $resolved[] = (int) $id;
            $children = $categories->where('parent_id', $id)->pluck('id')->toArray();
            foreach ($children as $cid) {
                $resolved[] = (int) $cid;
            }
        }
        return array_unique($resolved);
    }

    public static function getLubricantAndGasCategoryIds($business_id)
    {
        $rootCategoryIds = DB::table('categories')
            ->where('business_id', $business_id)
            ->where('parent_id', 0)
            ->where(function($q) {
                $q->where('name', 'like', '%Lubricant%')
                  ->orWhere('name', 'like', '%Gas%');
            })
            ->pluck('id')
            ->toArray();

        if (empty($rootCategoryIds)) {
            return [];
        }

        return self::getResolvedCategoryIdsForFiltering($business_id, $rootCategoryIds);
    }

    public static function getF15TargetCategoryIds($business_id)
    {
        $lubricantGasIds = self::getLubricantAndGasCategoryIds($business_id);
        if (!empty($lubricantGasIds)) {
            return $lubricantGasIds;
        }

        $category_ids = self::getActiveCategoryIds($business_id);
        if (empty($category_ids)) {
            return [];
        }
        return self::getResolvedCategoryIdsForFiltering($business_id, $category_ids);
    }

    public static function getCardAsToDay($business_id, $start_date, $end_date, $location_id = null)
    {
        $start = Carbon::parse($start_date)->toDateString();
        $end = Carbon::parse($end_date)->toDateString();

        $resolved_ids = self::getF15TargetCategoryIds($business_id);
        if (empty($resolved_ids)) {
            return 0.0;
        }

        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin(DB::raw('(SELECT transaction_id, SUM(amount) as card_payment_amount FROM transaction_payments WHERE method = "card" AND deleted_at IS NULL GROUP BY transaction_id) as tp'), 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('t.deleted_at')
            ->whereNull('tsl.deleted_at')
            ->whereDate('t.transaction_date', '>=', $start)
            ->whereDate('t.transaction_date', '<=', $end)
            ->whereNotNull('tp.card_payment_amount');

        if (!empty($location_id)) {
            $query->where('t.location_id', $location_id);
        }

        $data = $query
            ->select(
                't.id',
                'tp.card_payment_amount',
                DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as total_sell_amount'),
                DB::raw('SUM(CASE WHEN p.category_id IN (' . implode(',', $resolved_ids) . ') OR p.sub_category_id IN (' . implode(',', $resolved_ids) . ') THEN tsl.unit_price_inc_tax * tsl.quantity ELSE 0 END) as selected_sell_amount')
            )
            ->groupBy('t.id', 'tp.card_payment_amount')
            ->get();

        $total_card = 0.0;
        foreach ($data as $row) {
            if ($row->total_sell_amount > 0) {
                $total_card += $row->card_payment_amount * ($row->selected_sell_amount / $row->total_sell_amount);
            }
        }

        return $total_card;
    }

    public static function getF15TotalSalesForDateRange($businessId, $startDate, $endDate, $locationId = null): float
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();

        $resolved_ids = self::getF15TargetCategoryIds($businessId);
        if (empty($resolved_ids)) {
            return 0.0;
        }

        $query = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('t.deleted_at')
            ->whereNull('tsl.deleted_at')
            ->whereDate('t.transaction_date', '>=', $start)
            ->whereDate('t.transaction_date', '<=', $end);

        if (!empty($locationId)) {
            $query->where('t.location_id', $locationId);
        }

        $query->where(function($q) use ($resolved_ids) {
            $q->whereIn('p.category_id', $resolved_ids)
              ->orWhereIn('p.sub_category_id', $resolved_ids);
        });

        return (float) $query->sum(DB::raw('tsl.unit_price_inc_tax * tsl.quantity'));
    }

    public static function getCashAsToDay($business_id, $start_date, $end_date, $location_id = null)
    {
        $totalSales = self::getF15TotalSalesForDateRange($business_id, $start_date, $end_date, $location_id);
        $creditSales = self::getCreditAsToDay($business_id, $start_date, $end_date, $location_id);
        $cardSales = self::getCardAsToDay($business_id, $start_date, $end_date, $location_id);

        return max(0.0, $totalSales - $creditSales - $cardSales);
    }

    public static function getCreditAsToDay($business_id, $start_date, $end_date, $location_id = null)
    {
        $start = Carbon::parse($start_date)->toDateString();
        $end = Carbon::parse($end_date)->toDateString();

        $resolved_ids = self::getF15TargetCategoryIds($business_id);
        if (empty($resolved_ids)) {
            return 0.0;
        }

        // FULL CREDIT (no payment yet)
        $credit_full = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.is_credit_sale', 1)
            ->whereNull('t.deleted_at')
            ->whereNull('tsl.deleted_at')
            ->whereDate('t.transaction_date', '>=', $start)
            ->whereDate('t.transaction_date', '<=', $end)
            ->where(function($q) use ($resolved_ids) {
                $q->whereIn('p.category_id', $resolved_ids)
                  ->orWhereIn('p.sub_category_id', $resolved_ids);
            });

        if (!empty($location_id)) {
            $credit_full->where('t.location_id', $location_id);
        }

        $credit_full_total = (float) $credit_full->sum(DB::raw('tsl.unit_price_inc_tax * tsl.quantity'));

        // PARTIAL CREDIT PAYMENTS
        $credit_partial = DB::table('transactions as t')
            ->join('transaction_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'tsl.product_id', '=', 'p.id')
            ->leftJoin(DB::raw('(SELECT transaction_id, SUM(amount) as credit_payment_amount FROM transaction_payments WHERE method = "credit_sale" AND deleted_at IS NULL GROUP BY transaction_id) as tp'), 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereNull('t.deleted_at')
            ->whereNull('tsl.deleted_at')
            ->whereDate('t.transaction_date', '>=', $start)
            ->whereDate('t.transaction_date', '<=', $end)
            ->whereNotNull('tp.credit_payment_amount');

        if (!empty($location_id)) {
            $credit_partial->where('t.location_id', $location_id);
        }

        $partial_credit_data = $credit_partial
            ->select(
                't.id',
                'tp.credit_payment_amount',
                DB::raw('SUM(tsl.unit_price_inc_tax * tsl.quantity) as total_sell_amount'),
                DB::raw('SUM(CASE WHEN p.category_id IN (' . implode(',', $resolved_ids) . ') OR p.sub_category_id IN (' . implode(',', $resolved_ids) . ') THEN tsl.unit_price_inc_tax * tsl.quantity ELSE 0 END) as selected_sell_amount')
            )
            ->groupBy('t.id', 'tp.credit_payment_amount')
            ->get();

        $partial_credit_total = 0.0;
        foreach ($partial_credit_data as $row) {
            if ($row->total_sell_amount > 0) {
                $partial_credit_total += $row->credit_payment_amount * ($row->selected_sell_amount / $row->total_sell_amount);
            }
        }

        // Petro Settlement Credit Sales
        $petro_query = DB::table('settlement_credit_sale_payments as cs')
            ->join('settlements as s', function($join) {
                $join->on('cs.settlement_no', '=', 's.id')
                     ->orOn('cs.settlement_no', '=', 's.settlement_no');
            })
            ->join('products as p', 'cs.product_id', '=', 'p.id')
            ->where('cs.business_id', $business_id)
            ->whereDate('s.transaction_date', '>=', $start)
            ->whereDate('s.transaction_date', '<=', $end)
            ->where(function($q) use ($resolved_ids) {
                $q->whereIn('p.category_id', $resolved_ids)
                  ->orWhereIn('p.sub_category_id', $resolved_ids);
            });

        if (!empty($location_id)) {
            $petro_query->where('s.location_id', $location_id);
        }

        $petro_credit_total = (float) $petro_query->sum(DB::raw('cs.amount - cs.total_discount'));

        return $credit_full_total + $partial_credit_total + $petro_credit_total;
    }

    public static function getPurchaseAsToDay($businessId, $start_date, $end_date)
    {
        $resolved_ids = self::getF15TargetCategoryIds($businessId);
        if (empty($resolved_ids)) {
            return 0.0;
        }

        $query = Transaction::leftJoin('purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')
            ->leftJoin('variations as v', 'pl.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->where('transactions.business_id', $businessId)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereBetween('transactions.transaction_date', [$start_date, $end_date]);

        $query->where(function($q) use ($resolved_ids) {
            $q->whereIn('p.category_id', $resolved_ids)
              ->orWhereIn('p.sub_category_id', $resolved_ids);
        });

        return (float) $query->select(DB::raw('
            SUM(v.dpp_inc_tax * pl.quantity) AS total_purchase_amount
        '))->value('total_purchase_amount');
    }

    public static function getPurchaseNotReturnAsToDay($businessId, $start_date, $end_date)
    {
        $resolved_ids = self::getF15TargetCategoryIds($businessId);
        if (empty($resolved_ids)) {
            return 0.0;
        }

        $query = Transaction::leftJoin('purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')
            ->leftJoin('variations as v', 'pl.variation_id', '=', 'v.id')
            ->leftJoin('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('transactions as pr', function ($join) {
                $join->on('transactions.id', '=', 'pr.return_parent_id')
                    ->where('pr.type', 'purchase_return')
                    ->where('pr.status', 'final');
            })
            ->where('transactions.business_id', $businessId)
            ->where('transactions.type', 'purchase')
            ->where('transactions.status', 'received')
            ->whereBetween('transactions.transaction_date', [$start_date, $end_date]);

        $query->where(function($q) use ($resolved_ids) {
            $q->whereIn('p.category_id', $resolved_ids)
              ->orWhereIn('p.sub_category_id', $resolved_ids);
        });

        return (float) $query->select(DB::raw('
            SUM(v.dpp_inc_tax * pl.quantity)
            - COALESCE(SUM(pr.final_total), 0)
            AS total_purchase_amount
        '))->value('total_purchase_amount');
    }

    public static function getOpeningStockAsToDay($business_id, $today)
    {
        $today = Carbon::parse($today);
        $resolved_ids = self::getF15TargetCategoryIds($business_id);
        if (empty($resolved_ids)) {
            return [
                'opening_stock_previous' => 0.0,
                'opening_stock_today'    => 0.0,
                'opening_stocking_previous' => 0.0,
                'opening_stocking_today'    => 0.0,
            ];
        }

        // Check if today is an inventory day
        $todayF22DetailsQuery = FormF22Detail::join('products', 'form_f22_details.product_code', '=', 'products.sku')
            ->where('products.business_id', $business_id)
            ->whereDate('form_f22_details.created_at', $today);

        $todayF22DetailsQuery->where(function($q) use ($resolved_ids) {
            $q->whereIn('products.category_id', $resolved_ids)
              ->orWhereIn('products.sub_category_id', $resolved_ids);
        });

        $todayF22Details = $todayF22DetailsQuery->orderBy('form_f22_details.created_at', 'desc')
            ->get()
            ->groupBy('product_id')
            ->map(function ($group) {
                return $group->first();
            });

        // Today is an inventory day
        if ($todayF22Details->count() > 0) {
            $openingStockToday = $todayF22Details->sum(function ($detail) {
                return $detail->stock_count * $detail->unit_purchase_price;
            });

            return [
                'opening_stocking_previous' => 0,
                'opening_stocking_today'    => $openingStockToday,
                'opening_stock_previous'    => 0.0,
                'opening_stock_today'       => $openingStockToday,
            ];
        }

        // If today is not an inventory day, check the yesterday
        $yesterday    = $today->copy()->subDay();
        $yesterdayF22Query = FormF22Detail::where('business_id', $business_id)
            ->whereDate('created_at', $yesterday);

        $yesterdayF22 = $yesterdayF22Query->get();

        if ($yesterdayF22->count() > 0) {
            // Check if yesterday is an inventory day
            $openingStockPreviousDataQuery = FormF22Detail::join('products', 'form_f22_details.product_code', '=', 'products.sku')
                ->where('products.business_id', $business_id)
                ->whereDate('form_f22_details.created_at', $yesterday);

            $openingStockPreviousDataQuery->where(function($q) use ($resolved_ids) {
                $q->whereIn('products.category_id', $resolved_ids)
                  ->orWhereIn('products.sub_category_id', $resolved_ids);
            });

            $openingStockPreviousData = $openingStockPreviousDataQuery->orderBy('form_f22_details.created_at', 'desc')
                ->get()
                ->groupBy('product_code')
                ->map(function ($group) {
                    return $group->first();
                });

            $openingStockPrevious = $openingStockPreviousData->sum(function ($detail) {
                return $detail->current_stock * $detail->unit_purchase_price;
            });
        } else {
            //Yesterday is not an inventory day, so get data from the nearest inventory day
            $lastInventoryDateQuery = FormF22Detail::join('products', 'form_f22_details.product_code', '=', 'products.sku')
                ->where('products.business_id', $business_id)
                ->whereDate('form_f22_details.created_at', '<', $yesterday);

            $lastInventoryDateQuery->where(function($q) use ($resolved_ids) {
                $q->whereIn('products.category_id', $resolved_ids)
                  ->orWhereIn('products.sub_category_id', $resolved_ids);
            });

            $lastInventoryDate = $lastInventoryDateQuery->max('form_f22_details.created_at');

            if (! $lastInventoryDate) {
                $openingStockPrevious = 0;
            } else {
                $lastF22DetailsQuery = FormF22Detail::join('products', 'form_f22_details.product_code', '=', 'products.sku')
                    ->where('products.business_id', $business_id)
                    ->whereDate('form_f22_details.created_at', $lastInventoryDate);

                $lastF22DetailsQuery->where(function($q) use ($resolved_ids) {
                    $q->whereIn('products.category_id', $resolved_ids)
                      ->orWhereIn('products.sub_category_id', $resolved_ids);
                });

                $lastF22Details = $lastF22DetailsQuery->orderBy('form_f22_details.created_at', 'desc')
                    ->get()
                    ->groupBy('product_code')
                    ->map(function ($group) {
                        return $group->first();
                    });

                $stockLastF22Value = $lastF22Details->sum(function ($detail) {
                    return $detail->stock_count * $detail->unit_purchase_price;
                });

                $movementsQuery = VariationLocationDetails::join('products', 'variation_location_details.product_id', '=', 'products.id')
                    ->join('variations', 'variations.product_id', '=', 'products.id')
                    ->whereDate('variation_location_details.created_at', '>', $lastInventoryDate)
                    ->whereDate('variation_location_details.created_at', '<=', $yesterday);

                $movementsQuery->where(function($q) use ($resolved_ids) {
                    $q->whereIn('products.category_id', $resolved_ids)
                      ->orWhereIn('products.sub_category_id', $resolved_ids);
                });

                $movements = $movementsQuery->select(
                        'variation_location_details.product_id',
                        'variation_location_details.qty_available',
                        'variations.dpp_inc_tax'
                    )
                    ->get()
                    ->groupBy('product_id');

                $movementValue = $movements->sum(function ($group) {
                    $totalQty    = $group->sum('qty_available');
                    $dpp_inc_tax = $group->first()->dpp_inc_tax ?? 0;

                    return $totalQty * $dpp_inc_tax;
                });

                $openingStockPrevious = $stockLastF22Value + $movementValue;
            }
        }

        $openingStockTodayDetailsQuery = VariationLocationDetails::join('products', 'variation_location_details.product_id', '=', 'products.id')
            ->join('variations', 'variations.product_id', '=', 'products.id')
            ->whereDate('variation_location_details.created_at', '=', $today);

        $openingStockTodayDetailsQuery->where(function($q) use ($resolved_ids) {
            $q->whereIn('products.category_id', $resolved_ids)
              ->orWhereIn('products.sub_category_id', $resolved_ids);
        });

        $openingStockTodayDetails = $openingStockTodayDetailsQuery->select(
                'variation_location_details.product_id',
                'variation_location_details.qty_available',
                'variations.dpp_inc_tax'
            )
            ->get()
            ->groupBy('product_id');

        $openingStockToday = $openingStockTodayDetails->sum(function ($group) {
            return $group->sum(function ($item) {
                return ($item->qty_available ?? 0) * ($item->dpp_inc_tax ?? 0);
            });
        });

        return [
            'opening_stock_previous' => $openingStockPrevious,
            'opening_stock_today'    => $openingStockToday,
            'opening_stocking_previous' => $openingStockPrevious,
            'opening_stocking_today'    => $openingStockToday,
        ];
    }

    /**
     * True when an F22 stock-taking header exists for the calendar date (optional location).
     */
    public static function isF22StockTakingDate(int $businessId, string $date, $locationId = null): bool
    {
        $q = DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->whereDate('form_date', Carbon::parse($date)->toDateString());
        if ($locationId !== null && $locationId !== '') {
            $q->where('location_id', (int) $locationId);
        }

        return $q->exists();
    }

    /**
     * Start date of the active F15 accounting cycle.
     *
     * The cycle starts on the first calendar day of the selected month, unless an
     * F22 stock-taking form was saved later in that month. In that case the latest
     * F22 date becomes the new cycle start. This single rule is shared by purchases
     * and Cash/Card/Credit carries, preventing pre-reset values from reappearing.
     */
    public static function getF15CycleStartDate(int $businessId, string $date, $locationId = null): string
    {
        $selected = Carbon::parse($date)->startOfDay();
        $monthStart = $selected->copy()->startOfMonth();

        $query = DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->whereDate('form_date', '>=', $monthStart->toDateString())
            ->whereDate('form_date', '<=', $selected->toDateString());

        if ($locationId !== null && $locationId !== '') {
            $query->where('location_id', (int) $locationId);
        }

        $lastF22Date = $query
            ->orderBy('form_date', 'desc')
            ->orderBy('id', 'desc')
            ->value('form_date');

        return !empty($lastF22Date)
            ? Carbon::parse($lastF22Date)->toDateString()
            : $monthStart->toDateString();
    }

    protected static function getF15SalesCarryStartDate(int $businessId, string $date, $locationId = null): string
    {
        return self::getF15CycleStartDate($businessId, $date, $locationId);
    }

    /**
     * F15 Credit Sale row — running "As of Today" total since the latest F22 reset.
     */
    public static function getF15CreditSaleAsOfTodayForDate(int $businessId, string $date, $locationId = null): float
    {
        $d = Carbon::parse($date)->toDateString();
        $carryStart = self::getF15SalesCarryStartDate($businessId, $d, $locationId);

        return self::getCreditAsToDay($businessId, $carryStart, $d, $locationId);
    }

    /**
     * F15 Card Sale row — running "As of Today" total since the latest F22 reset.
     */
    public static function getF15CardSaleAsOfTodayForDate(int $businessId, string $date, $locationId = null): float
    {
        $d = Carbon::parse($date)->toDateString();
        $carryStart = self::getF15SalesCarryStartDate($businessId, $d, $locationId);

        return self::getCardAsToDay($businessId, $carryStart, $d, $locationId);
    }

    /**
     * F15 Cash Sale row — running "As of Today" total since the latest F22 reset.
     */
    public static function getF15CashSaleAsOfTodayForDate(int $businessId, string $date, $locationId = null): float
    {
        $d = Carbon::parse($date)->toDateString();
        $carryStart = self::getF15SalesCarryStartDate($businessId, $d, $locationId);

        return self::getCashAsToDay($businessId, $carryStart, $d, $locationId);
    }

    /**
     * F15 row "Opening Stock" (client §18): F22 stock-taking date, totals at sale price, Ref Book No.
     * Mirrors getFormF15Data Opening Stock logic; optional location_id on form_f22_headers.
     */
    public static function getF15OpeningStockRowFromF22(int $businessId, string $selectedDate, $locationId = null): array
    {
        $selectedDate = Carbon::parse($selectedDate)->toDateString();
        $previousDate = Carbon::parse($selectedDate)->subDay()->toDateString();
        $resolved_ids = self::getF15TargetCategoryIds($businessId);
        if (empty($resolved_ids)) {
            return [
                'opening_stock_previous' => 0.0,
                'opening_stock_today' => 0.0,
                'opening_stock_as_of_display' => 0.0,
                'opening_f22_book_refs' => '',
            ];
        }

        $todayHeadersQuery = DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->whereDate('form_date', $selectedDate);
        if ($locationId !== null && $locationId !== '') {
            $todayHeadersQuery->where('location_id', (int) $locationId);
        }

        $isF22SavedToday = self::isF22StockTakingDate($businessId, $selectedDate, $locationId);

        $lastF22Query = DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->whereDate('form_date', '<', $selectedDate);
        if ($locationId !== null && $locationId !== '') {
            $lastF22Query->where('location_id', (int) $locationId);
        }
        $lastF22 = $lastF22Query->orderBy('form_date', 'desc')->first();

        $lastF22Total = 0.0;
        if ($lastF22) {
            $lastF22DetailsQuery = DB::table('form_f22_details')
                ->where('header_id', $lastF22->id);
            $lastF22DetailsQuery->join('products as p', 'form_f22_details.product_code', '=', 'p.sku')
                ->where(function($q) use ($resolved_ids) {
                    $q->whereIn('p.category_id', $resolved_ids)
                      ->orWhereIn('p.sub_category_id', $resolved_ids);
                });
            $lastF22Total = (float) $lastF22DetailsQuery->sum('form_f22_details.sales_price_total');
        }

        $currentF22Total = 0.0;
        if ($isF22SavedToday) {
            $headerIds = (clone $todayHeadersQuery)->pluck('id');
            if ($headerIds->isNotEmpty()) {
                $currentF22TotalQuery = DB::table('form_f22_details')
                    ->whereIn('header_id', $headerIds->all());
                $currentF22TotalQuery->join('products as p', 'form_f22_details.product_code', '=', 'p.sku')
                    ->where(function($q) use ($resolved_ids) {
                        $q->whereIn('p.category_id', $resolved_ids)
                          ->orWhereIn('p.sub_category_id', $resolved_ids);
                    });
                $currentF22Total = (float) $currentF22TotalQuery->sum('form_f22_details.sales_price_total');
            }
        }

        // (a) Non–stock-taking days: previous calendar day's F22 total at sale price
        $prevDayHeadersQuery = DB::table('form_f22_headers')
            ->where('business_id', $businessId)
            ->whereDate('form_date', $previousDate);
        if ($locationId !== null && $locationId !== '') {
            $prevDayHeadersQuery->where('location_id', (int) $locationId);
        }
        $prevDayHeaderIds = (clone $prevDayHeadersQuery)->pluck('id');

        $previousDayF22BalanceAtSalePrice = 0.0;
        $hasF22OnPreviousDay = $prevDayHeaderIds->isNotEmpty();
        if ($hasF22OnPreviousDay) {
            $previousDayF22BalanceQuery = DB::table('form_f22_details')
                ->whereIn('header_id', $prevDayHeaderIds->all());
            $previousDayF22BalanceQuery->join('products as p', 'form_f22_details.product_code', '=', 'p.sku')
                ->where(function($q) use ($resolved_ids) {
                    $q->whereIn('p.category_id', $resolved_ids)
                      ->orWhereIn('p.sub_category_id', $resolved_ids);
                });
            $previousDayF22BalanceAtSalePrice = (float) $previousDayF22BalanceQuery->sum('form_f22_details.sales_price_total');
        }

        $previousF15Header = DB::table('mpcs_form_f15_headers')
            ->where('business_id', $businessId)
            ->whereDate('dated_at', $previousDate)
            ->first();

        $previousBalanceTodayFromF15 = 0.0;
        if ($previousF15Header) {
            $balanceRow = DB::table('mpcs_form_f15_details')
                ->join('form_f15_transaction_data', 'mpcs_form_f15_details.form15_label_id', '=', 'form_f15_transaction_data.id')
                ->where('mpcs_form_f15_details.f15_form_id', $previousF15Header->id)
                ->where('form_f15_transaction_data.description', 'Balance Stock in Sale Price')
                ->select('mpcs_form_f15_details.rupees')
                ->first();
            $previousBalanceTodayFromF15 = (float) ($balanceRow->rupees ?? 0);
        }

        // No. 18 rules:
        // Up to Previous Date is zero only on an F22 stock-taking date.
        // Today carries the previous day's F15 Balance Stock in Sale Price until the next F22;
        // on an F22 date it becomes that F22 stock value at sale price.
        $openingPrevious = $isF22SavedToday
            ? 0.0
            : ($previousBalanceTodayFromF15 != 0.0 ? $previousBalanceTodayFromF15 : $lastF22Total);
        $openingToday = $isF22SavedToday
            ? $currentF22Total
            : ($previousBalanceTodayFromF15 != 0.0
                ? $previousBalanceTodayFromF15
                : ($hasF22OnPreviousDay ? $previousDayF22BalanceAtSalePrice : $lastF22Total));

        $openingAsOfDisplay = $openingToday;

        $formNos = (clone $todayHeadersQuery)->orderBy('form_no')->pluck('form_no')->unique()->values();
        $bookRefs = $formNos->isEmpty()
            ? ''
            : $formNos->map(function ($n) {
                return 'F22/' . $n;
            })->implode(', ');

        return [
            'opening_stock_previous' => $openingPrevious,
            'opening_stock_today' => $openingToday,
            'opening_stock_as_of_display' => $openingAsOfDisplay,
            'opening_f22_book_refs' => $bookRefs,
        ];
    }

    /**
     * F16A source purchases for F15.
     *
     * A purchase must become visible in F15 immediately after Purchase / Save.
     * Therefore this query is based on the purchase transaction and purchase lines,
     * not on form_f16_details (which may not exist until the user later opens/saves F16A).
     */
    protected static function f16aPurchasesForDateRangeQuery(int $businessId, $start_date, $end_date, $location_id = null)
    {
        $start = Carbon::parse($start_date)->format('Y-m-d');
        $end = Carbon::parse($end_date)->format('Y-m-d');
        $resolvedIds = self::getF15TargetCategoryIds($businessId);

        $query = DB::table('transactions as t')
            ->join('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
            ->join('products as p', 'p.id', '=', 'pl.product_id')
            ->leftJoin('variations as v', 'v.id', '=', 'pl.variation_id')
            ->where('t.business_id', $businessId)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereDate('t.transaction_date', '>=', $start)
            ->whereDate('t.transaction_date', '<=', $end);

        if (Schema::hasColumn('transactions', 'deleted_at')) {
            $query->whereNull('t.deleted_at');
        }
        if (Schema::hasColumn('purchase_lines', 'deleted_at')) {
            $query->whereNull('pl.deleted_at');
        }
        if ($location_id !== null && $location_id !== '') {
            $query->where('t.location_id', (int) $location_id);
        }

        // Preserve the categories configured for F15.  If no F15 category has
        // been configured, return no direct-purchase value rather than mixing
        // unrelated purchases into the statutory form.
        if (empty($resolvedIds)) {
            $query->whereRaw('1 = 0');
        } else {
            $query->where(function ($sub) use ($resolvedIds) {
                $sub->whereIn('p.category_id', $resolvedIds)
                    ->orWhereIn('p.sub_category_id', $resolvedIds);
            });
        }

        return $query;
    }

    protected static function f16aSalePriceExpression(): string
    {
        $parts = [];
        if (Schema::hasColumn('purchase_lines', 'sell_price_at_purchase')) {
            $parts[] = 'NULLIF(pl.sell_price_at_purchase, 0)';
        }
        if (Schema::hasColumn('variations', 'sell_price_inc_tax')) {
            $parts[] = 'NULLIF(v.sell_price_inc_tax, 0)';
        }
        if (Schema::hasColumn('variations', 'default_sell_price')) {
            $parts[] = 'NULLIF(v.default_sell_price, 0)';
        }

        // The requested value is at SALE PRICE.  Do not silently fall back to
        // purchase price, because doing so produces a plausible but wrong F15 value.
        $parts[] = '0';

        return 'COALESCE(' . implode(', ', $parts) . ')';
    }

    public static function getF16aSaleTotalForDateRange(int $businessId, $start_date, $end_date, $location_id = null): float
    {
        $salePrice = self::f16aSalePriceExpression();

        return (float) self::f16aPurchasesForDateRangeQuery(
            $businessId,
            $start_date,
            $end_date,
            $location_id
        )->selectRaw(
            "COALESCE(SUM(($salePrice) * COALESCE(pl.quantity, 0)), 0) AS sale_amount"
        )->value('sale_amount');
    }

    /**
     * Calculate the F16A number for a transaction date when the form detail row
     * has not yet been saved. This mirrors F16AFormController's date numbering.
     */
    protected static function calculateF16aFormNoForDate(int $businessId, string $date)
    {
        if (! Schema::hasTable('mpcs_16a_form_settings')) {
            return null;
        }

        $settings = DB::table('mpcs_16a_form_settings')
            ->where('business_id', $businessId)
            ->whereNotNull('date')
            ->whereNotNull('starting_number')
            ->orderBy('date')
            ->get();

        if ($settings->isEmpty()) {
            return null;
        }

        $selectedDate = Carbon::parse($date)->startOfDay();
        $setting = $settings
            ->filter(function ($row) use ($selectedDate) {
                return Carbon::parse($row->date)->startOfDay()->lte($selectedDate);
            })
            ->sortByDesc('date')
            ->first();

        if (! $setting) {
            $setting = $settings->first();
        }

        $openingDate = Carbon::parse($setting->date)->startOfDay();
        $days = $selectedDate->lt($openingDate)
            ? 0
            : $openingDate->diffInDays($selectedDate);

        return (int) $setting->starting_number + (int) $days;
    }

    public static function getF16aDistinctFormNosForDateRange(int $businessId, $start_date, $end_date, $location_id = null): array
    {
        $transactions = self::f16aPurchasesForDateRangeQuery(
            $businessId,
            $start_date,
            $end_date,
            $location_id
        )
            ->selectRaw('DISTINCT t.id AS transaction_id, DATE(t.transaction_date) AS transaction_date')
            ->orderBy('transaction_date')
            ->get();

        if ($transactions->isEmpty()) {
            return [];
        }

        $savedNumbers = collect();
        if (Schema::hasTable('form_f16_details')) {
            $savedNumbers = DB::table('form_f16_details')
                ->whereIn('transaction_id', $transactions->pluck('transaction_id')->all())
                ->whereNotNull('form_no')
                ->where('form_no', '!=', '')
                ->select('transaction_id', 'form_no')
                ->orderBy('id')
                ->get()
                ->groupBy('transaction_id')
                ->map(function ($rows) {
                    return $rows->last()->form_no;
                });
        }

        return $transactions
            ->map(function ($transaction) use ($businessId, $savedNumbers) {
                $saved = $savedNumbers->get($transaction->transaction_id);
                if ($saved !== null && $saved !== '') {
                    return $saved;
                }

                return self::calculateF16aFormNoForDate(
                    $businessId,
                    $transaction->transaction_date
                );
            })
            ->filter(function ($number) {
                return $number !== null && $number !== '';
            })
            ->unique()
            ->sort(function ($a, $b) {
                return strnatcasecmp((string) $a, (string) $b);
            })
            ->values()
            ->all();
    }

    public static function getF16aBookReferenceListForDateRange(int $businessId, $start_date, $end_date, $location_id = null): string
    {
        $numbers = self::getF16aDistinctFormNosForDateRange(
            $businessId,
            $start_date,
            $end_date,
            $location_id
        );

        return collect($numbers)
            ->map(function ($number) {
                return 'F16A/' . $number;
            })
            ->implode(', ');
    }

    public static function getF18ReceivedSaleTotalForDateRange(int $businessId, $start_date, $end_date, $location_id = null): float
    {
        $start = Carbon::parse($start_date)->toDateString();
        $end = Carbon::parse($end_date)->toDateString();
        $resolved_ids = self::getF15TargetCategoryIds($businessId);
        if (empty($resolved_ids)) {
            return 0.0;
        }

        $q = DB::table('form_f18_headers as h')
            ->join('form_f18_details as d', 'h.id', '=', 'd.header_id')
            ->join('products as p', 'd.product_id', '=', 'p.id')
            ->where('h.business_id', $businessId)
            ->whereDate('h.form_date', '>=', $start)
            ->whereDate('h.form_date', '<=', $end);

        if ($location_id !== null && $location_id !== '') {
            $q->where('h.from_location_id', (int) $location_id);
        }

        $q->where(function($sub) use ($resolved_ids) {
            $sub->whereIn('p.category_id', $resolved_ids)
                ->orWhereIn('p.sub_category_id', $resolved_ids);
        });

        return (float) $q->sum('d.received_sale_total');
    }

    public static function getF18DistinctFormNosForDateRange(int $businessId, $start_date, $end_date, $location_id = null): array
    {
        $start = Carbon::parse($start_date)->toDateString();
        $end = Carbon::parse($end_date)->toDateString();
        $resolved_ids = self::getF15TargetCategoryIds($businessId);
        if (empty($resolved_ids)) {
            return [];
        }

        $q = DB::table('form_f18_headers as h')
            ->join('form_f18_details as d', 'h.id', '=', 'd.header_id')
            ->join('products as p', 'd.product_id', '=', 'p.id')
            ->where('h.business_id', $businessId)
            ->whereDate('h.form_date', '>=', $start)
            ->whereDate('h.form_date', '<=', $end);

        if ($location_id !== null && $location_id !== '') {
            $q->where('h.from_location_id', (int) $location_id);
        }

        $q->where(function($sub) use ($resolved_ids) {
            $sub->whereIn('p.category_id', $resolved_ids)
                ->orWhereIn('p.sub_category_id', $resolved_ids);
        });

        return $q->select('h.form_no')
            ->distinct()
            ->orderBy('h.form_no')
            ->pluck('h.form_no')
            ->filter(function ($n) {
                return $n !== null && $n !== '';
            })
            ->unique()
            ->values()
            ->all();
    }

    public static function getF18BookReferenceListForDateRange(int $businessId, $start_date, $end_date, $location_id = null): string
    {
        $nos = self::getF18DistinctFormNosForDateRange($businessId, $start_date, $end_date, $location_id);
        if ($nos === []) {
            return '';
        }

        return collect($nos)->map(function ($n) {
            return 'F18/' . $n;
        })->implode(', ');
    }

    public static function getCashTodayExcludingFuel(int $businessId, string $date, $locationId = null): float
    {
        return self::getCashAsToDay($businessId, $date, $date, $locationId);
    }
}
