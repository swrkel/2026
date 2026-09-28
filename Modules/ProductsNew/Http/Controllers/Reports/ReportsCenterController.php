<?php

namespace Modules\ProductsNew\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ReportsCenterController extends Controller
{
    public function index(Request $request)
    {
        $cards = [
            [
                'title' => 'Product Master',
                'description' => 'Complete product, classification and status details.',
                'route' => 'products-new.reports.product-master',
                'permission' => 'products_new.reports.product_master',
                'icon' => 'fa-cubes',
                'theme' => 'blue',
            ],
            [
                'title' => 'Stock Valuation',
                'description' => 'Location-wise quantities and inventory value.',
                'route' => 'products-new.reports.stock-valuation',
                'permission' => 'products_new.reports.stock_valuation',
                'icon' => 'fa-calculator',
                'theme' => 'emerald',
            ],
            [
                'title' => 'Low Stock',
                'description' => 'Products at or below their alert quantity.',
                'route' => 'products-new.reports.low-stock',
                'permission' => 'products_new.reports.low_stock',
                'icon' => 'fa-level-down',
                'theme' => 'amber',
            ],
            [
                'title' => 'Price Changes',
                'description' => 'Audit trail of purchasing and selling price changes.',
                'route' => 'products-new.reports.price-changes',
                'permission' => 'products_new.reports.price_changes',
                'icon' => 'fa-exchange',
                'theme' => 'violet',
            ],
            [
                'title' => 'Batch / Lot',
                'description' => 'Batch balances, expiry dates and traceability.',
                'route' => 'products-new.reports.batch',
                'permission' => 'products_new.reports.batch',
                'icon' => 'fa-tags',
                'theme' => 'cyan',
            ],
            [
                'title' => 'Product Movement',
                'description' => 'Product and inventory movement analysis.',
                'route' => 'products-new.reports.product-movement',
                'permission' => 'products_new.reports.movement',
                'icon' => 'fa-random',
                'theme' => 'indigo',
            ],
            [
                'title' => 'Profitability',
                'description' => 'Product margin and profitability-ready information.',
                'route' => 'products-new.reports.profitability',
                'permission' => 'products_new.reports.profitability',
                'icon' => 'fa-line-chart',
                'theme' => 'green',
            ],
            [
                'title' => 'Product Aging',
                'description' => 'Age products using creation and movement dates.',
                'route' => 'products-new.reports.aging',
                'permission' => 'products_new.reports.aging',
                'icon' => 'fa-clock-o',
                'theme' => 'orange',
            ],
            [
                'title' => 'Fast / Slow / Dead',
                'description' => 'Movement-based stock performance classification.',
                'route' => 'products-new.reports.fast-slow-dead-stock',
                'permission' => 'products_new.reports.fast_slow_dead',
                'icon' => 'fa-tachometer',
                'theme' => 'rose',
            ],
            [
                'title' => 'Negative & Overstock',
                'description' => 'Identify negative balances and excess inventory.',
                'route' => 'products-new.reports.negative-overstock',
                'permission' => 'products_new.reports.negative_overstock',
                'icon' => 'fa-exclamation-triangle',
                'theme' => 'red',
            ],
            [
                'title' => 'Expiry',
                'description' => 'Monitor expired and soon-to-expire batches.',
                'route' => 'products-new.reports.expiry',
                'permission' => 'products_new.reports.expiry',
                'icon' => 'fa-calendar-times-o',
                'theme' => 'crimson',
            ],
            [
                'title' => 'Serial Numbers',
                'description' => 'Serial, IMEI, asset tag and status tracking.',
                'route' => 'products-new.reports.serial',
                'permission' => 'products_new.reports.serial',
                'icon' => 'fa-barcode',
                'theme' => 'slate',
            ],
            [
                'title' => 'Category & Brand',
                'description' => 'Analyse products by category and brand.',
                'route' => 'products-new.reports.category-brand',
                'permission' => 'products_new.reports.category_brand',
                'icon' => 'fa-sitemap',
                'theme' => 'teal',
            ],
            [
                'title' => 'Price History',
                'description' => 'Historical product price analysis and audit.',
                'route' => 'products-new.reports.price-history-analytics',
                'permission' => 'products_new.reports.price_history',
                'icon' => 'fa-history',
                'theme' => 'purple',
            ],
            [
                'title' => 'Inventory Turnover',
                'description' => 'Review stock velocity and turnover readiness.',
                'route' => 'products-new.reports.inventory-turnover',
                'permission' => 'products_new.reports.inventory_turnover',
                'icon' => 'fa-refresh',
                'theme' => 'sky',
            ],
            [
                'title' => 'ABC / XYZ Analysis',
                'description' => 'Classification-ready product and stock analytics.',
                'route' => 'products-new.reports.abc-xyz',
                'permission' => 'products_new.reports.abc_xyz',
                'icon' => 'fa-pie-chart',
                'theme' => 'lime',
            ],
            [
                'title' => 'Reorder Recommendation',
                'description' => 'Compare available stock with reorder requirements.',
                'route' => 'products-new.reports.reorder-recommendation',
                'permission' => 'products_new.reports.reorder_recommendation',
                'icon' => 'fa-shopping-cart',
                'theme' => 'navy',
            ],
        ];

        return view('productsnew::reports.index', compact('cards'));
    }
}
