<?php

namespace App\Utils;



use App\Business;

use App\BusinessLocation;

use App\Category;

use App\Discount;

use App\Media;

use App\Product;

use App\ProductRack;

use App\ProductVariation;

use App\PurchaseLine;

use App\TaxRate;

use App\Transaction;

use App\TransactionSellLine;

use App\TransactionSellLinesPurchaseLines;

use App\Unit;

use App\Variation;

use App\VariationGroupPrice;

use App\VariationLocationDetails;
use Illuminate\Support\Facades\Cache;
use App\VariationPrice;

use App\VariationStoreDetail;

use App\VariationTemplate;

use App\VariationValueTemplate;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

use Modules\Petro\Entities\FuelTank;

use Modules\Vat\Entities\VatProduct;



class ProductUtil extends Util
{

    /**

     * Create single type product variation

     *

     * @param (int or object) $product

     * @param $sku

     * @param $purchase_price

     * @param $dpp_inc_tax (default purchase pric including tax)

     * @param $profit_percent

     * @param $selling_price

     * @param $combo_variations = []

     *

     * @return boolean

     */



    public $deactivated_in = [

        'ezyinvoice_invoices',

        'petro_settlements',

        'billtocustomer_issuecustomerbills',

        'billtocustomer_issuecustomerbillsvat',

        'pricechange_pricechanges',

        'stocktaking_stocktaking',

        'production_production_process',

        'production_production_addproduction',

        'purchases_addpurchases',

        'purchases_listpurchasereturn_add',

        'sales_addsales',

        'sales_pos',

        'sales_listquotations_add',

        'sales_listorders_add',

        'sales_listsalereturns_add',

        'tpos_add',

        'tpos_fpos',

        'repair_add',

        'autorepair_add',

        'stocktransfer_add',

        'stockadjustment_add',

        'vat_vatinvoice',

        'vat_vatinvoice2',

        'vat_vatinvoice127',

        'production_stockreport',

        'production_itemreport',

        'reports_stockreport',

        'reports_stocksummary',

        'reports_itemreport',

        'dailysummary_stocksummary_qty',

        'dailysummary_stocksummary_value',

    ];



    public function createSingleProductVariation($product, $sku, $purchase_price, $dpp_inc_tax, $profit_percent, $selling_price, $selling_price_inc_tax, $combo_variations = [], $multiple_unit_price = null)
    {

        if (!is_object($product)) {

            $product = Product::find($product);
        }

        //create product variations

        $product_variation_data = [

            'name' => 'DUMMY',

            'is_dummy' => 1,

        ];

        $product_variation = $product->product_variations()->create($product_variation_data);

        //create variations

        $variation_data = [

            'name' => 'DUMMY',

            'product_id' => $product->id,

            'sub_sku' => $sku,

            'default_purchase_price' => $this->num_uf($purchase_price),

            'dpp_inc_tax' => $this->num_uf($dpp_inc_tax),

            'profit_percent' => $this->num_uf($profit_percent),

            'default_sell_price' => $this->num_uf($selling_price),

            'sell_price_inc_tax' => $this->num_uf($selling_price_inc_tax),

            'combo_variations' => $combo_variations,

            'default_multiple_unit_price' => $multiple_unit_price,

        ];

        $variation = $product_variation->variations()->create($variation_data);

        Media::uploadMedia($product->business_id, $variation, request(), 'variation_images');

        return true;
    }



    public function createSingleVatProductVariation($product, $sku, $purchase_price, $dpp_inc_tax, $profit_percent, $selling_price, $selling_price_inc_tax, $combo_variations = [], $multiple_unit_price = null)
    {

        if (!is_object($product)) {

            $product = VatProduct::find($product);
        }

        //create product variations

        $product_variation_data = [

            'name' => 'DUMMY',

            'is_dummy' => 1,

        ];

        $product_variation = $product->product_variations()->create($product_variation_data);

        //create variations

        $variation_data = [

            'name' => 'DUMMY',

            'product_id' => $product->id,

            'sub_sku' => $sku,

            'default_purchase_price' => $this->num_uf($purchase_price),

            'dpp_inc_tax' => $this->num_uf($dpp_inc_tax),

            'profit_percent' => $this->num_uf($profit_percent),

            'default_sell_price' => $this->num_uf($selling_price),

            'sell_price_inc_tax' => $this->num_uf($selling_price_inc_tax),

            'combo_variations' => $combo_variations,

            'default_multiple_unit_price' => $multiple_unit_price,

        ];

        $variation = $product_variation->variations()->create($variation_data);

        Media::uploadMedia($product->business_id, $variation, request(), 'variation_images');

        return true;
    }



    public function filterVatProduct($business_id, $search_term, $search_fields = [])
    {



        $query = VatProduct::join('vat_variations', 'vat_products.id', '=', 'vat_variations.product_id')

            ->active()

            ->whereNull('vat_variations.deleted_at')

            ->leftjoin('vat_units as U', 'vat_products.unit_id', '=', 'U.id')

            ->where('vat_products.business_id', $business_id)

            ->where('vat_products.type', '!=', 'modifier');



        //Include search

        if (!empty($search_term)) {

            $query->where(function ($query) use ($search_term, $search_fields) {

                if (in_array('name', $search_fields)) {

                    $query->where('vat_products.name', 'like', '%' . $search_term . '%');
                }



                if (in_array('sku', $search_fields)) {

                    $query->orWhere('products.sku', 'like', '%' . $search_term . '%');
                }



                if (in_array('sub_sku', $search_fields)) {

                    $query->orWhere('sub_sku', 'like', '%' . $search_term . '%');
                }
            });
        }



        $query->select(

            'vat_products.id as product_id',

            'vat_products.name',

            'vat_products.type',

            'vat_variations.id as variation_id',

            'vat_variations.name as variation',

            'vat_variations.sell_price_inc_tax as selling_price',

            'vat_variations.sub_sku',

            'U.actual_name as unit'

        );



        $query->groupBy('vat_variations.id');



        return $query->get();
    }



    public function filterProduct($business_id, $search_term, $location_id = null, $not_for_selling = null, $price_group_id = null, $product_types = [], $search_fields = [], $check_qty = false, $search_type = 'like', $module = null)
    {



        $query = Product::join('variations', 'products.id', '=', 'variations.product_id')

            ->active()

            ->whereNull('variations.deleted_at')

            ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')

            ->leftjoin(

                'variation_location_details AS VLD',

                function ($join) use ($location_id) {

                    $join->on('variations.id', '=', 'VLD.variation_id');



                    //Include Location

                    if (!empty($location_id)) {

                        $join->where(function ($query) use ($location_id) {

                            $query->where('VLD.location_id', '=', $location_id);

                            //Check null to show products even if no quantity is available in a location.

                            //TODO: Maybe add a settings to show product not available at a location or not.

                            $query->orWhereNull('VLD.location_id');
                        });
                    }
                }

            );

        if (!empty($module)) {

            $query->forModule($module);
        }



        if (!is_null($not_for_selling)) {

            $query->where('products.not_for_selling', $not_for_selling);
        }



        if (!empty($price_group_id)) {

            $query->leftjoin(

                'variation_group_prices AS VGP',

                function ($join) use ($price_group_id) {

                    $join->on('variations.id', '=', 'VGP.variation_id')

                        ->where('VGP.price_group_id', '=', $price_group_id);
                }

            );
        }



        $query->where('products.business_id', $business_id)

            ->where('products.type', '!=', 'modifier');



        if (!empty($product_types)) {

            $query->whereIn('products.type', $product_types);
        }



        if (in_array('lot', $search_fields)) {

            $query->leftjoin('purchase_lines as pl', 'variations.id', '=', 'pl.variation_id');
        }



        //Include search

        if (!empty($search_term)) {



            if (strlen($search_term) < 2 && is_numeric($search_term)) {



                //Search with like condition

                if ($search_type == 'like') {

                    $query->where(function ($query) use ($search_term, $search_fields) {



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', 'like', '%' . $search_term . '%');
                        }
                    });
                }



                //Search with exact condition

                if ($search_type == 'exact') {

                    $query->where(function ($query) use ($search_term, $search_fields) {



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', $search_term);
                        }
                    });
                }
            } else {



                //Search with like condition

                if ($search_type == 'like') {

                    $query->where(function ($query) use ($search_term, $search_fields) {

                        if (in_array('name', $search_fields)) {

                            $query->where('products.name', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('sub_sku', $search_fields)) {

                            $query->orWhere('variations.sub_sku', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('lot', $search_fields)) {

                            $query->orWhere('pl.lot_number', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('product_custom_field1', $search_fields)) {

                            $query->orWhere('product_custom_field1', 'like', '%' . $search_term . '%');
                        }

                        if (in_array('product_custom_field2', $search_fields)) {

                            $query->orWhere('product_custom_field2', 'like', '%' . $search_term . '%');
                        }

                        if (in_array('product_custom_field3', $search_fields)) {

                            $query->orWhere('product_custom_field3', 'like', '%' . $search_term . '%');
                        }

                        if (in_array('product_custom_field4', $search_fields)) {

                            $query->orWhere('product_custom_field4', 'like', '%' . $search_term . '%');
                        }
                    });
                }



                //Search with exact condition

                if ($search_type == 'exact') {

                    $query->where(function ($query) use ($search_term, $search_fields) {

                        if (in_array('name', $search_fields)) {

                            $query->where('products.name', $search_term);
                        }



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', $search_term);
                        }



                        if (in_array('sub_sku', $search_fields)) {

                            $query->orWhere('variations.sub_sku', $search_term);
                        }



                        if (in_array('lot', $search_fields)) {

                            $query->orWhere('pl.lot_number', $search_term);
                        }
                    });
                }
            }
        }



        //Include check for quantity

        if ($check_qty) {

            $query->where('VLD.qty_available', '>', 0);
        }



        if (!empty($location_id)) {

            $query->ForLocation($location_id);
        }



        $query->select(

            'products.id as product_id',

            'products.name',

            'products.type',

            'products.enable_stock',

            'variations.id as variation_id',

            'variations.name as variation',

            'VLD.qty_available',

            'variations.sell_price_inc_tax as selling_price',

            'variations.sub_sku',

            'U.short_name as unit'

        );



        if (!empty($price_group_id)) {

            $query->addSelect(DB::raw('IF (VGP.price_type = "fixed", VGP.price_inc_tax, VGP.price_inc_tax * variations.sell_price_inc_tax / 100) as variation_group_price'));
        }



        if (in_array('lot', $search_fields)) {

            $query->addSelect('pl.id as purchase_line_id', 'pl.lot_number');
        }



        $query->groupBy('variations.id');



        return $query->orderBy('VLD.qty_available', 'desc')

            ->get();
    }



    public function filterProductStockAdjustment($business_id, $search_term, $location_id = null, $not_for_selling = null, $price_group_id = null, $product_types = [], $search_fields = [], $check_qty = false, $search_type = 'like', $store_id = null, $module = null)
    {
        $search_term = trim((string) $search_term);
        $search_type = $search_type === 'exact' ? 'exact' : 'like';
        $search_fields = is_array($search_fields) ? $search_fields : [];

        // The core Stock Adjustment lookup must always search these three
        // fields. Stock/store quantity must not prevent a product from being
        // selected because an increase adjustment can create the first stock
        // row for a location/store.
        $search_fields = array_values(array_unique(array_merge(
            ['name', 'sku', 'sub_sku'],
            $search_fields
        )));

        $query = Product::join('variations', 'products.id', '=', 'variations.product_id')
            ->active()
            ->whereNull('variations.deleted_at')
            ->where('products.enable_stock', 1)
            ->leftJoin('units as U', 'products.unit_id', '=', 'U.id')
            ->leftJoin('variation_location_details as VLD', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'VLD.variation_id');

                if (!empty($location_id)) {
                    $join->where('VLD.location_id', '=', $location_id);
                }
            });

        if (!empty($module)) {
            $query->forModule($module);
        }

        if (!is_null($not_for_selling) && $not_for_selling !== '') {
            $query->where('products.not_for_selling', $not_for_selling);
        }

        if (!empty($price_group_id)) {
            $query->leftJoin('variation_group_prices as VGP', function ($join) use ($price_group_id) {
                $join->on('variations.id', '=', 'VGP.variation_id')
                    ->where('VGP.price_group_id', '=', $price_group_id);
            });
        }

        $query->where('products.business_id', $business_id)
            ->where('products.type', '!=', 'modifier');

        if (!empty($product_types)) {
            $query->whereIn('products.type', $product_types);
        }

        if (in_array('lot', $search_fields, true)) {
            $query->leftJoin('purchase_lines as pl', 'variations.id', '=', 'pl.variation_id');
        }

        if ($search_term !== '') {
            $operator = $search_type === 'exact' ? '=' : 'like';
            $search_value = $search_type === 'exact'
                ? $search_term
                : '%' . $search_term . '%';

            $query->where(function ($search_query) use ($search_fields, $operator, $search_value) {
                if (in_array('name', $search_fields, true)) {
                    $search_query->orWhere('products.name', $operator, $search_value);
                }

                if (in_array('sku', $search_fields, true)) {
                    $search_query->orWhere('products.sku', $operator, $search_value);
                }

                if (in_array('sub_sku', $search_fields, true)) {
                    $search_query->orWhere('variations.sub_sku', $operator, $search_value);
                }

                if (in_array('lot', $search_fields, true)) {
                    $search_query->orWhere('pl.lot_number', $operator, $search_value);
                }

                foreach (['product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4'] as $custom_field) {
                    if (in_array($custom_field, $search_fields, true)) {
                        $search_query->orWhere('products.' . $custom_field, $operator, $search_value);
                    }
                }
            });
        }

        if ($check_qty) {
            $query->where(DB::raw('COALESCE(VLD.qty_available, 0)'), '>', 0);
        }

        if (!empty($location_id)) {
            // Products explicitly assigned to this location are allowed. Older
            // core products with no product_locations rows are also allowed.
            $query->where(function ($location_query) use ($location_id) {
                $location_query
                    ->whereExists(function ($mapped_location_query) use ($location_id) {
                        $mapped_location_query->select(DB::raw(1))
                            ->from('product_locations as sa_product_locations')
                            ->whereColumn('sa_product_locations.product_id', 'products.id')
                            ->where('sa_product_locations.location_id', $location_id);
                    })
                    ->orWhereNotExists(function ($unmapped_location_query) {
                        $unmapped_location_query->select(DB::raw(1))
                            ->from('product_locations as sa_any_product_location')
                            ->whereColumn('sa_any_product_location.product_id', 'products.id');
                    });
            });
        }

        $query->select(
            'products.id as product_id',
            'products.name as name',
            'products.name as product_name',
            'products.type',
            'products.enable_stock',
            'products.sku',
            'variations.id as variation_id',
            'variations.name as variation',
            DB::raw('COALESCE(VLD.qty_available, 0) as qty_available'),
            DB::raw('NULL as store_id'),
            DB::raw('0 as store_qty'),
            'variations.sell_price_inc_tax as selling_price',
            'variations.sub_sku',
            'U.short_name as unit'
        );

        if (!empty($price_group_id)) {
            $query->addSelect(DB::raw('IF(VGP.price_type = "fixed", VGP.price_inc_tax, VGP.price_inc_tax * variations.sell_price_inc_tax / 100) as variation_group_price'));
        }

        if (in_array('lot', $search_fields, true)) {
            $query->addSelect('pl.id as purchase_line_id', 'pl.lot_number');
        }

        $results = $query
            ->distinct()
            ->orderByRaw(
                'CASE WHEN products.sku = ? OR variations.sub_sku = ? THEN 0 WHEN products.name LIKE ? THEN 1 ELSE 2 END',
                [$search_term, $search_term, '%' . $search_term . '%']
            )
            ->orderBy('products.name')
            ->limit(30)
            ->get();

        return $results->map(function ($product) {
            $product_name = trim((string) ($product->product_name ?: $product->name));
            $variation_name = trim((string) $product->variation);
            $sku = trim((string) ($product->sub_sku ?: $product->sku));

            $display_name = $product_name;
            if ($product->type === 'variable' && $variation_name !== '' && strtolower($variation_name) !== 'dummy') {
                $display_name .= ' - ' . $variation_name;
            }
            if ($sku !== '') {
                $display_name .= ' (' . $sku . ')';
            }

            $product->name = $product_name;
            $product->label = $display_name;
            $product->value = $display_name;

            return $product;
        })->values();
    }


    public function filterProductPos($business_id, $search_term, $location_id = null, $not_for_selling = null, $price_group_id = null, $product_types = [], $search_fields = [], $check_qty = false, $search_type = 'like', $store_id = null, $brand_id = null, $module)
    {



        $search_type = $search_type ?? 'like';



        if (empty($store_id)) {

            $store_id = request()->session()->get('business.default_store');
        }



        $query = Product::join('variations', 'products.id', '=', 'variations.product_id')

            ->active()

            ->whereNull('variations.deleted_at')

            ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')

            ->leftjoin(

                'variation_location_details AS VLD',

                function ($join) use ($location_id) {

                    $join->on('variations.id', '=', 'VLD.variation_id');



                    //Include Location

                    if (!empty($location_id)) {

                        $join->where(function ($query) use ($location_id) {

                            $query->where('VLD.location_id', '=', $location_id);

                            //Check null to show products even if no quantity is available in a location.

                            //TODO: Maybe add a settings to show product not available at a location or not.

                            $query->orWhereNull('VLD.location_id');
                        });
                    }
                }

            )

            ->leftjoin(

                'variation_store_details AS VSD',

                function ($join) use ($store_id) {

                    $join->on('variations.id', '=', 'VSD.variation_id');

                    //Include Location

                    if (!empty($store_id)) {

                        $join->where(function ($query) use ($store_id) {

                            $query->where('VSD.store_id', '=', $store_id);

                            //Check null to show products even if no quantity is available in a location.

                            //TODO: Maybe add a settings to show product not available at a location or not.

                            $query->orWhereNull('VSD.store_id');
                        });;
                    }
                }

            );



        if (!empty($module)) {

            $query->forModule($module);
        }



        if (!is_null($not_for_selling)) {

            $query->where('products.not_for_selling', $not_for_selling);
        }



        if (!empty($price_group_id)) {

            $query->leftjoin(

                'variation_group_prices AS VGP',

                function ($join) use ($price_group_id) {

                    $join->on('variations.id', '=', 'VGP.variation_id')

                        ->where('VGP.price_group_id', '=', $price_group_id);
                }

            );
        }



        $query->where('products.business_id', $business_id)

            ->where('products.type', '!=', 'modifier');



        if (!empty($product_types)) {

            $query->whereIn('products.type', $product_types);
        }

        if (!is_array($search_fields)) {

            $search_fields = [];
        }

        if (in_array('lot', $search_fields)) {

            $query->leftjoin('purchase_lines as pl', 'variations.id', '=', 'pl.variation_id');
        }



        //Include search

        if (!empty($search_term)) {



            if (strlen($search_term) < 2 && is_numeric($search_term) && false) {



                //Search with like condition

                if ($search_type == 'like') {

                    $query->where(function ($query) use ($search_term, $search_fields) {



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', 'like', '%' . $search_term . '%');
                        }
                    });
                }



                //Search with exact condition

                if ($search_type == 'exact') {

                    $query->where(function ($query) use ($search_term, $search_fields) {



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', $search_term);
                        }
                    });
                }
            } else {



                //Search with like condition

                if ($search_type == 'like') {

                    $query->where(function ($query) use ($search_term, $search_fields) {

                        if (in_array('name', $search_fields)) {



                            $query->where('products.name', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('sub_sku', $search_fields)) {

                            $query->orWhere('variations.sub_sku', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('lot', $search_fields)) {

                            $query->orWhere('pl.lot_number', 'like', '%' . $search_term . '%');
                        }



                        if (in_array('product_custom_field1', $search_fields)) {

                            $query->orWhere('product_custom_field1', 'like', '%' . $search_term . '%');
                        }

                        if (in_array('product_custom_field2', $search_fields)) {

                            $query->orWhere('product_custom_field2', 'like', '%' . $search_term . '%');
                        }

                        if (in_array('product_custom_field3', $search_fields)) {

                            $query->orWhere('product_custom_field3', 'like', '%' . $search_term . '%');
                        }

                        if (in_array('product_custom_field4', $search_fields)) {

                            $query->orWhere('product_custom_field4', 'like', '%' . $search_term . '%');
                        }
                    });
                }



                //Search with exact condition

                if ($search_type == 'exact') {

                    $query->where(function ($query) use ($search_term, $search_fields) {

                        if (in_array('name', $search_fields)) {

                            $query->where('products.name', $search_term);
                        }



                        if (in_array('sku', $search_fields)) {

                            $query->orWhere('products.sku', $search_term);
                        }



                        if (in_array('sub_sku', $search_fields)) {

                            $query->orWhere('variations.sub_sku', $search_term);
                        }



                        if (in_array('lot', $search_fields)) {

                            $query->orWhere('pl.lot_number', $search_term);
                        }
                    });
                }
            }
        }



        //Include check for quantity

        if ($check_qty) {

            $query->where('VSD.qty_available', '>', 0);
        }



        if ($brand_id && $brand_id != 'all') {

            $query->where('products.brand_id', $brand_id);
        }



        if (!empty($location_id)) {

            $query->ForLocation($location_id);
        }



        $query->select(

            'products.id as product_id',

            'products.name',

            'products.type',

            'products.enable_stock',

            'variations.id as variation_id',

            'variations.name as variation',

            'VSD.qty_available',

            'variations.sell_price_inc_tax as selling_price',

            'variations.sub_sku',

            'U.short_name as unit'

        );



        if (!empty($price_group_id)) {

            $query->addSelect(DB::raw('IF (VGP.price_type = "fixed", VGP.price_inc_tax, VGP.price_inc_tax * variations.sell_price_inc_tax / 100) as variation_group_price'));
        }



        if (in_array('lot', $search_fields)) {

            $query->addSelect('pl.id as purchase_line_id', 'pl.lot_number');
        }



        $query->groupBy('variations.id');



        $products = $query->orderBy('VSD.qty_available', 'desc')

            ->get();

        return $products;
    }



    /**

     * Create variable type product variation

     *

     * @param (int or object) $product

     * @param $input_variations

     *

     * @return boolean

     */

    public function createVariableProductVariations($product, $input_variations, $business_id = null, $can_edit_sku = true)
    {

        if (!is_object($product)) {

            $product = Product::find($product);
        }

        //create product variations

        foreach ($input_variations as $key => $value) {

            $images = [];

            $variation_template_name = !empty($value['name']) ? $value['name'] : null;

            $variation_template_id = !empty($value['variation_template_id']) ? $value['variation_template_id'] : null;

            if (empty($variation_template_id)) {

                if ($variation_template_name != 'DUMMY') {

                    $variation_template = VariationTemplate::where('business_id', $business_id)

                        ->whereRaw('LOWER(name)="' . strtolower($variation_template_name) . '"')

                        ->with(['values'])

                        ->first();

                    if (empty($variation_template)) {

                        $variation_template = VariationTemplate::create([

                            'name' => $variation_template_name,

                            'business_id' => $business_id,

                        ]);
                    }

                    $variation_template_id = $variation_template->id;
                }
            } else {

                $variation_template = VariationTemplate::with(['values'])->find($value['variation_template_id']);

                $variation_template_id = $variation_template->id;

                $variation_template_name = $variation_template->name;
            }

            $product_variation_data = [

                'name' => $variation_template_name,

                'product_id' => $product->id,

                'is_dummy' => 0,

                'variation_template_id' => $variation_template_id,

            ];

            $product_variation = ProductVariation::create($product_variation_data);

            //create variations

            if (!empty($value['variations'])) {

                $variation_data = [];

                $c = Variation::withTrashed()

                    ->where('product_id', $product->id)

                    ->count() + 1;

                foreach ($value['variations'] as $k => $v) {

                    $sub_sku = (!empty($v['sub_sku']) && $can_edit_sku)
                        ? $v['sub_sku']
                        : $this->generateSubSku($product->sku, $c, $product->barcode_type);

                    $variation_value_id = !empty($v['variation_value_id']) ? $v['variation_value_id'] : null;

                    $variation_value_name = !empty($v['value']) ? $v['value'] : null;

                    if (!empty($variation_value_id)) {

                        $variation_value = $variation_template->values->filter(function ($item) use ($variation_value_id) {

                            return $item->id == $variation_value_id;
                        })->first();

                        $variation_value_name = $variation_value->name;
                    } else {

                        if (!empty($variation_template)) {

                            $variation_value = VariationValueTemplate::where('variation_template_id', $variation_template->id)

                                ->whereRaw('LOWER(name)="' . $variation_value_name . '"')

                                ->first();

                            if (empty($variation_value)) {

                                $variation_value = VariationValueTemplate::create([

                                    'name' => $variation_value_name,

                                    'variation_template_id' => $variation_template->id,

                                ]);
                            }

                            $variation_value_id = $variation_value->id;

                            $variation_value_name = $variation_value->name;
                        } else {

                            $variation_value_id = null;

                            $variation_value_name = $variation_value_name;
                        }
                    }

                    $variation_data[] = [

                        'name' => $variation_value_name,

                        'variation_value_id' => $variation_value_id,

                        'product_id' => $product->id,

                        'sub_sku' => $sub_sku,

                        'default_purchase_price' => !empty($v['default_purchase_price']) ? $this->num_uf($v['default_purchase_price']) : 0.00,

                        'dpp_inc_tax' => !empty($v['dpp_inc_tax']) ? $this->num_uf($v['dpp_inc_tax']) : 0.00,

                        'profit_percent' => !empty($v['profit_percent']) ? $this->num_uf($v['profit_percent']) : 0.00,

                        'default_sell_price' => $this->num_uf($v['default_sell_price']),

                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax']),

                    ];

                    $c++;

                    $images[] = 'variation_images_' . $key . '_' . $k;
                }

                $variations = $product_variation->variations()->createMany($variation_data);

                $i = 0;

                foreach ($variations as $variation) {

                    Media::uploadMedia($product->business_id, $variation, request(), $images[$i]);

                    $i++;
                }
            }
        }
    }

    /**

     * Update variable type product variation

     *

     * @param $product_id

     * @param $input_variations_edit

     *

     * @return boolean

     */

    public function updateVariableProductVariations($product_id, $input_variations_edit, $can_edit_sku = true)
    {

        $product = Product::find($product_id);

        $tax_rate = 0;
        if (!empty($product->sale_tax)) {
            $tax_rate_obj = TaxRate::find($product->sale_tax);
            if (!empty($tax_rate_obj)) {
                $tax_rate = $tax_rate_obj->amount;
            }
        }

        //Update product variations

        $product_variation_ids = [];

        foreach ($input_variations_edit as $key => $value) {

            $product_variation_ids[] = $key;

            $product_variation = ProductVariation::find($key);

            $product_variation->name = $value['name'];

            $product_variation->save();

            //Update existing variations

            $variations_ids = [];

            if (!empty($value['variations_edit'])) {

                foreach ($value['variations_edit'] as $k => $v) {

                    $sell_price_inc_tax = $this->num_uf($v['sell_price_inc_tax']);

                    $data = [

                        'name' => $v['value'],

                        'default_purchase_price' => !empty($v['default_purchase_price']) ? $this->num_uf($v['default_purchase_price']) : 0.00,

                        'dpp_inc_tax' => !empty($v['dpp_inc_tax']) ? $this->num_uf($v['dpp_inc_tax']) : 0.00,

                        'profit_percent' => !empty($v['profit_percent']) ? $this->num_uf($v['profit_percent']) : 0.00,

                        'sell_price_inc_tax' => $sell_price_inc_tax,

                        'default_sell_price' => $this->calc_percentage_base($sell_price_inc_tax, $tax_rate),

                    ];

                    if ($can_edit_sku && !empty($v['sub_sku'])) {

                        $data['sub_sku'] = $v['sub_sku'];
                    }

                    $variation = Variation::where('id', $k)

                        ->where('product_variation_id', $key)

                        ->first();

                    $variation->update($data);

                    Media::uploadMedia($product->business_id, $variation, request(), 'edit_variation_images_' . $key . '_' . $k);

                    $variations_ids[] = $k;
                }
            }

            //Check if purchase or sell exist for the deletable variations

            $count_purchase = PurchaseLine::join(

                'transactions as T',

                'purchase_lines.transaction_id',

                '=',

                'T.id'

            )

                ->where('T.type', 'purchase')

                ->where('T.status', 'received')

                ->where('T.business_id', $product->business_id)

                ->where('purchase_lines.product_id', $product->id)

                ->whereNotIn('purchase_lines.variation_id', $variations_ids)

                ->count();

            $count_sell = TransactionSellLine::join(

                'transactions as T',

                'transaction_sell_lines.transaction_id',

                '=',

                'T.id'

            )

                ->where('T.type', 'sell')

                ->where('T.status', 'final')

                ->where('T.business_id', $product->business_id)

                ->where('transaction_sell_lines.product_id', $product->id)

                ->whereNotIn('transaction_sell_lines.variation_id', $variations_ids)

                ->count();

            $is_variation_delatable = $count_purchase > 0 || $count_sell > 0 ? false : true;

            if ($is_variation_delatable) {

                Variation::whereNotIn('id', $variations_ids)

                    ->where('product_variation_id', $key)

                    ->delete();
            } else {

                throw new \Exception(__('lang_v1.purchase_already_exist'));
            }

            //Add new variations

            if (!empty($value['variations'])) {

                $variation_data = [];

                $c = Variation::withTrashed()

                    ->where('product_id', $product->id)

                    ->count() + 1;

                $media = [];

                foreach ($value['variations'] as $k => $v) {

                    $sub_sku = (!empty($v['sub_sku']) && $can_edit_sku)
                        ? $v['sub_sku']
                        : $this->generateSubSku($product->sku, $c, $product->barcode_type);

                    $variation_value_name = !empty($v['value']) ? $v['value'] : null;

                    $variation_value_id = null;

                    if (!empty($product_variation->variation_template_id)) {

                        $variation_value = VariationValueTemplate::where('variation_template_id', $product_variation->variation_template_id)

                            ->whereRaw('LOWER(name)="' . $v['value'] . '"')

                            ->first();

                        if (empty($variation_value)) {

                            $variation_value = VariationValueTemplate::create([

                                'name' => $v['value'],

                                'variation_template_id' => $product_variation->variation_template_id,

                            ]);
                        }

                        $variation_value_id = $variation_value->id;
                    }

                    $variation_data[] = [

                        'name' => $variation_value_name,

                        'variation_value_id' => $variation_value_id,

                        'product_id' => $product->id,

                        'sub_sku' => $sub_sku,

                        'default_purchase_price' => $this->num_uf($v['default_purchase_price']),

                        'dpp_inc_tax' => $this->num_uf($v['dpp_inc_tax']),

                        'profit_percent' => $this->num_uf($v['profit_percent']),

                        'default_sell_price' => $this->num_uf($v['default_sell_price']),

                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax']),

                    ];

                    $c++;

                    $media[] = 'variation_images_' . $key . '_' . $k;
                }

                $new_variations = $product_variation->variations()->createMany($variation_data);

                $i = 0;

                foreach ($new_variations as $new_variation) {

                    Media::uploadMedia($product->business_id, $new_variation, request(), $media[$i]);

                    $i++;
                }
            }
        }

        ProductVariation::where('product_id', $product_id)

            ->whereNotIn('id', $product_variation_ids)

            ->delete();
    }

    /**

     * Checks if products has manage stock enabled then Updates quantity for product and its

     * variations

     *

     * @param $location_id

     * @param $product_id

     * @param $variation_id

     * @param $new_quantity

     * @param $old_quantity = 0

     * @param $number_format = null

     * @param $uf_data = true, if false it will accept numbers in database format

     *

     * @return boolean

     */

    public function updateProductQuantity($location_id, $product_id, $variation_id, $new_quantity, $old_quantity = 0, $number_format = null, $uf_data = true, $tank_id = null)
    {

        if ($uf_data) {

            $qty_difference = $this->num_uf($new_quantity, $number_format) - $this->num_uf($old_quantity, $number_format);
        } else {

            $qty_difference = $this->num_uf($new_quantity) - $this->num_uf($old_quantity);
        }

        $product = Product::find($product_id);

        //Check if stock is enabled or not.

        if ($product->enable_stock == 1 && $qty_difference != 0) {

            $variation = Variation::where('id', $variation_id)

                ->where('product_id', $product_id)

                ->first();

            // Check if variation exists
            if (empty($variation)) {
                \Log::error("Variation not found. Product ID: {$product_id}, Variation ID: {$variation_id}");
                return;
            }

            //Add quantity in VariationLocationDetails

            $variation_location_d = VariationLocationDetails::where('variation_id', $variation->id)

                ->where('product_id', $product_id)

                ->where('product_variation_id', $variation->product_variation_id)

                ->where('location_id', $location_id)

                ->first();

            if (empty($variation_location_d)) {

                $variation_location_d = new VariationLocationDetails();

                $variation_location_d->variation_id = $variation->id;

                $variation_location_d->product_id = $product_id;

                $variation_location_d->location_id = $location_id;

                $variation_location_d->product_variation_id = $variation->product_variation_id;

                $variation_location_d->qty_available = 0;
            }

            $variation_location_d->qty_available += $qty_difference;

            $variation_location_d->save();



            //add qty to fuel tank current stock

            if (!empty($tank_id)) {

                FuelTank::where('id', $tank_id)->increment('current_balance', $qty_difference);
            }
        }

        return true;
    }

    /**

     * Checks if products has manage stock enabled then Decrease quantity for product and its variations

     *

     * @param $product_id

     * @param $variation_id

     * @param $location_id

     * @param $new_quantity

     * @param $old_quantity = 0

     *

     * @return boolean

     */

    public function decreaseProductQuantity($product_id, $variation_id, $location_id, $new_quantity, $old_quantity = 0, $adjustment_type = null, $store_id = false) //$adjustment_type for stock adjustment
    {

        Log::info('data received new_quantity: ' . $new_quantity);

        Log::info('data received old_quantity: ' . $old_quantity);

        Log::info('data received adjustment_type: ' . $adjustment_type);

        Log::info('data received product_id ' . $product_id);

        $business_id = request()->session()->get('user.business_id');

        if ($new_quantity != $old_quantity) {

            $qty_difference = $this->num_uf($new_quantity) - $this->num_uf($old_quantity);

            if ($qty_difference < 0) {

                $qty_difference = $qty_difference * -1;
            }



            $adjustment_type = empty($adjustment_type) ? ($new_quantity > $old_quantity ? "increase" : "decrease") : $adjustment_type;



            $product = Product::where('id', $product_id)

                ->where('business_id', $business_id)

                ->first();

            Log::info('Product enable_stock: ' . $product->enable_stock);

            //Check if stock is enabled or not.

            if ($product->enable_stock == 1) {

                //Decrement Quantity in variations location table

                $details = VariationLocationDetails::where('variation_id', $variation_id)

                    ->where('product_id', $product_id)

                    ->where('location_id', $location_id)

                    ->first();



                Log::info('VariationLocationDetails found: ' . json_encode($details));



                //If location details not exists create new one

                if (empty($details)) {

                    $variation = Variation::find($variation_id);

                    // Check if variation exists
                    if (empty($variation)) {
                        \Log::error("Variation not found in decreaseProductQuantity. Product ID: {$product_id}, Variation ID: {$variation_id}");
                        return;
                    }

                    $details = VariationLocationDetails::create([

                        'product_id' => $product_id,

                        'location_id' => $location_id,

                        'variation_id' => $variation_id,

                        'product_variation_id' => $variation->product_variation_id,

                        'qty_available' => 0,

                    ]);
                }



                Log::info('Before decrement, current qty_available: ' . $details->qty_available);



                if (!empty($adjustment_type)) {

                    if ($adjustment_type == 'increase') {

                        $details->increment('qty_available', $qty_difference);
                    }

                    if ($adjustment_type == 'decrease') {



                        $details->decrement('qty_available', $qty_difference);
                    }
                } else {

                    $details->decrement('qty_available', $qty_difference);
                }



                $details->refresh(); // Reload latest from DB

                Log::info('After decrement, qty_available: ' . $details->qty_available);
            }
        }



        return true;
    }

    /**

     * Decrease the product quantity of combo sub-products

     *

     * @param $variation_id

     * @param $location_id

     * @param $decrease_qty (factor by which qty will be decreased)

     *

     * @return boolean

     */

    public function decreaseProductQuantityCombo($combo_details, $location_id)
    {

        foreach ($combo_details as $details) {

            $this->decreaseProductQuantity(

                $details['product_id'],

                $details['variation_id'],

                $location_id,

                $details['quantity'],

                0,

                'decrease'

            );



            $store_id = request()->session()->get('business.default_store');

            $this->decreaseProductQuantityStore(

                $details['product_id'],

                $details['variation_id'],

                $location_id,

                $details['quantity'],

                $store_id,

                "decrease",

                0

            );
        }
    }

    /**

     * create adjustment for weight excess or loss

     *

     * @param $product array

     *

     * @return boolean

     */

    public function createWeightExcessLossAdjustment($product, $location_id)
    {

        //Decrease available quantity

        $this->decreaseProductQuantity(

            $product['product_id'],

            $product['variation_id'],

            $location_id,

            $this->num_uf($product['quantity']),

            0,

            $product['addjustment_type']

        );



        $store_id = request()->session()->get('business.default_store');

        $this->decreaseProductQuantityStore(

            $product['product_id'],

            $product['variation_id'],

            $location_id,

            $this->num_uf($product['quantity']),

            $store_id,

            "decrease",

            0

        );
    }

    /**

     * Get all details for a product from its variation id

     *

     * @param int $variation_id

     * @param int $business_id

     * @param int $location_id

     * @param bool $check_qty (If false qty_available is not checked)

     *

     * @return object

     */

    public function getDetailsFromVariation($variation_id, $business_id, $location_id = null, $check_qty = true, $store_id = null)
    {

        $query = Variation::join('products AS p', 'variations.product_id', '=', 'p.id')

            ->join('product_variations AS pv', 'variations.product_variation_id', '=', 'pv.id')

            // ->leftJoin('variation_location_details AS vld', 'variations.id', '=', 'vld.variation_id')

            ->leftJoin('variation_location_details AS vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id');

                if (!empty($location_id)) {
                    $join->where('vld.location_id', '=', $location_id);
                }
            })

            ->leftJoin('variation_store_details AS vsd', 'variations.id', '=', 'vsd.variation_id')

            ->leftJoin('units', 'p.unit_id', '=', 'units.id')

            ->leftJoin('product_racks', 'variations.product_id', '=', 'product_racks.product_id')

            ->leftJoin('categories', 'p.category_id', '=', 'categories.id')

            ->leftJoin('categories as sub_category', 'p.sub_category_id', '=', 'sub_category.id')

            ->leftJoin('purchase_lines', 'variations.product_id', '=', 'purchase_lines.product_id')

            ->leftJoin('transactions', 'purchase_lines.transaction_id', '=', 'transactions.id')

            ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

            ->leftJoin('brands', function ($join) {

                $join->on('p.brand_id', '=', 'brands.id')

                    ->whereNull('brands.deleted_at');
            })

            ->where('p.business_id', $business_id)

            ->where('variations.id', $variation_id);



        // if (!empty($location_id)) {

        //     $query->where(function ($query) use ($location_id) {

        //         $query->where('vld.location_id', $location_id)

        //             ->orWhereNull('vld.location_id'); // Ensure products with no location details are included

        //     });
        // }



        if (!empty($store_id)) {

            $query->where(function ($query) use ($store_id) {

                $query->where('vsd.store_id', $store_id);
            });
        }



        $product = $query->select(

            DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':', variations.name, ')'), p.name) AS product_name"),

            'p.id as product_id',

            'p.brand_id',

            'p.category_id',

            'p.tax as tax_id',

            'p.enable_stock',

            'p.enable_sr_no',

            'p.min_sell_price',

            'p.type as product_type',

            'p.name as product_actual_name',

            'p.warranty_id',

            'pv.name as product_variation_name',

            'pv.is_dummy as is_dummy',

            'variations.name as variation_name',

            'variations.sub_sku',

            'p.barcode_type',

            DB::raw("IFNULL(vld.qty_available, 0) as current_stock"),

            DB::raw("IFNULL(vld.qty_available, 0) as qty_available"),

            'variations.default_sell_price',

            'variations.sell_price_inc_tax',

            'variations.id as variation_id',

            'variations.combo_variations',

            'units.short_name as unit',

            'units.id as unit_id',

            'units.allow_decimal as unit_allow_decimal',

            'brands.name as brand',

            'product_racks.rack as rack_number',

            'contacts.name as supplier_name',

            'purchase_lines.purchase_price',

            'categories.weight_excess_loss_applicable',

            'sub_category.name as subcat_name',

            'p.sub_category_id',

            'variations.dpp_inc_tax as last_purchased_price'

        )->first();



        if (!empty($product->product_type) && $product->product_type == 'combo') {

            if ($check_qty) {

                $product->qty_available = $this->calculateComboQuantity($location_id, $product->combo_variations);
            }

            $product->combo_products = $this->calculateComboDetails($location_id, $product->combo_variations);
        }



        return $product;
    }



    /**

     * Calculates the quantity of combo products based on

     * the quantity of variation items used.

     *

     * @param int $location_id

     * @param array $combo_variations

     *

     * @return int

     */

    public function calculateComboQuantity($location_id, $combo_variations)
    {

        //get stock of the items and calcuate accordingly.

        $combo_qty = 0;

        foreach ($combo_variations as $key => $value) {

            $vld = VariationLocationDetails::where('variation_id', $value['variation_id'])

                ->where('location_id', $location_id)

                ->first();

            $product = Product::find($vld->product_id);

            $variation_qty = !empty($vld) ? $vld->qty_available : 0;

            $multiplier = $this->getMultiplierOf2Units($product->unit_id, $value['unit_id']);

            if ($key == 0) {

                $combo_qty = ($variation_qty / $multiplier) / $combo_variations[$key]['quantity'];
            } else {

                $combo_qty = min($combo_qty, ($variation_qty / $multiplier) / $combo_variations[$key]['quantity']);
            }
        }

        return floor($combo_qty);
    }

    /**

     * Calculates the quantity of combo products based on

     * the quantity of variation items used.

     *

     * @param int $location_id

     * @param array $combo_variations

     *

     * @return int

     */

    public function calculateComboDetails($location_id, $combo_variations)
    {

        $details = [];

        foreach ($combo_variations as $key => $value) {

            $variation = Variation::with('product')->findOrFail($value['variation_id']);

            $vld = VariationLocationDetails::where('variation_id', $value['variation_id'])

                ->where('location_id', $location_id)

                ->first();

            $variation_qty = !empty($vld) ? $vld->qty_available : 0;

            $multiplier = $this->getMultiplierOf2Units($variation->product->unit_id, $value['unit_id']);

            $details[] = [

                'variation_id' => $value['variation_id'],

                'product_id' => $variation->product_id,

                'qty_required' => $this->num_uf($value['quantity']) * $multiplier,

            ];
        }

        return $details;
    }

    /**

     * Calculates the total amount of invoice

     *

     * @param array $products

     * @param int $tax_id

     * @param array $discount['discount_type', 'discount_amount']

     *

     * @return Mixed (false, array)

     */

    public function calculateInvoiceTotal($products, $tax_id, $discount = null)
    {

        if (empty($products)) {

            return false;
        }

        $output = ['total_before_tax' => 0, 'tax' => 0, 'discount' => 0, 'final_total' => 0];

        //Sub Total

        foreach ($products as $product) {

            $product_data = collect($product);



            $subtotal = $this->num_uf($product_data->get('$subtotal'));

            $quantity = $product_data->get('quantity');

            $tax_details = $product_data->get('tax_id') ? TaxRate::find($product_data->get('tax_id')) : null;

            $unit_price = floatval($product_data->get('unit_price'));

            $item_tax = 0;

            if ($tax_details) {

                $item_tax = ($unit_price * $tax_details->amount) / 100;
            }

            $unit_tax_price = $unit_price + $item_tax;



            $unit_discount = floatval($product_data->get('line_discount_amount'));

            if ($product_data->get('line_discount_type') === 'fixed') {

                $unit_total_price = max($unit_tax_price - $unit_discount, 0);
            } else {

                $unit_total_price = max($unit_tax_price - (($unit_tax_price * $unit_discount) / 100), 0);
            }

            // $output['total_before_tax'] += $unit_total_price * $this->num_uf($quantity);

            $output['total_before_tax'] += $subtotal;

            //Add modifier price to total if exists

            if (!empty($product['modifier_price'])) {

                foreach ($product['modifier_price'] as $modifier_price) {

                    $output['total_before_tax'] += $this->num_uf($modifier_price);
                }
            }
        }

        //Calculate discount

        if (is_array($discount)) {

            if ($discount['discount_type'] == 'fixed') {

                $output['discount'] = $this->num_uf($discount['discount_amount']);
            } else {

                $output['discount'] = ($this->num_uf($discount['discount_amount']) / 100) * $output['total_before_tax'];
            }
        }

        //Tax

        $output['tax'] = 0;

        if (!empty($tax_id)) {

            $tax_details = TaxRate::find($tax_id);

            if (!empty($tax_details)) {

                $output['tax_id'] = $tax_id;

                $output['tax'] = ($tax_details->amount / 100) * ($output['total_before_tax'] - $output['discount']);
            }
        }

        //Calculate total

        $output['final_total'] = $output['total_before_tax'] + $output['tax'] - $output['discount'];

        return $output;
    }

    /**

     * Generates product sku

     *

     * @param string $string

     *

     * @return generated sku (string)

     */

    public function generateProductSku($string)
    {

        $business_id = request()->session()->get('user.business_id');

        $sku_prefix = Business::where('id', $business_id)->value('sku_prefix');

        return $sku_prefix . str_pad($string, 4, '0', STR_PAD_LEFT);
    }

    /**

     * Gives list of trending products

     *

     * @param int $business_id

     * @param array $filters

     *

     * @return Obj

     */

    public function getTrendingProducts($business_id, $filters = [])
    {

        $query = Transaction::join(

            'transaction_sell_lines as tsl',

            'transactions.id',

            '=',

            'tsl.transaction_id'

        )

            ->join('products as p', 'tsl.product_id', '=', 'p.id')

            ->leftjoin('units as u', 'u.id', '=', 'p.unit_id')

            ->where('transactions.business_id', $business_id)

            ->where('transactions.type', 'sell')

            ->where('transactions.status', 'final');

        $permitted_locations = auth()->user()->permitted_locations();

        if ($permitted_locations != 'all') {

            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!empty($filters['location_id'])) {

            $query->where('transactions.location_id', $filters['location_id']);
        }

        if (!empty($filters['category'])) {

            $query->where('p.category_id', $filters['category']);
        }

        if (!empty($filters['sub_category'])) {

            $query->where('p.sub_category_id', $filters['sub_category']);
        }

        if (!empty($filters['brand'])) {

            $query->where('p.brand_id', $filters['brand']);
        }

        if (!empty($filters['unit'])) {

            $query->where('p.unit_id', $filters['unit']);
        }

        if (!empty($filters['limit'])) {

            $query->limit($filters['limit']);
        } else {

            $query->limit(5);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {

            $query->whereBetween('transaction_date', [

                $filters['start_date'] . ' 00:00:00',

                $filters['end_date'] . ' 23:59:59',

            ]);
        }

        // $sell_return_query = "(SELECT SUM(TPL.quantity) FROM transactions AS T JOIN purchase_lines AS TPL ON T.id=TPL.transaction_id WHERE TPL.product_id=tsl.product_id AND T.type='sell_return'";

        // if ($permitted_locations != 'all') {

        //     $sell_return_query .= ' AND T.location_id IN ('

        //      . implode(',', $permitted_locations) . ') ';

        // }

        // if (!empty($filters['start_date']) && !empty($filters['end_date'])) {

        //     $sell_return_query .= ' AND date(T.transaction_date) BETWEEN \'' . $filters['start_date'] . '\' AND \'' . $filters['end_date'] . '\'';

        // }

        // $sell_return_query .= ')';

        $products = $query->select(

            DB::raw("(SUM(tsl.quantity) - COALESCE(SUM(tsl.quantity_returned), 0)) as total_unit_sold"),

            'p.name as product',

            'u.short_name as unit'

        )

            ->groupBy('tsl.product_id')

            ->orderBy('total_unit_sold', 'desc')

            ->get();

        return $products;
    }

    /**

     * Gives list of products based on products id and variation id

     *

     * @param int $business_id

     * @param int $product_id

     * @param int $variation_id = null

     *

     * @return Obj

     */

    public function getDetailsFromProduct($business_id, $product_id, $variation_id = null)
    {

        $product = Product::leftjoin('variations as v', 'products.id', '=', 'v.product_id')

            ->whereNull('v.deleted_at')

            ->where('products.business_id', $business_id);

        if (!is_null($variation_id) && $variation_id !== '0') {

            $product->where('v.id', $variation_id);
        }

        $product->where('products.id', $product_id);

        $products = $product->select(

            'products.id as product_id',

            'products.name as product_name',

            'v.id as variation_id',

            'v.name as variation_name'

        )

            ->get();

        return $products;
    }

    /**

     * F => D (Previous product Increase)

     * D => F (All product decrease)

     * F => F (Newly added product drerease)

     *

     * @param  object $transaction_before

     * @param  object  $transaction

     * @param  array  $input

     *

     * @return void

     */

    public function adjustProductStockForInvoice($status_before, $transaction, $input, $uf_data = true)
    {

        if ($status_before == 'final' && $transaction->status == 'draft') {

            foreach ($input['products'] as $product) {

                if (!empty($product['transaction_sell_lines_id'])) {

                    $this->updateProductQuantity($input['location_id'], $product['product_id'], $product['variation_id'], $product['quantity'], 0, null, false);

                    $this->updateProductQuantityStore($input['location_id'], $product['product_id'], $product['variation_id'], $product['quantity']);

                    //Adjust quantity for combo items.

                    if (isset($product['product_type']) && $product['product_type'] == 'combo') {

                        //Giving quantity in minus will increase the qty

                        foreach ($product['combo'] as $value) {

                            $this->updateProductQuantity($input['location_id'], $value['product_id'], $value['variation_id'], $value['quantity'], 0, null, false);

                            $this->updateProductQuantityStore($input['location_id'], $value['product_id'], $value['variation_id'], $value['quantity']);
                        }

                        // $this->updateEditedSellLineCombo($product['combo'], $input['location_id']);

                    }
                }
            }
        } elseif ($status_before == 'draft' && $transaction->status == 'final') {

            foreach ($input['products'] as $product) {

                $uf_quantity = $uf_data ? $this->num_uf($product['quantity']) : $product['quantity'];

                $this->decreaseProductQuantity(

                    $product['product_id'],

                    $product['variation_id'],

                    $input['location_id'],

                    $uf_quantity,

                    0,

                    'decrease'

                );



                $store_id = request()->session()->get('business.default_store');

                $this->decreaseProductQuantityStore(

                    $product['product_id'],

                    $product['variation_id'],

                    $input['location_id'],

                    $uf_quantity,

                    $store_id,

                    "decrease",

                    0

                );



                //Adjust quantity for combo items.

                if (isset($product['product_type']) && $product['product_type'] == 'combo') {

                    $this->decreaseProductQuantityCombo($product['combo'], $input['location_id']);

                    //$this->decreaseProductQuantityCombo($product['variation_id'], $input['location_id'], $uf_quantity);

                }
            }
        } elseif ($status_before == 'final' && $transaction->status == 'final') {

            foreach ($input['products'] as $product) {

                if (empty($product['transaction_sell_lines_id'])) {

                    $uf_quantity = $uf_data ? $this->num_uf($product['quantity']) : $product['quantity'];

                    $this->decreaseProductQuantity(

                        $product['product_id'],

                        $product['variation_id'],

                        $input['location_id'],

                        $uf_quantity,

                        0,

                        'decrease'

                    );



                    $store_id = request()->session()->get('business.default_store');

                    $this->decreaseProductQuantityStore(

                        $product['product_id'],

                        $product['variation_id'],

                        $input['location_id'],

                        $uf_quantity,

                        $store_id,

                        "decrease",

                        0

                    );



                    //Adjust quantity for combo items.

                    if (isset($product['product_type']) && $product['product_type'] == 'combo') {

                        $this->decreaseProductQuantityCombo($product['combo'], $input['location_id']);

                        //$this->decreaseProductQuantityCombo($product['variation_id'], $input['location_id'], $uf_quantity);

                    }
                }
            }
        }
    }

    /**

     * Updates variation from purchase screen

     *

     * @param array $variation_data

     *

     * @return void

     */

    public function updateProductFromPurchase($variation_data)
    {

        $variation_details = Variation::where('id', $variation_data['variation_id'])

            ->with(['product', 'product.product_tax'])

            ->first();

        // Check if variation exists - if not, continue with VariationPrice creation only
        if (empty($variation_details)) {
            // Create VariationPrice record even if original variation doesn't exist
            $variation_price = new VariationPrice();
            $variation_price->name = 'DUMMY'; // Default name when original variation is missing
            $variation_price->product_id = $variation_data['product_id'] ?? null;
            $variation_price->sub_sku = '0' . ($variation_data['product_id'] ?? '000');
            $variation_price->product_variation_id = $variation_data['variation_id'];
            $variation_price->variation_value_id = null;
            $variation_price->default_purchase_price = $variation_data['pp_without_discount'];
            $variation_price->dpp_inc_tax = $variation_data['pp_without_discount']; // No tax calculation without product details
            $variation_price->sell_price_inc_tax = $variation_data['sell_price_inc_tax'] ?? $variation_data['pp_without_discount'] * 1.25; // 25% markup as fallback
            $variation_price->default_sell_price = $variation_price->sell_price_inc_tax; // Assume no tax for fallback
            $variation_price->profit_percent = $this->get_percent($variation_price->default_purchase_price, $variation_price->default_sell_price);
            $variation_price->created_at = now();
            $variation_price->save();
            
            return;
        }

        $tax_rate = 0;

        if (!empty($variation_details->product->product_tax->amount)) {

            $tax_rate = $variation_details->product->product_tax->amount;
        }

        if (!isset($variation_data['sell_price_inc_tax'])) {

            $variation_data['sell_price_inc_tax'] = $variation_details->sell_price_inc_tax;
        }

        $product = Product::find($variation_details->product_id);

        if (
            empty($product->do_not_update_purchase_price) &&
            (
                ($variation_details->default_purchase_price != $variation_data['pp_without_discount']) ||

                ($variation_details->sell_price_inc_tax != $variation_data['sell_price_inc_tax'])
            )
        ) {

            //Set default purchase price exc. tax

            $variation_details->default_purchase_price = $variation_data['pp_without_discount'];

            //Set default purchase price inc. tax

            $variation_details->dpp_inc_tax = $this->calc_percentage($variation_details->default_purchase_price, $tax_rate, $variation_details->default_purchase_price);

            //Set default sell price inc. tax

            $variation_details->sell_price_inc_tax = $variation_data['sell_price_inc_tax'];

            //set sell price inc. tax

            $variation_details->default_sell_price = $this->calc_percentage_base($variation_details->sell_price_inc_tax, $tax_rate);

            //set profit margin

            $variation_details->profit_percent = $this->get_percent($variation_details->default_purchase_price, $variation_details->default_sell_price);

            $variation_details->save();
        }

        //varation price table

        // ✅ Save new record in VariationPrice table

        $variation_price = new VariationPrice();

        //$variation_price->variation_id = $variation_details->id;

        $variation_price->name = $variation_details->name;

        $variation_price->product_id = $variation_details->product_id;

        $variation_price->sub_sku = $variation_details->sub_sku;

        $variation_price->product_variation_id = $variation_details->product_variation_id;

        $variation_price->variation_value_id = $variation_details->variation_value_id;

        $variation_price->default_purchase_price = $variation_data['pp_without_discount'];

        $variation_price->dpp_inc_tax = $this->calc_percentage(

            $variation_price->default_purchase_price,

            $tax_rate,

            $variation_price->default_purchase_price

        );

        $variation_price->sell_price_inc_tax = $variation_data['sell_price_inc_tax'];

        $variation_price->default_sell_price = $this->calc_percentage_base(

            $variation_price->sell_price_inc_tax,

            $tax_rate

        );

        $variation_price->profit_percent = $this->get_percent(

            $variation_price->default_purchase_price,

            $variation_price->default_sell_price

        );

        $variation_price->created_at = now();

        $variation_price->save();

        // 🔁 Update the last purchase line to use the new variation ID



    }

    /**

     * Generated SKU based on the barcode type.

     *

     * @param string $sku

     * @param string $c

     * @param string $barcode_type

     *

     * @return void

     */

    public function generateSubSku($sku, $c, $barcode_type)
    {

        $sub_sku = $sku . $c;

        if (in_array($barcode_type, ['C128', 'C39'])) {

            $sub_sku = $sku . '-' . $c;
        }

        return $sub_sku;
    }

    /**

     * Add rack details.

     *

     * @param int $business_id

     * @param int $product_id

     * @param array $product_racks

     * @param array $product_racks

     *

     * @return void

     */

    public function addRackDetails($business_id, $product_id, $product_racks)
    {

        if (!empty($product_racks)) {

            $data = [];

            foreach ($product_racks as $location_id => $detail) {

                $data[] = [

                    'business_id' => $business_id,

                    'location_id' => $location_id,

                    'product_id' => $product_id,

                    'rack' => !empty($detail['rack']) ? $detail['rack'] : null,

                    'row' => !empty($detail['row']) ? $detail['row'] : null,

                    'position' => !empty($detail['position']) ? $detail['position'] : null,

                    'created_at' => \Carbon::now()->toDateTimeString(),

                    'updated_at' => \Carbon::now()->toDateTimeString(),

                ];
            }

            ProductRack::insert($data);
        }
    }

    /**

     * Get rack details.

     *

     * @param int $business_id

     * @param int $product_id

     *

     * @return void

     */

    public function getRackDetails($business_id, $product_id, $get_location = false)
    {

        $query = ProductRack::where('product_racks.business_id', $business_id)

            ->where('product_id', $product_id);

        if ($get_location) {

            $racks = $query->join('business_locations AS BL', 'product_racks.location_id', '=', 'BL.id')

                ->select([

                    'product_racks.rack',

                    'product_racks.row',

                    'product_racks.position',

                    'BL.name',

                ])

                ->get();
        } else {

            $racks = collect($query->select(['rack', 'row', 'position', 'location_id'])->get());

            $racks = $racks->mapWithKeys(function ($item, $key) {

                return [$item['location_id'] => $item->toArray()];
            })->toArray();
        }

        return $racks;
    }

    /**

     * Update rack details.

     *

     * @param int $business_id

     * @param int $product_id

     * @param array $product_racks

     *

     * @return void

     */

    public function updateRackDetails($business_id, $product_id, $product_racks)
    {

        if (!empty($product_racks)) {

            foreach ($product_racks as $location_id => $details) {

                ProductRack::where('business_id', $business_id)

                    ->where('product_id', $product_id)

                    ->where('location_id', $location_id)

                    ->update([

                        'rack' => !empty($details['rack']) ? $details['rack'] : null,

                        'row' => !empty($details['row']) ? $details['row'] : null,

                        'position' => !empty($details['position']) ? $details['position'] : null,

                    ]);
            }
        }
    }

    /**

     * Retrieves selling price group price for a product variation.

     *

     * @param int $variation_id

     * @param int $price_group_id

     * @param int $tax_id

     *

     * @return decimal

     */

    public function getVariationGroupPrice($variation_id, $price_group_id, $tax_id)
    {

        $price_inc_tax =

            VariationGroupPrice::where('variation_id', $variation_id)

            ->where('price_group_id', $price_group_id)

            ->value('price_inc_tax');

        $price_exc_tax = $price_inc_tax;

        if (!empty($price_inc_tax) && !empty($tax_id)) {

            $tax_amount = TaxRate::where('id', $tax_id)->value('amount');

            $price_exc_tax = $this->calc_percentage_base($price_inc_tax, $tax_amount);
        }

        return [

            'price_inc_tax' => $price_inc_tax,

            'price_exc_tax' => $price_exc_tax,

        ];
    }

    /**

     * Creates new variation if not exists.

     *

     * @param int $business_id

     * @param string $name

     *

     * @return obj

     */

    public function createOrNewVariation($business_id, $name)
    {

        $variation = VariationTemplate::where('business_id', $business_id)

            ->where('name', 'like', $name)

            ->with(['values'])

            ->first();

        if (empty($variation)) {

            $variation = VariationTemplate::create([

                'business_id' => $business_id,

                'name' => $name,

            ]);
        }

        return $variation;
    }

    /**

     * Adds opening stock to a single product.

     *

     * @param int $business_id

     * @param obj $product

     * @param array $input

     * @param obj $transaction_date

     * @param int $user_id

     *

     * @return void

     */

    public function addSingleProductOpeningStock($business_id, $product, $input, $transaction_date, $user_id)
    {

        $locations = BusinessLocation::forDropdown($business_id)->toArray();

        $tax_percent = !empty($product->product_tax->amount) ? $product->product_tax->amount : 0;

        $tax_id = !empty($product->product_tax->id) ? $product->product_tax->id : null;

        foreach ($input as $key => $value) {

            $location_id = $key;

            $purchase_total = 0;

            //Check if valid location

            if (array_key_exists($location_id, $locations)) {

                $purchase_lines = [];

                $purchase_price = $this->num_uf(trim($value['purchase_price']));

                $item_tax = $this->calc_percentage($purchase_price, $tax_percent);

                $purchase_price_inc_tax = $purchase_price + $item_tax;

                $qty = $this->num_uf(trim($value['quantity']));

                $exp_date = null;

                if (!empty($value['exp_date'])) {

                    $exp_date = \Carbon::createFromFormat('d-m-Y', $value['exp_date'])->format('Y-m-d');
                }

                $lot_number = null;

                if (!empty($value['lot_number'])) {

                    $lot_number = $value['lot_number'];
                }

                if ($qty > 0) {

                    $qty_formated = $this->num_f($qty);

                    //Calculate transaction total

                    $purchase_total += ($purchase_price_inc_tax * $qty);

                    $variation_id = $product->variations->first()->id;

                    $purchase_line = new PurchaseLine();

                    $purchase_line->product_id = $product->id;

                    $purchase_line->variation_id = $variation_id;

                    $purchase_line->item_tax = $item_tax;

                    $purchase_line->tax_id = $tax_id;

                    $purchase_line->quantity = $qty;

                    $purchase_line->pp_without_discount = $purchase_price;

                    $purchase_line->purchase_price = $purchase_price;

                    $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;

                    $purchase_line->exp_date = $exp_date;

                    $purchase_line->lot_number = $lot_number;

                    $purchase_lines[] = $purchase_line;

                    $this->updateProductQuantity($location_id, $product->id, $variation_id, $qty_formated);

                    $this->updateProductQuantityStore($location_id, $product->id, $variation_id, $qty_formated);
                }

                //create transaction & purchase lines

                if (!empty($purchase_lines)) {

                    $transaction = Transaction::create(

                        [

                            'type' => 'opening_stock',

                            'opening_stock_product_id' => $product->id,

                            'status' => 'received',

                            'business_id' => $business_id,

                            'transaction_date' => $transaction_date,

                            'total_before_tax' => $purchase_total,

                            'location_id' => $location_id,

                            'final_total' => $purchase_total,

                            'payment_status' => 'paid',

                            'created_by' => $user_id,

                        ]

                    );

                    $transaction->purchase_lines()->saveMany($purchase_lines);

                    $opening_balance_equity_id = $this->account_exist_return_id('Opening Balance Equity Account');

                    /*
                     * MA-002 - the amount was landing in the WRONG PARAMETER.
                     *
                     * There are two createAccountTransaction methods with the
                     * same name and DIFFERENT parameter orders:
                     *
                     *   Util::createAccountTransaction(
                     *       $transaction, $type, $account_id, $amount, ...)
                     *
                     *   TransactionUtil::createAccountTransaction(
                     *       $transaction, $type, $account_id,
                     *       $transaction_payment_id = null, $sub_type = null,
                     *       $contact_id = null, $amount = 0, ...)
                     *
                     * This call passed $purchase_total as the 4th argument,
                     * meaning it as the amount. It resolves to TransactionUtil's
                     * version, where the 4th argument is TRANSACTION_PAYMENT_ID.
                     *
                     * The proof is in the data: 203 of the 274 unscoped rows
                     * have transaction_payment_id EXACTLY equal to their amount
                     * (the other 71 differ only by decimal truncation, e.g.
                     * 2541649.2 stored as 2541649). The amount column itself
                     * stayed correct only because $amount defaulted to 0 and
                     * the method fell back to $transaction->final_total.
                     *
                     * The amount is now passed in the 7th position, where that
                     * signature expects it, and transaction_payment_id is left
                     * null - which is correct, since an opening entry has no
                     * payment. The resulting amount is unchanged, because
                     * $purchase_total and $transaction->final_total are the
                     * same figure here.
                     */
                    $this->createAccountTransaction(
                        $transaction,
                        'credit',
                        $opening_balance_equity_id,
                        null,            // transaction_payment_id - an opening entry has none
                        null,            // sub_type
                        null,            // contact_id
                        $purchase_total  // amount - this is where it belongs
                    );
                }
            }
        }
    }

    /**

     * Add/Edit transaction purchase lines

     *

     * @param object $transaction

     * @param array $input_data

     * @param array $currency_details

     * @param boolean $enable_product_editing

     * @param string $before_status = null

     *

     * @return array

     */

    public function createOrUpdatePurchaseLines($transaction, $input_data, $currency_details, $enable_product_editing, $store_id, $before_status = null)
    {

        $updated_purchase_lines = [];

        $updated_purchase_line_ids = [0];

        $exchange_rate = !empty($transaction->exchange_rate) ? $transaction->exchange_rate : 1;

        foreach ($input_data as $data) {

            $multiplier = 1;

            if (isset($data['sub_unit_id']) && $data['sub_unit_id'] == $data['product_unit_id']) {

                unset($data['sub_unit_id']);
            }

            if (!empty($data['sub_unit_id'])) {

                $unit = Unit::find($data['sub_unit_id']);

                $multiplier = !empty($unit->base_unit_multiplier) ? $unit->base_unit_multiplier : 1;
            }

            $free_qty = !empty($data['free_qty']) ? $data['free_qty'] : 0;
            if (request()->input('free_qty_as_new_transaction') == 1) {
                $free_qty = 0;
            }
            $new_quantity = ($this->num_uf($data['quantity']) * $multiplier) + $free_qty;

            $new_quantity_f = $this->num_f($new_quantity);

            //update existing purchase line

            if (isset($data['purchase_line_id'])) {

                $purchase_line = PurchaseLine::findOrFail($data['purchase_line_id']);

                $updated_purchase_line_ids[] = $purchase_line->id;

                $old_quantity_with_bonus = $purchase_line->quantity + (!empty($purchase_line->bonus_qty) ? $purchase_line->bonus_qty : 0);
                
                $old_qty = $this->num_f($old_quantity_with_bonus);

                $this->updateProductStock($before_status, $transaction, $data['product_id'], $data['variation_id'], $new_quantity, $old_quantity_with_bonus, $currency_details, $store_id);
            } else {

                //create newly added purchase lines

                $purchase_line = new PurchaseLine();

                $purchase_line->product_id = $data['product_id'];

                $purchase_line->variation_id = $data['variation_id'];

                //Increase quantity only if status is received

                if ($transaction->status == 'received') {

                    $this->updateProductQuantity($transaction->location_id, $data['product_id'], $data['variation_id'], $new_quantity_f, 0, $currency_details);

                    $this->updateProductQuantityStore($transaction->location_id, $data['product_id'], $data['variation_id'], $new_quantity_f, $store_id, 0, $currency_details);
                }
            }

            $pp_without_discount =
                ($this->num_uf($data['pp_without_discount'], $currency_details) * $exchange_rate) / $multiplier;

            $discount_percent = 0;

            // Store discount amount ONLY if you still want it visible per line (optional)
            $discount_amount = !empty($data['discount_amount'])
                ? $this->num_uf($data['discount_amount'], $currency_details)
                : 0;

            // Price must NEVER change
            $purchase_price = $pp_without_discount;


            $purchase_line->quantity = $new_quantity;

            $purchase_line->bonus_qty = $free_qty;

            $purchase_line->discount_percent = $this->num_uf(!empty($data['discount_percent']) ? $data['discount_percent'] : 0, $currency_details);

            // $purchase_line->purchase_price = ($this->num_uf($data['purchase_price'], $currency_details) * $exchange_rate) / $multiplier;

            $purchase_line->purchase_price_inc_tax = ($this->num_uf(!empty($data['purchase_price_inc_tax']) ? $data['purchase_price_inc_tax'] : 0, $currency_details) * $exchange_rate) / $multiplier;

            $purchase_line->item_tax = ($this->num_uf(!empty($data['item_tax']) ? $data['item_tax'] : 0, $currency_details) * $exchange_rate) / $multiplier;

            $purchase_line->tax_id = !empty($data['purchase_line_tax_id']) ? $data['purchase_line_tax_id'] : null;

            $purchase_line->lot_number = !empty($data['lot_number']) ? $data['lot_number'] : null;

            $purchase_line->mfg_date = !empty($data['mfg_date']) ? $this->uf_date($data['mfg_date']) : null;

            $purchase_line->exp_date = !empty($data['exp_date']) ? $this->uf_date($data['exp_date']) : null;

            $purchase_line->sub_unit_id = !empty($data['sub_unit_id']) ? $data['sub_unit_id'] : null;

            $purchase_line->pp_without_discount = $pp_without_discount;
            $purchase_line->discount_amount     = $discount_amount;
            $purchase_line->purchase_price      = $purchase_price;

            // $purchase_line->discount_amount = !empty($data['discount_amount']) ? $data['discount_amount'] : null;

            // Snapshot the sell price at the time of purchase for F16A historical accuracy
            $variation = Variation::find($data['variation_id']);
            if ($variation) {
                $purchase_line->sell_price_at_purchase = $variation->sell_price_inc_tax ?? $variation->default_sell_price ?? 0;
            }

            $updated_purchase_lines[] = $purchase_line;

            //Edit product price

            if ($enable_product_editing == 1) {

                if (isset($data['default_sell_price'])) {

                    $variation_data['sell_price_inc_tax'] = ($this->num_uf($data['default_sell_price'], $currency_details)) / $multiplier;
                }

                $variation_data['pp_without_discount'] = ($this->num_uf($data['pp_without_discount'], $currency_details) * $exchange_rate) / $multiplier;

                $variation_data['variation_id'] = $purchase_line->variation_id;

                $variation_data['product_id'] = $purchase_line->product_id;

                $variation_data['purchase_price'] = $purchase_line->purchase_price;

                $this->updateProductFromPurchase($variation_data);
            }
        }

        //unset deleted purchase lines

        $delete_purchase_line_ids = [];

        $delete_purchase_lines = null;

        if (!empty($updated_purchase_line_ids)) {

            $delete_purchase_lines = PurchaseLine::where('transaction_id', $transaction->id)

                ->whereNotIn('id', $updated_purchase_line_ids)

                ->get();

            if ($delete_purchase_lines->count()) {

                foreach ($delete_purchase_lines as $delete_purchase_line) {

                    $delete_purchase_line_ids[] = $delete_purchase_line->id;

                    //decrease deleted only if previous status was received

                    if ($before_status == 'received') {

                        $this->decreaseProductQuantity(

                            $delete_purchase_line->product_id,

                            $delete_purchase_line->variation_id,

                            $transaction->location_id,

                            $delete_purchase_line->quantity,

                            0,

                            'decrease'

                        );



                        $store_id = request()->session()->get('business.default_store');

                        $this->decreaseProductQuantityStore(

                            $delete_purchase_line->product_id,

                            $delete_purchase_line->variation_id,

                            $transaction->location_id,

                            $delete_purchase_line->quantity,

                            $store_id,

                            "decrease",

                            0

                        );
                    }
                }

                //Delete deleted purchase lines

                PurchaseLine::where('transaction_id', $transaction->id)

                    ->whereIn('id', $delete_purchase_line_ids)

                    ->delete();
            }
        }

        //update purchase lines

        if (!empty($updated_purchase_lines)) {

            $transaction->purchase_lines()->saveMany($updated_purchase_lines);



        }

        return $delete_purchase_lines;
    }

    /**

     * Updates product stock after adding or updating purchase

     *

     * @param string $status_before

     * @param obj $transaction

     * @param integer $product_id

     * @param integer $variation_id

     * @param decimal $new_quantity in database format

     * @param decimal $old_quantity in database format

     * @param array $currency_details

     *

     */

    public function updateProductStock($status_before, $transaction, $product_id, $variation_id, $new_quantity, $old_quantity, $currency_details, $store_id = null)
    {

        $new_quantity_f = $this->num_f($new_quantity);

        $old_qty = $this->num_f($old_quantity);

        //Update quantity for existing products

        if ($status_before == 'received' && $transaction->status == 'received') {

            //if status received update existing quantity

            $this->updateProductQuantity($transaction->location_id, $product_id, $variation_id, $new_quantity_f, $old_qty, $currency_details);

            $this->updateProductQuantityStore($transaction->location_id, $product_id, $variation_id, $new_quantity_f, $store_id, $old_qty, $currency_details);
        } elseif ($status_before == 'received' && $transaction->status != 'received') {

            //decrease quantity only if status changed from received to not received

            $this->decreaseProductQuantity(

                $product_id,

                $variation_id,

                $transaction->location_id,

                $old_quantity,

                0,

                'decrease'

            );

            $this->decreaseProductQuantityStore(

                $product_id,

                $variation_id,

                $transaction->location_id,

                $old_quantity,

                $store_id,

                "decrease",

                0

            );
        } elseif ($status_before != 'received' && $transaction->status == 'received') {

            $this->updateProductQuantity($transaction->location_id, $product_id, $variation_id, $new_quantity_f, 0, $currency_details);

            $this->updateProductQuantityStore($transaction->location_id, $product_id, $variation_id, $new_quantity_f, $store_id, $old_qty, $currency_details);
        }
    }

    /**

     * Recalculates purchase line data according to subunit data

     *

     * @param integer $purchase_line

     * @param integer $business_id

     *

     * @return array

     */

    public function changePurchaseLineUnit($purchase_line, $business_id)
    {

        $base_unit = $purchase_line->product->unit;

        $sub_units = $base_unit->sub_units;

        $sub_unit_id = $purchase_line->sub_unit_id;

        $sub_unit = $sub_units->filter(function ($item) use ($sub_unit_id) {

            return $item->id == $sub_unit_id;
        })->first();

        if (!empty($sub_unit)) {

            $multiplier = $sub_unit->base_unit_multiplier;

            $purchase_line->quantity = $purchase_line->quantity / $multiplier;

            $purchase_line->pp_without_discount = $purchase_line->pp_without_discount * $multiplier;

            $purchase_line->purchase_price = $purchase_line->purchase_price * $multiplier;

            $purchase_line->purchase_price_inc_tax = $purchase_line->purchase_price_inc_tax * $multiplier;

            $purchase_line->item_tax = $purchase_line->item_tax * $multiplier;

            $purchase_line->quantity_returned = $purchase_line->quantity_returned / $multiplier;

            $purchase_line->quantity_sold = $purchase_line->quantity_sold / $multiplier;

            $purchase_line->quantity_adjusted = $purchase_line->quantity_adjusted / $multiplier;
        }

        //SubUnits

        $purchase_line->sub_units_options = $this->getSubUnits($business_id, $base_unit->id, false, $purchase_line->product_id);

        return $purchase_line;
    }

    /**

     * Recalculates sell line data according to subunit data

     *

     * @param integer $unit_id

     *

     * @return array

     */

    public function changeSellLineUnit($business_id, $sell_line)
    {

        $unit_details = $this->getSubUnits($business_id, $sell_line->unit_id, false, $sell_line->product_id);

        $sub_unit = null;

        $sub_unit_id = $sell_line->sub_unit_id;

        foreach ($unit_details as $key => $value) {

            if ($key == $sub_unit_id) {

                $sub_unit = $value;
            }
        }

        if (!empty($sub_unit)) {

            $multiplier = $sub_unit['multiplier'];

            $sell_line->quantity_ordered = $sell_line->quantity_ordered / $multiplier;

            $sell_line->item_tax = $sell_line->item_tax * $multiplier;

            $sell_line->default_sell_price = $sell_line->default_sell_price * $multiplier;

            $sell_line->unit_price_before_discount = $sell_line->unit_price_before_discount * $multiplier;

            $sell_line->sell_price_inc_tax = $sell_line->sell_price_inc_tax * $multiplier;

            $sell_line->sub_unit_multiplier = $multiplier;

            $sell_line->unit_details = $unit_details;
        }

        return $sell_line;
    }

    /**

     * Retrieves current stock of a variation for the given location

     *

     * @param int $variation_id, int location_id

     *

     * @return float

     */

    public function getCurrentStock($variation_id, $location_id)
    {

        $current_stock = VariationLocationDetails::where('variation_id', $variation_id)

            ->where('location_id', $location_id)

            ->value('qty_available');

        if (null == $current_stock) {

            $current_stock = 0;
        }

        return $current_stock;
    }

    /**
     * Rebuild qty_available for a variation/location from stock history.
     * This is useful after opening stock edits where incremental updates can drift.
     *
     * @param  int  $business_id
     * @param  int  $variation_id
     * @param  int  $location_id
     * @return float
     */
    public function syncVariationLocationQuantityFromHistory($business_id, $variation_id, $location_id)
    {
        $filters = [
            'search_box' => null,
            'sales_form_no' => null,
            'purchase_order_no' => null,
            'bill_no' => null,
            'customer_id' => null,
            'supplier_id' => null,
            'date_range' => null,
        ];

        $stock_history = $this->getVariationStockHistory($business_id, $variation_id, $location_id, $filters);
        // History is returned newest-first, so the first row is the current balance.
        $calculated_stock = !empty($stock_history) ? (float) $stock_history[0]['stock'] : 0;

        $variation = Variation::find($variation_id);
        if (empty($variation)) {
            return $calculated_stock;
        }

        $variation_location_details = VariationLocationDetails::firstOrNew([
            'variation_id' => $variation_id,
            'product_id' => $variation->product_id,
            'product_variation_id' => $variation->product_variation_id,
            'location_id' => $location_id,
        ]);

        $variation_location_details->qty_available = $calculated_stock;
        $variation_location_details->save();

        return $calculated_stock;
    }

    /**

     * Adjusts stock over selling with purchases, opening stocks andstock transfers

     * Also maps with respective sells

     *

     * @param obj $transaction

     *

     * @return void

     */

    public function adjustStockOverSelling($transaction)
    {

        if ($transaction->status != 'received') {

            return false;
        }

        foreach ($transaction->purchase_lines as $purchase_line) {

            if ($purchase_line->product->enable_stock == 1) {

                //Available quantity in the purchase line

                $purchase_line_qty_avlbl = $purchase_line->quantity_remaining;

                if ($purchase_line_qty_avlbl <= 0) {

                    continue;
                }

                //update sell line purchase line mapping

                $sell_line_purchase_lines =

                    TransactionSellLinesPurchaseLines::where('purchase_line_id', 0)

                    ->join('transaction_sell_lines as tsl', 'tsl.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')

                    ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')

                    ->where('t.location_id', $transaction->location_id)

                    ->where('tsl.variation_id', $purchase_line->variation_id)

                    ->where('tsl.product_id', $purchase_line->product_id)

                    ->select('transaction_sell_lines_purchase_lines.*')

                    ->get();

                foreach ($sell_line_purchase_lines as $slpl) {

                    if ($purchase_line_qty_avlbl > 0) {

                        if ($slpl->quantity <= $purchase_line_qty_avlbl) {

                            $purchase_line_qty_avlbl -= $slpl->quantity;

                            $slpl->purchase_line_id = $purchase_line->id;

                            $slpl->save();

                            //update purchase line quantity sold

                            $purchase_line->quantity_sold += $slpl->quantity;

                            $purchase_line->save();
                        } else {

                            $diff = $slpl->quantity - $purchase_line_qty_avlbl;

                            $slpl->purchase_line_id = $purchase_line->id;

                            $slpl->quantity = $purchase_line_qty_avlbl;

                            $slpl->save();

                            //update purchase line quantity sold

                            $purchase_line->quantity_sold += $slpl->quantity;

                            $purchase_line->save();

                            TransactionSellLinesPurchaseLines::create([

                                'sell_line_id' => $slpl->sell_line_id,

                                'purchase_line_id' => 0,

                                'quantity' => $diff,

                            ]);

                            break;
                        }
                    }
                }
            }
        }
    }

    /**

     * Finds out most relevant descount for the product

     *

     * @param obj $product, int $business_id, int $location_id, bool $is_cg,

     * bool $is_spg

     *

     * @return obj discount

     */

    public function getProductDiscount($product, $business_id, $location_id, $is_cg = false, $is_spg = false)
    {

        $now = \Carbon::now()->toDateTimeString();

        //Search if both category and brand matches

        $query1 = Discount::where('business_id', $business_id)

            ->where('location_id', $location_id)

            ->where('is_active', 1)

            ->where('starts_at', '<=', $now)

            ->where('ends_at', '>=', $now)

            ->where('brand_id', $product->brand_id)

            ->where('category_id', $product->category_id)

            ->orderBy('priority', 'desc')

            ->latest();

        if ($is_cg) {

            $query1->where('applicable_in_cg', 1);
        }

        if ($is_spg) {

            $query1->where('applicable_in_spg', 1);
        }

        $discount = $query1->first();

        //Search if either category or brand matches

        if (empty($discount)) {

            $query2 = Discount::where('business_id', $business_id)

                ->where('location_id', $location_id)

                ->where('is_active', 1)

                ->where('starts_at', '<=', $now)

                ->where('ends_at', '>=', $now)

                ->where(function ($q) use ($product) {

                    $q->whereRaw('(brand_id="' . $product->brand_id . '" AND category_id IS NULL)')

                        ->orWhereRaw('(category_id="' . $product->category_id . '" AND brand_id IS NULL)');
                })

                ->orderBy('priority', 'desc');

            if ($is_cg) {

                $query2->where('applicable_in_cg', 1);
            }

            if ($is_spg) {

                $query2->where('applicable_in_spg', 1);
            }

            $discount = $query2->first();
        }

        if (!empty($discount)) {

            $discount->formated_starts_at = $this->format_date($discount->starts_at->toDateTimeString(), true);

            $discount->formated_ends_at = $this->format_date($discount->ends_at->toDateTimeString(), true);
        }

        return $discount;
    }

    /**

     * Checks if products has manage stock enabled then Updates quantity for product and its

     * variations

     *

     * @param $location_id

     * @param $product_id

     * @param $variation_id

     * @param $new_quantity

     * @param $old_quantity = 0

     * @param $number_format = null

     * @param $uf_data = true, if false it will accept numbers in database format

     *

     * @return boolean

     */

    public function updateProductQuantityStore($location_id, $product_id, $variation_id, $new_quantity, $store_id = null, $old_quantity = 0, $number_format = null, $uf_data = true)
    {
        if ($uf_data) {
            $qty_difference = $this->num_uf($new_quantity, $number_format) - $this->num_uf($old_quantity, $number_format);
        } else {
            $qty_difference = $this->num_uf($new_quantity) - $this->num_uf($old_quantity);
        }

        if (abs($qty_difference) < 0.0000001) {
            return true;
        }

        $product = Product::find($product_id);
        if (empty($product)) {
            \Log::error("Product not found in updateProductQuantityStore. Product ID: {$product_id}");
            return false;
        }

        $variation = Variation::where('id', $variation_id)
            ->where('product_id', $product_id)
            ->first();
        if (empty($variation)) {
            \Log::error("Variation not found in updateProductQuantityStore. Product ID: {$product_id}, Variation ID: {$variation_id}");
            return false;
        }

        app(\App\Services\StoreStockIntegrityService::class)->adjustStoreStock(
            (int) $location_id,
            (int) $product_id,
            (int) $variation_id,
            (float) $qty_difference,
            !empty($store_id) ? (int) $store_id : null,
            (int) $variation->product_variation_id,
            true,
            (int) (request()->session()->get('user.business_id') ?: request()->session()->get('business.id') ?: 0)
        );

        return true;
    }
    /**

     * Checks if products has manage stock enabled then Updates quantity for product and its

     * variations in Opening Stock only

     *

     * @param $location_id

     * @param $product_id

     * @param $variation_id

     * @param $new_quantity

     * @param $old_quantity = 0

     * @param $number_format = null

     * @param $uf_data = true, if false it will accept numbers in database format

     *

     * @return boolean

     */

    public function updateProductQuantityStoreForOpeningStock($location_id, $product_id, $variation_id, $new_quantity, $store_id, $old_quantity = 0, $number_format = null, $uf_data = true)
    {
        $quantity = $uf_data
            ? $this->num_uf($new_quantity, $number_format)
            : $this->num_uf($new_quantity);

        $variation = Variation::where('id', $variation_id)
            ->where('product_id', $product_id)
            ->first();
        if (empty($variation)) {
            \Log::error("Variation not found in updateProductQuantityStoreForOpeningStock. Product ID: {$product_id}, Variation ID: {$variation_id}");
            return false;
        }

        app(\App\Services\StoreStockIntegrityService::class)->setStoreStock(
            (int) $location_id,
            (int) $product_id,
            (int) $variation_id,
            (float) $quantity,
            !empty($store_id) ? (int) $store_id : null,
            (int) $variation->product_variation_id,
            true,
            (int) (request()->session()->get('user.business_id') ?: request()->session()->get('business.id') ?: 0)
        );

        return true;
    }
    /**

     * Checks if products has manage stock enabled then Decrease quantity for product and its variations

     *

     * @param $product_id

     * @param $variation_id

     * @param $location_id

     * @param $new_quantity

     * @param $old_quantity = 0

     *

     * @return boolean

     */

    public function decreaseProductQuantityStore($product_id, $variation_id, $location_id, $new_quantity, $store_id, $type = "decrease", $old_quantity = 0)
    {
        $qty_difference = $this->num_uf($new_quantity) - $this->num_uf($old_quantity);
        if ($qty_difference < 0) {
            $qty_difference *= -1;
        }

        $product = Product::find($product_id);
        if (empty($product) || (int) $product->enable_stock !== 1 || abs($qty_difference) < 0.0000001) {
            return true;
        }

        $variation = Variation::where('id', $variation_id)
            ->where('product_id', $product_id)
            ->first();
        if (empty($variation)) {
            \Log::error("Variation not found in decreaseProductQuantityStore. Product ID: {$product_id}, Variation ID: {$variation_id}");
            return false;
        }

        $signedDelta = $type === 'increase' ? (float) $qty_difference : -(float) $qty_difference;

        app(\App\Services\StoreStockIntegrityService::class)->adjustStoreStock(
            (int) $location_id,
            (int) $product_id,
            (int) $variation_id,
            $signedDelta,
            !empty($store_id) ? (int) $store_id : null,
            (int) $variation->product_variation_id,
            true,
            (int) (request()->session()->get('user.business_id') ?: request()->session()->get('business.id') ?: 0)
        );

        return true;
    }
    public function getProductUnitsDropdown($product_id)
    {

        $business_id = request()->session()->get('user.business_id');

        $product = Product::find($product_id);

        $html = '';

        if (!empty($product)) {

            $sub_units = $this->getSubUnits($business_id, $product->unit_id, true, $product_id);

            $variation = Variation::where('product_id', $product_id)->first();

            $default_multiple_unit_price = [];

            if (!empty($variation)) {

                $default_multiple_unit_price = (array) json_decode($variation->default_multiple_unit_price);
            }

            if (count($sub_units) > 0) {

                $html .= '<select name="sub_unit_id" class="form-control input-sm sub_unit">';

                foreach ($sub_units as $key => $value) {

                    $html .= '<option value="' . $key . '" data-multiplier="' . $value["multiplier"] . '" data-unit_price="';

                    if (array_key_exists($key, $default_multiple_unit_price)) {

                        $html .= $default_multiple_unit_price[$key];
                    }

                    $html .= '"  data-unit_name="' . $value["name"] . '" data-allow_decimal="' . $value['allow_decimal'] . '" >

                            ' . $value["name"] . '

                        </option>';
                }

                $html .= '</select>';
            }
        }

        return $html;
    }



    public function getVariationStockDetails($business_id, $variation_id, $location_id,$filters, $store_id = null, $start_date = null, $end_date = null)
    {
        if (!empty($filters['date_range'])) {
            // $dates = explode(' - ', $filters['date_range']);
            $dates = preg_split('/\s[~-]\s/', $filters['date_range']);
            $filters['start_date'] = \Carbon\Carbon::createFromFormat('m/d/Y', $dates[0])->startOfDay();
            $filters['end_date']   = \Carbon\Carbon::createFromFormat('m/d/Y', $dates[1])->endOfDay();
        }
        // Sum purchases (received) and returns (final) separately for accuracy

        $sumPurchase = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')
            
        //    ->when($filters['supplier_id'], function($query, $supplier_id) {
        //         return $query->where('transactions.contact_id', $supplier_id);
        //     })
        //     ->when($filters['customer_id'], function($query, $customer_id) {
        //         return $query->where('transactions.contact_id', $customer_id);
        //     })
            ->when(!empty($filters['supplier_id']), function($query) use ($filters) {
                return $query->where('transactions.contact_id', $filters['supplier_id']);
            })
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function($query) use ($filters) {
                // return $query->whereBetween('transactions.created_at', [$filters['start_date'], $filters['end_date']]);
                return $query->whereBetween('transactions.transaction_date', [$filters['start_date'], $filters['end_date']]);
            })
            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->where('transactions.type', 'purchase')

            ->where('purchase_lines.variation_id', $variation_id)

            ->where('transactions.status', 'received')

            ->whereNull('purchase_lines.deleted_at')

            ->groupBy('purchase_lines.variation_id')

            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity, SUM(purchase_lines.quantity_returned) AS sum_quantity_returned')

            ->withoutTrashed()

            ->first();



        // Combined purchase return transactions carry quantity_returned on purchase_lines

        $sumCombinedReturns = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')

            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->where('transactions.type', 'purchase_return')
            ->when($filters['sales_form_no'], function ($query, $sales_form_no) {
                $query->where(function ($q) use ($sales_form_no) {
                    $q->where('transactions.type', 'sell')
                    ->where(function ($s) use ($sales_form_no) {
                        $s->where('transactions.invoice_no', $sales_form_no)   // Sale / POS No
                            ->orWhere('transactions.ref_no', $sales_form_no);    // Settlement No
                    });
                });
            })
           ->when($filters['purchase_order_no'], function ($query, $purchase_order_no) {
                $query->where(function ($q) use ($purchase_order_no) {
                    $q->where('transactions.type', 'purchase')
                    ->where(function ($p) use ($purchase_order_no) {
                        $p->where('transactions.ref_no', $purchase_order_no); // Purchase No
                    });
                });
            })

            ->where('purchase_lines.variation_id', $variation_id)

            ->where('transactions.status', 'final')

            ->whereNull('purchase_lines.deleted_at')

            ->groupBy('purchase_lines.variation_id')

            ->selectRaw('SUM(purchase_lines.quantity_returned) AS sum_quantity_returned')

            ->withoutTrashed()

            ->first();



        $sumDeleted = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')

            ->where('transactions.location_id', $location_id)



            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['_deleted_purchase'])

            ->where('purchase_lines.variation_id', $variation_id)

            ->where('transactions.status', 'received')

            ->whereNull('purchase_lines.deleted_at')

            ->groupBy('purchase_lines.variation_id')

            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity, SUM(purchase_lines.quantity_returned) AS sum_quantity_returned')

            ->withoutTrashed()

            ->first();



        $sumTransfer = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')



            ->where('transactions.location_id', $location_id)



            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['purchase_transfer'])

            ->where('purchase_lines.product_id', $variation_id)

            ->where('purchase_lines.variation_id', $variation_id)

            ->where('transactions.status', 'received')

            ->whereNull('purchase_lines.deleted_at')

            ->groupBy('purchase_lines.variation_id')

            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity')

            ->withoutTrashed()

            ->first();



        $sumProd = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')



            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['production_purchase'])

            ->where('purchase_lines.product_id', $variation_id)

            ->where('purchase_lines.variation_id', $variation_id)

            ->where('transactions.status', 'received')

            ->whereNull('purchase_lines.deleted_at')

            ->groupBy('purchase_lines.variation_id')

            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity')

            ->withoutTrashed()

            ->first();



        $sumOpening = Transaction::leftJoin('purchase_lines', 'transactions.id', 'purchase_lines.transaction_id')



            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['opening_stock'])

            ->where('purchase_lines.product_id', $variation_id)

            ->where('purchase_lines.variation_id', $variation_id)

            ->where('transactions.status', 'received')

            ->whereNull('purchase_lines.deleted_at')

            ->groupBy('purchase_lines.variation_id')

            ->selectRaw('SUM(purchase_lines.quantity) AS sum_quantity')

            ->withoutTrashed()

            ->first();



        $sumAdjust = Transaction::leftJoin('stock_adjustment_lines', 'transactions.id', 'stock_adjustment_lines.transaction_id')



            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['stock_adjustment'])

            ->where(function ($query) {
                $query->whereNull('transactions.sub_type')
                    ->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            })

            ->where('stock_adjustment_lines.product_id', $variation_id)

            ->where('stock_adjustment_lines.variation_id', $variation_id)

            ->where('transactions.status', 'received')

            ->select(

                DB::raw("SUM(CASE WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'increase' THEN stock_adjustment_lines.quantity ELSE 0 END) as increased"),

                DB::raw("SUM(CASE WHEN COALESCE(stock_adjustment_lines.stock_adjustment_type, stock_adjustment_lines.type) = 'decrease' THEN stock_adjustment_lines.quantity ELSE 0 END) as decreased")

            )

            ->withoutTrashed()

            ->get()

            ->first();



        $incr = !empty($sumAdjust) ? $sumAdjust->increased : 0;

        $decr = !empty($sumAdjust) ? $sumAdjust->decreased : 0;



        $sumOp = !empty($sumOpening) ? $sumOpening->sum_quantity : 0;

        $sumPt = !empty($sumTransfer) ? $sumTransfer->sum_quantity : 0;

        $sumPr = !empty($sumProd) ? $sumProd->sum_quantity : 0;



        $sumQuantity = !empty($sumPurchase) ? $sumPurchase->sum_quantity : 0;

        $sumQuantityReturned = (!empty($sumPurchase) ? $sumPurchase->sum_quantity_returned : 0)

            + (!empty($sumCombinedReturns) ? $sumCombinedReturns->sum_quantity_returned : 0);



        $sumQuantityDeleted = !empty($sumDeleted) ? $sumDeleted->sum_quantity : 0;

        $sumQuantityReturnedDeleted = !empty($sumDeleted) ? $sumDeleted->sum_quantity_returned : 0;



        $sumSell = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')



            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['sell', 'sell_return'])

            ->where('transaction_sell_lines.product_id', $variation_id)

            ->where('transaction_sell_lines.variation_id', $variation_id)

            ->where('transactions.status', 'final')

            ->whereNull('transaction_sell_lines.deleted_at')

            ->groupBy('transaction_sell_lines.variation_id')

            ->selectRaw('SUM(transaction_sell_lines.quantity) AS sum_quantity, SUM(transaction_sell_lines.quantity_returned) AS sum_quantity_returned')

            ->withoutTrashed()

            ->first();



        $sumSellTr = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')



            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['sell_transfer'])

            ->where('transaction_sell_lines.product_id', $variation_id)

            ->where('transaction_sell_lines.variation_id', $variation_id)

            ->where('transactions.status', 'final')

            ->whereNull('transaction_sell_lines.deleted_at')

            ->groupBy('transaction_sell_lines.variation_id')

            ->selectRaw('SUM(transaction_sell_lines.quantity) AS sum_quantity')

            ->withoutTrashed()

            ->first();



        $sumSellPr = Transaction::leftJoin('transaction_sell_lines', 'transactions.id', 'transaction_sell_lines.transaction_id')



            ->where('transactions.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })



            ->whereIn('transactions.type', ['production_sell'])

            ->where('transaction_sell_lines.product_id', $variation_id)

            ->where('transaction_sell_lines.variation_id', $variation_id)

            ->where('transactions.status', 'final')

            ->whereNull('transaction_sell_lines.deleted_at')

            ->groupBy('transaction_sell_lines.variation_id')

            ->selectRaw('SUM(transaction_sell_lines.quantity) AS sum_quantity')

            ->withoutTrashed()

            ->first();



        $sumQuantitySell = !empty($sumSell) ? $sumSell->sum_quantity : 0;

        $sumQuantityTr = !empty($sumSellTr) ? $sumSellTr->sum_quantity : 0;

        $sumQuantityPr = !empty($sumSellPr) ? $sumSellPr->sum_quantity : 0;



        $sumQuantitySell_2 = !empty($sumSell) ? $sumSell->sum_quantity_sell : 0;

        $sumQuantityReturnedSell = !empty($sumSell) ? $sumSell->sum_quantity_returned : 0;

        // Direct aggregation for purchase returns to align with stock history UI
        $purchaseReturnQty = DB::table('transactions as t')
            ->join('purchase_lines as pl', 'pl.transaction_id', '=', 't.id')
            ->whereIn('t.type', ['purchase_return'])
            ->whereIn('t.status', ['received', 'final'])
            ->where('t.location_id', $location_id)
            ->where(function ($q) use ($variation_id) {
                $q->where('pl.variation_id', $variation_id)
                    ->orWhere('pl.product_id', $variation_id);
            })
            ->sum('pl.quantity_returned');



        $bal = $sumOp - $sumQuantityDeleted + $sumQuantityReturnedDeleted + $sumQuantity - $sumQuantityReturned - $sumQuantitySell + $sumQuantityReturnedSell + $incr - $decr + $sumPt - $sumQuantityTr - $sumQuantityPr + $sumPr;



        $purchase_details = Variation::join('products as p', 'p.id', '=', 'variations.product_id')

            ->join('units', 'p.unit_id', '=', 'units.id')

            ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')

            ->leftjoin('purchase_lines as pl', 'pl.variation_id', '=', 'variations.id')

            ->leftjoin('transactions as t', 'pl.transaction_id', '=', 't.id')

            ->where('t.location_id', $location_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('t.is_settlement', 1)

                        ->where('t.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('t.is_settlement', '<>', 1);
                });
            })



            ->where('t.status', 'received')

            ->where('p.business_id', $business_id)

            ->where('variations.id', $variation_id)

            ->when($store_id != null, function ($query) use ($store_id) {

                return $query->where('t.store_id', $store_id);
            })

            ->when($start_date != null, function ($query) use ($start_date) {

                return $query->whereDate('t.transaction_date', '>=', $start_date);
            })

            ->when($end_date != null, function ($query) use ($end_date) {

                return $query->whereDate('t.transaction_date', '<=', $end_date);
            })

            ->select(

                DB::raw("SUM(IF(t.type='purchase' AND t.status='received', pl.quantity, 0)) as total_purchase"),

                DB::raw("SUM(IF(t.type='production_purchase' AND t.status='received', pl.quantity, 0)) as manufactured"),

                DB::raw("SUM(IF(t.type='purchase' AND t.status='received', pl.quantity_returned, 0) + IF(t.type='purchase_return' AND t.status='final', pl.quantity_returned, 0)) as total_purchase_return"),

                DB::raw("SUM(pl.quantity_adjusted) as total_adjusted"),

                DB::raw("SUM(IF(t.type='opening_stock', pl.quantity, 0)) as total_opening_stock"),

                DB::raw("SUM(IF(t.type='purchase_transfer', pl.quantity, 0)) as total_purchase_transfer"),

                'variations.sub_sku as sub_sku',

                'p.name as product',

                'p.type',

                'p.sku',

                'p.id as product_id',

                'units.short_name as unit',

                'pv.name as product_variation',

                'variations.name as variation_name',

                'variations.id as variation_id'

            )

            ->withoutTrashed()

            ->get()->first();



        $sell_details_query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')

            ->leftjoin('transaction_sell_lines as sl', 'sl.variation_id', '=', 'variations.id')

            ->join('transactions as t', 'sl.transaction_id', '=', 't.id')

            ->where('t.location_id', $location_id)

            ->where('t.status', 'final')

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('t.is_settlement', 1)

                        ->where('t.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('t.is_settlement', '<>', 1);
                });
            })



            ->where('p.business_id', $business_id)

            ->where('variations.id', $variation_id)



            ->when($start_date != null, function ($query) use ($start_date) {

                return $query->whereDate('t.transaction_date', '>=', $start_date);
            })

            ->when($end_date != null, function ($query) use ($end_date) {

                return $query->whereDate('t.transaction_date', '<=', $end_date);
            })



            ->withoutTrashed()

            ->select(

                DB::raw("SUM(IF(t.type='sell', sl.quantity, 0)) as total_sold"),

                DB::raw("SUM(IF(t.type='production_sell', sl.quantity, 0)) as input"),

                DB::raw("SUM(IF(t.type='sell', sl.quantity_returned, 0)) as total_sell_return"),

                DB::raw("SUM(IF(t.type='sell_transfer', sl.quantity, 0)) as total_sell_transfer")

            );



        if (!empty($store_id) && $store_id) {

            $sell_details_query->leftjoin('variation_store_details as vsd', 'variations.id', '=', 'vsd.variation_id')

                ->where('vsd.store_id', $store_id);
        }

        $sell_details = $sell_details_query->get()->first();

        $current_stock = VariationLocationDetails::where(
            'variation_id',

            $variation_id
        )

            ->where('location_id', $location_id)

            ->first();



        if ($purchase_details->type == 'variable') {

            $product_name = $purchase_details->product . ' - ' . $purchase_details->product_variation . ' - ' . $purchase_details->variation_name . ' (' . $purchase_details->sub_sku . ')';
        } else {

            $product_name = $purchase_details->product . ' (' . $purchase_details->sku . ')';
        }



        $output = [

            'variation' => $product_name,

            'unit' => $purchase_details->unit,

            'second_unit' => $purchase_details->unit,

            'total_purchase' => $sumQuantity,                                                             //$purchase_details->total_purchase,

            'total_purchase_return' => $purchaseReturnQty,

            'total_adjusted' => ($incr - $decr),                                                          //$purchase_details->total_adjusted,

            'total_opening_stock' => $sumOp,                                                                   //$purchase_details->total_opening_stock,

            'total_purchase_transfer' => $sumPt,                                                                   //$purchase_details->total_purchase_transfer,

            'total_sold' => $sumQuantitySell,                                                         //$sell_details->total_sold,

            'total_sell_return' => $sumQuantityReturnedSell,                                                 //$sell_details->total_sell_return,

            'total_sell_transfer' => $sumQuantityTr,                                                           //$sell_details->total_sell_transfer,

            'current_stock' => $current_stock->qty_available ?? 0,

            'input' => $sumQuantityPr, //$sell_details->input ?? 0,

            'manufactured' => $sumPr,         //$purchase_details->manufactured ?? 0

        ];



        return $output;
    }



    public function getVariationStockHistory($business_id, $variation_id, $location_id,$filters, $store_id = null, $start_date = null, $end_date = null)
    {

        if (!empty($filters['date_range'])) {
            // $dates = explode(' - ', $filters['date_range']);
            $dates = preg_split('/\s[~-]\s/', $filters['date_range']);
            $filters['start_date'] = \Carbon\Carbon::createFromFormat('m/d/Y', $dates[0])->startOfDay();
            $filters['end_date']   = \Carbon\Carbon::createFromFormat('m/d/Y', $dates[1])->endOfDay();
        }

        $stock_history = Transaction::leftJoin('transaction_sell_lines as sl', function ($join) use ($variation_id) {

            $join->on('sl.transaction_id', '=', 'transactions.id')

                ->where('sl.variation_id', '=', $variation_id)

                ->whereNull('sl.deleted_at');
        })

            ->leftJoin('purchase_lines as pl', function ($join) use ($variation_id) {

                $join->on('pl.transaction_id', '=', 'transactions.id')

                    ->where('pl.variation_id', '=', $variation_id)

                    ->whereNull('pl.deleted_at');
            })

            ->leftJoin('stock_adjustment_lines as al', function ($join) use ($variation_id) {

                $join->on('al.transaction_id', '=', 'transactions.id')

                    ->where('al.variation_id', '=', $variation_id);
            })

            ->leftJoin('transactions as return', function ($join) {

                $join->on('transactions.return_parent_id', '=', 'return.id')

                    ->whereNull('return.deleted_at');
            })

            ->leftJoin('purchase_lines as rpl', function ($join) use ($variation_id) {

                $join->on('rpl.transaction_id', '=', 'return.id')

                    ->where('rpl.variation_id', '=', $variation_id)

                    ->whereNull('rpl.deleted_at');
            })

            ->leftJoin('transaction_sell_lines as rsl', function ($join) use ($variation_id) {

                $join->on('rsl.transaction_id', '=', 'return.id')

                    ->where('rsl.variation_id', '=', $variation_id)

                    ->whereNull('rsl.deleted_at');
            })

            // ->leftJoin('contacts as c', function ($join) {

            //     $join->on('transactions.contact_id', '=', 'c.id')

            //         ->whereNull('c.deleted_at');
            // })

            ->leftJoin('contacts as c', function ($join) use ($business_id) {
                $join->on('transactions.contact_id', '=', 'c.id')
                    ->where('c.business_id', '=', $business_id) // CRITICAL
                    ->whereNull('c.deleted_at');
            })

            ->leftJoin('pump_operators as po', function ($join) {

                $join->on('transactions.pump_operator_id', '=', 'po.id');
            })

            ->leftJoin('transaction_payments as tp', 'tp.transaction_id', '=', 'transactions.id')

            ->leftJoin('accounts as acc', 'tp.account_id', '=', 'acc.id')


            // ->when($filters['supplier_id'], function($query, $supplier_id) {
            //     return $query->where('transactions.contact_id', $supplier_id);
            // })
            // ->when($filters['customer_id'], function($query, $customer_id) {
            //     return $query->where('transactions.contact_id', $customer_id);
            // })
            ->when($filters['supplier_id'], function($query, $supplier_id) {
                return $query->where(function ($q) use ($supplier_id) {
                    $q->where('transactions.contact_id', $supplier_id)
                    ->where('transactions.type', 'purchase');
                });
            })
            ->when($filters['customer_id'], function($query, $customer_id) {
                return $query->where(function ($q) use ($customer_id) {
                    $q->where('transactions.contact_id', $customer_id)
                    ->where('transactions.type', 'sell');
                });
            })
            ->when(!empty($filters['start_date']) && !empty($filters['end_date']), function($query) use ($filters) {
                return $query->whereBetween('transactions.created_at', [$filters['start_date'], $filters['end_date']]);
            })
            ->where('transactions.location_id', $location_id)
            ->where('transactions.business_id', $business_id)

            ->where(function ($query) {

                $query->where(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', 1)

                        ->where('transactions.is_credit_sale', 0);
                })->orWhere(function ($innerQuery) {

                    $innerQuery->where('transactions.is_settlement', '<>', 1);
                });
            })

            ->where(function ($q) use ($variation_id) {

                $q->where('sl.variation_id', $variation_id)

                    ->orWhere('pl.variation_id', $variation_id)

                    ->orWhere('al.variation_id', $variation_id)

                    ->orWhere('rpl.variation_id', $variation_id)

                    // ->orWhere('rsl.variation_id', $variation_id);

                    ->orWhere('rsl.variation_id', $variation_id);
            })

            ->whereIn('transactions.status', ['final', 'received'])

            ->whereIn('transactions.type', ['_deleted_purchase', 'sell', 'purchase', 'stock_adjustment', 'opening_stock', 'sell_transfer', 'purchase_transfer', 'production_purchase', 'purchase_return', 'sell_return', 'production_sell'])
            ->where(function ($query) {
                $query->where('transactions.type', '!=', 'stock_adjustment')
                    ->orWhereNull('transactions.sub_type')
                    ->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            })
            ->when($filters['sales_form_no'], function ($query, $sales_form_no) {
                $query->where(function ($q) use ($sales_form_no) {
                    $q->where('transactions.type', 'sell')
                    ->where(function ($s) use ($sales_form_no) {
                        $s->where('transactions.invoice_no', $sales_form_no)   // Sale / POS No
                            ->orWhere('transactions.ref_no', $sales_form_no);    // Settlement No
                    });
                });
            })
           ->when($filters['purchase_order_no'], function ($query, $purchase_order_no) {
                $query->where(function ($q) use ($purchase_order_no) {
                    $q->where('transactions.type', 'purchase')
                    ->where(function ($p) use ($purchase_order_no) {
                        $p->where('transactions.ref_no', $purchase_order_no); // Purchase No
                    });
                });
            })
            ->when($store_id != null, function ($query) use ($store_id) {

                return $query->leftJoin('variation_store_details as vsds', function ($join) {

                    $join->on('vsds.variation_id', '=', 'sl.variation_id')

                        ->whereNull('vsds.deleted_at');
                })

                    ->leftJoin('variation_store_details as vsdp', function ($join) {

                        $join->on('vsdp.variation_id', '=', 'pl.variation_id')

                            ->whereNull('vsdp.deleted_at');
                    })

                    ->where(function ($q) use ($store_id) {

                        $q->where('vsdp.store_id', $store_id)

                            ->orWhere('vsds.store_id', $store_id);
                    });
            })

            ->when($start_date != null, function ($query) use ($start_date) {

                return $query->whereDate('transactions.transaction_date', '>=', $start_date);
            })

            ->when($end_date != null, function ($query) use ($end_date) {

                return $query->whereDate('transactions.transaction_date', '<=', $end_date);
            })

            ->select(

                'transactions.id as transaction_id',

                'transactions.type as transaction_type',

                'sl.quantity as sell_line_quantity',

                'pl.quantity as purchase_line_quantity',

                'rsl.quantity_returned as sell_return',

                'rpl.quantity_returned as purchase_return',

                'pl.quantity_returned as direct_purchase_return',

                'al.quantity as stock_adjusted',

                DB::raw('COALESCE(al.stock_adjustment_type, al.type) as adjustment_type'),

                'pl.quantity_returned as combined_purchase_return',

                'transactions.return_parent_id',

                'transactions.transaction_date',

                'transactions.status',

                'transactions.invoice_no',

                'transactions.ref_no',

                'transactions.additional_notes',

                // 'tp.amount as payment_amount',

                'tp.method as payment_method',

                'transactions.final_total as payment_amount_debit',

                'transactions.final_total as stock_credit_amount',

                'transactions.payment_status',

                'tp.cheque_number',

                'acc.name as account_name',

                // \DB::raw('IFNULL(c.name, po.name) as contact_name'),

                \DB::raw("
                    CASE 
                        WHEN c.type = 'supplier' THEN c.name
                        WHEN c.type = 'customer' THEN c.name
                        ELSE COALESCE(po.name, c.name)
                    END as contact_name
                "),

                //'c.name as contact_name',

                // 'c.supplier_business_name',

                \DB::raw('IFNULL(transactions.store_id, return.store_id) as store_id')

            )

            ->orderBy('transactions.transaction_date', 'asc')

            ->orderBy('transactions.id', 'asc')

            ->withoutTrashed()

            ->get();

        $stock_history = $stock_history->unique('transaction_id');

        Log::debug($stock_history->toArray());

        $stock_history_array = [];

        $stock = 0;

        $stock_in_second_unit = 0;

        foreach ($stock_history as $stock_line) {



            $temp_array = [

                'date' => $stock_line->transaction_date,

                'transaction_id' => $stock_line->transaction_id,

                'contact_name' => $stock_line->contact_name,

                'supplier_business_name' => $stock_line->supplier_business_name,

            ];

            if ($stock_line->transaction_type == 'sell') {

                if ($stock_line->status != 'final') {

                    continue;
                }

                $quantity_change = -1 * $stock_line->sell_line_quantity;

                $stock += $quantity_change;



                $stock_in_second_unit -= $stock_line->sell_line_quantity;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'sell',

                    'type_label' => __('sale.sale'),

                    'ref_no' => $stock_line->invoice_no,

                    'sell_secondary_unit_quantity' => $stock_line->sell_line_quantity,

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'purchase') {

                if ($stock_line->status != 'received') {

                    continue;
                }

                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_in_second_unit += $stock_line->purchase_line_quantity;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'purchase',

                    'type_label' => __('lang_v1.purchase'),

                    'ref_no' => $stock_line->ref_no,

                    'purchase_secondary_unit_quantity' => $stock_line->purchase_line_quantity,

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == '_deleted_purchase') {

                if ($stock_line->status != 'received') {

                    continue;
                }

                $quantity_change = -1 * $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_in_second_unit += $stock_line->purchase_line_quantity;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'purchase_deleted',

                    'type_label' => "Deleted Purchase PO No " . $stock_line->invoice_no,

                    'ref_no' => $stock_line->ref_no,

                    'purchase_secondary_unit_quantity' => $stock_line->purchase_line_quantity,

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'stock_adjustment') {



                if ($stock_line->adjustment_type == 'increase') {

                    $quantity_change = $stock_line->stock_adjusted;

                    $label = __('stock_adjustment.stock_adjustment_increase');
                } elseif ($stock_line->adjustment_type == 'decrease') {

                    $quantity_change = -1 * $stock_line->stock_adjusted;

                    $label = __('stock_adjustment.stock_adjustment_decrease');
                } else {

                    continue;
                }



                $stock += $quantity_change;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'stock_adjustment',

                    'type_label' => $label,

                    'ref_no' => $stock_line->ref_no,
                    'description' => $stock_line->additional_notes,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'opening_stock') {

                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'opening_stock',

                    'type_label' => __('report.opening_stock'),

                    'ref_no' => $stock_line->ref_no ?? '',
                    'description' => $stock_line->additional_notes,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'sell_transfer') {

                if ($stock_line->status != 'final') {

                    continue;
                }

                $quantity_change = -1 * $stock_line->sell_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'sell_transfer',

                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.out') . ')',

                    'ref_no' => $stock_line->ref_no,

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'purchase_transfer') {

                if ($stock_line->status != 'received') {

                    continue;
                }



                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'purchase_transfer',

                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.in') . ')',

                    'ref_no' => $stock_line->ref_no,

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'production_sell') {

                if ($stock_line->status != 'final') {

                    continue;
                }

                $quantity_change = -1 * $stock_line->sell_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'sell',

                    'type_label' => __('manufacturing::lang.ingredient'),

                    'ref_no' => '',

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'production_purchase') {

                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    'type' => 'production_purchase',

                    'type_label' => __('manufacturing::lang.manufactured'),

                    'ref_no' => $stock_line->ref_no,

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            } elseif ($stock_line->transaction_type == 'purchase_return') {

                // Handle purchase return transactions
                $quantity_change = -1 * ($stock_line->combined_purchase_return + $stock_line->purchase_return + $stock_line->direct_purchase_return);

                // Only add to history if there's actually a quantity change
                if ($quantity_change != 0) {
                    $stock += $quantity_change;

                    $description = "Purchase Return No " . $stock_line->ref_no;

                    if (!empty($stock_line->contact_name)) {
                        $description .= " - " . $stock_line->contact_name;
                    }

                    $stock_history_array[] = array_merge($temp_array, [
                        'quantity_change' => $quantity_change,
                        'stock' => $this->roundQuantity($stock),
                        'type' => 'purchase_return',
                        'type_label' => __('lang_v1.purchase_return'),
                        'ref_no' => $stock_line->ref_no,
                        'description' => $description,
                    ]);
                }
            } elseif ($stock_line->transaction_type == 'sell_return') {

                $quantity_change = $stock_line->sell_return;

                $stock += $quantity_change;

                $stock_history_array[] = array_merge($temp_array, [

                    'quantity_change' => $quantity_change,

                    'stock' => $this->roundQuantity($stock),

                    // 'type' => 'purchase_transfer',

                    'type' => 'sell_return',

                    'type_label' => __('lang_v1.sell_return'),

                    'ref_no' => $stock_line->invoice_no,

                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),

                ]);
            }
        }



        return array_reverse($stock_history_array);
    }



    public function getTankStockDetails($business_id, $product_id, $location_id, $tank_id = null)
    {

        $purchase_details = Product::leftjoin('units', 'products.unit_id', '=', 'units.id')

            ->leftjoin('tank_purchase_lines as pl', 'pl.product_id', '=', 'products.id')

            ->leftjoin('transactions as t', function ($join) use ($location_id) {



                return $join->on('pl.transaction_id', 't.id')

                    ->on('t.location_id', \DB::raw($location_id));
            })

            ->where('products.business_id', $business_id)

            ->where('products.id', $product_id)

            ->when($tank_id != null, function ($query) use ($tank_id) {

                return $query->where('pl.tank_id', $tank_id);
            })

            ->select(

                DB::raw("SUM(IF(t.type='purchase' AND t.status='received', pl.quantity, 0)) as total_purchase"),

                DB::raw("SUM(IF(t.type='opening_stock', pl.quantity, 0)) as total_opening_stock"),

                DB::raw("SUM(IF(t.type='purchase_transfer', pl.quantity, 0)) as total_purchase_transfer"),

                'products.name as product_name',

                'products.type',

                'products.sku',

                'products.id as product_id',

                'units.short_name as unit'

            )

            ->get()

            ->first();



        $sell_details = Product::leftjoin('tank_sell_lines as sl', 'sl.product_id', '=', 'products.id')

            ->join('transactions as t', 'sl.transaction_id', '=', 't.id')

            ->where('t.location_id', $location_id)

            ->where('t.status', 'final')

            ->where('products.business_id', $business_id)

            ->where('products.id', $product_id)

            ->when($tank_id != null, function ($query) use ($tank_id) {

                return $query->where('sl.tank_id', $tank_id);
            })

            ->select(

                DB::raw("SUM(IF(t.type='sell', sl.quantity, 0)) as total_sold"),

                DB::raw("SUM(IF(t.type='sell_transfer', sl.quantity, 0)) as total_sell_transfer")

            )

            ->get()

            ->first();



        $transactionUtil = app(TransactionUtil::class);

        $total_adjusted_query = Transaction::join('stock_adjustment_lines as sal', 'transactions.id', '=', 'sal.transaction_id')
            ->where('transactions.location_id', $location_id)
            ->where('transactions.type', 'stock_adjustment')
            ->where('sal.product_id', $product_id);

        if ($tank_id !== null) {
            $total_adjusted_query->where('sal.tank_id', $tank_id);
        }

        $non_dip_adjustment_query = clone $total_adjusted_query;

        $non_dip_total_adjusted = (float) $non_dip_adjustment_query
            ->where(function ($query) {
                $query->whereNull('transactions.sub_type')
                    ->orWhere('transactions.sub_type', '!=', 'dip_resetting');
            })
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(sal.stock_adjustment_type, sal.type) = 'increase' THEN sal.quantity WHEN COALESCE(sal.stock_adjustment_type, sal.type) = 'decrease' THEN -1 * sal.quantity ELSE 0 END), 0) as total_adjusted")
            ->value('total_adjusted');

        $total_adjusted = (float) $total_adjusted_query
            ->selectRaw("COALESCE(SUM(CASE WHEN COALESCE(sal.stock_adjustment_type, sal.type) = 'increase' THEN sal.quantity WHEN COALESCE(sal.stock_adjustment_type, sal.type) = 'decrease' THEN -1 * sal.quantity ELSE 0 END), 0) as total_adjusted")
            ->value('total_adjusted');



        $balance = $transactionUtil->getTankProductBalanceByProductId($product_id, $location_id);



        $output = [
            'variation' => $purchase_details->product_name,
            'product_name' => $purchase_details->product_name,
            'unit' => $purchase_details->unit,
            'second_unit' => $purchase_details->unit,
            'total_purchase' => $purchase_details->total_purchase,
            'total_opening_stock' => $purchase_details->total_opening_stock,
            'total_adjusted' => $total_adjusted,
            'total_purchase_transfer' => $purchase_details->total_purchase_transfer,
            'total_sold' => $sell_details->total_sold,
            'total_sell_return' => 0,
            'total_sell_transfer' => $sell_details->total_sell_transfer,
            'current_stock' => ($balance ?? 0) + $non_dip_total_adjusted,
            'total_purchase_return' => 0,
            'input' => 0,
            'manufactured' => 0,

        ];

        return $output;
    }



    public function getTankStockHistory($business_id, $product_id, $location_id, $tank_id = null)
    {



        $stock_history = Transaction::leftjoin(
            'tank_sell_lines as sl',

            'sl.transaction_id',
            '=',
            'transactions.id'
        )

            ->leftjoin('tank_purchase_lines as pl', 'pl.transaction_id', '=', 'transactions.id')

            ->leftjoin('stock_adjustment_lines as al', 'al.transaction_id', '=', 'transactions.id')

            ->where('transactions.location_id', $location_id)

            ->where(function ($q) use ($product_id) {

                return $q->where('sl.product_id', $product_id)

                    ->orWhere('pl.product_id', $product_id)

                    ->orWhere('al.product_id', $product_id);
            })

            ->when($tank_id != null, function ($q) use ($tank_id) {

                return $q->where('sl.tank_id', $tank_id)

                    ->orWhere('pl.tank_id', $tank_id)
                    ->orWhere('al.tank_id', $tank_id);
            })

            ->where(function ($query) {
                $query->whereIn('transactions.type', ['sell', 'purchase', 'opening_stock', 'sell_transfer', 'purchase_transfer', 'production_purchase'])
                    ->orWhere('transactions.type', 'stock_adjustment');
            })

            ->select(

                'transactions.id as transaction_id',

                'transactions.type as transaction_type',

                'sl.quantity as sell_line_quantity',

                'pl.quantity as purchase_line_quantity',

                'al.quantity as stock_adjusted',
                DB::raw('COALESCE(al.stock_adjustment_type, al.type) as adjustment_type'),
                'transactions.sub_type',

                'transactions.transaction_date',

                'transactions.status',

                'transactions.invoice_no',

                'transactions.ref_no',
                'transactions.additional_notes',
                \DB::raw('IFNULL(sl.tank_id, pl.tank_id) as tank_id')

            )

            ->orderBy('transactions.id', 'asc');



        $stock_history = $stock_history->get();



        $stock_history_array = [];

        $stock = 0;

        foreach ($stock_history as $stock_line) {

            if ($stock_line->transaction_type == 'sell') {

                if ($stock_line->status != 'final') {

                    continue;
                }

                $quantity_change = -1 * $stock_line->sell_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = [

                    'date' => $stock_line->transaction_date,

                    'quantity_change' => $quantity_change,

                    'stock' => round($stock),

                    'type' => 'sell',

                    'type_label' => __('sale.sale'),

                    'ref_no' => $stock_line->invoice_no,

                    'transaction_id' => $stock_line->transaction_id,

                ];
            } elseif ($stock_line->transaction_type == 'purchase') {

                if ($stock_line->status != 'received') {

                    continue;
                }

                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = [

                    'date' => $stock_line->transaction_date,

                    'quantity_change' => $quantity_change,

                    'stock' => round($stock),

                    'type' => 'purchase',

                    'type_label' => __('lang_v1.purchase'),

                    'ref_no' => $stock_line->ref_no,

                    'transaction_id' => $stock_line->transaction_id,

                ];
            } elseif ($stock_line->transaction_type == 'opening_stock') {

                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = [

                    'date' => $stock_line->transaction_date,

                    'quantity_change' => $quantity_change,

                    'stock' => round($stock),

                    'type' => 'opening_stock',

                    'type_label' => __('report.opening_stock'),

                    'ref_no' => $stock_line->ref_no ?? '',

                    'transaction_id' => $stock_line->transaction_id,

                ];
            } elseif ($stock_line->transaction_type == 'sell_transfer') {

                $quantity_change = -1 * $stock_line->sell_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = [

                    'date' => $stock_line->transaction_date,

                    'quantity_change' => $quantity_change,

                    'stock' => round($stock),

                    'type' => 'sell_transfer',

                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.out') . ')',

                    'ref_no' => $stock_line->ref_no,

                    'transaction_id' => $stock_line->transaction_id,

                ];
            } elseif ($stock_line->transaction_type == 'purchase_transfer') {

                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = [

                    'date' => $stock_line->transaction_date,

                    'quantity_change' => $quantity_change,

                    'stock' => round($stock),

                    'type' => 'purchase_transfer',

                    'type_label' => __('lang_v1.stock_transfers') . ' (' . __('lang_v1.in') . ')',

                    'ref_no' => $stock_line->ref_no,

                    'transaction_id' => $stock_line->transaction_id,

                ];
            } elseif ($stock_line->transaction_type == 'production_purchase') {

                $quantity_change = $stock_line->purchase_line_quantity;

                $stock += $quantity_change;

                $stock_history_array[] = [

                    'date' => $stock_line->transaction_date,

                    'quantity_change' => $quantity_change,

                    'stock' => round($stock),

                    'type' => 'production_purchase',

                    'type_label' => __('manufacturing::lang.manufactured'),

                    'ref_no' => $stock_line->ref_no,

                    'transaction_id' => $stock_line->transaction_id,

                ];
            } elseif ($stock_line->transaction_type == 'stock_adjustment') {
                if ($stock_line->adjustment_type == 'increase') {
                    $quantity_change = (float) $stock_line->stock_adjusted;
                    $label = ($stock_line->sub_type == 'dip_resetting') ? __('petro::lang.dip_reset') . ' (' . __('lang_v1.in') . ')' : __('lang_v1.stock_adjustment') . ' (' . __('stock_adjustment.increase') . ')';
                } elseif ($stock_line->adjustment_type == 'decrease') {
                    $quantity_change = -1 * (float) $stock_line->stock_adjusted;
                    $label = ($stock_line->sub_type == 'dip_resetting') ? __('petro::lang.dip_reset') . ' (' . __('lang_v1.out') . ')' : __('lang_v1.stock_adjustment') . ' (' . __('stock_adjustment.decrease') . ')';
                } else {
                    continue;
                }

                $stock += $quantity_change;

                $stock_history_array[] = [
                    'date' => $stock_line->transaction_date,
                    'quantity_change' => $quantity_change,
                    'stock' => round($stock, 3),
                    'type' => 'stock_adjustment',
                    'type_label' => $label,
                    'adjustment_type' => $stock_line->adjustment_type,
                    'ref_no' => $stock_line->ref_no,
                    'description' => !empty($stock_line->additional_notes) ? $stock_line->additional_notes : (($stock_line->sub_type == 'dip_resetting') ? __('petro::lang.dip_reset') : __('lang_v1.stock_adjustment')),
                    'transaction_id' => $stock_line->transaction_id,
                ];
            }
        }

        return array_reverse($stock_history_array);
    }

    public function getProductStockDetails($business_id, $filters, $for, $module = null)
    {
        // Temporarily disable caching to fix DataTable error
        // $cache_key = 'product_stock_details_' . $business_id . '_' . md5(serialize($filters) . $for . $module);

        try {
            DB::enableQueryLog();

            $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')

                ->join('units', 'p.unit_id', '=', 'units.id')

                ->leftjoin('variation_location_details as vld', 'p.id', '=', 'vld.product_id')

                // Fuel products get their live stock from fuel_tanks. Keep a separate left join so
                // fuel stock rows are not lost when there is no variation_location_details row.
                ->leftjoin('fuel_tanks as ft', function ($join) use ($business_id) {
                    $join->on('p.id', '=', 'ft.product_id')
                        ->where('ft.business_id', '=', $business_id);
                })

                ->leftjoin('business_locations as l', 'vld.location_id', '=', 'l.id')

                ->leftjoin('business_locations as ft_l', 'ft.location_id', '=', 'ft_l.id')

                ->leftjoin('categories as c', 'p.category_id', '=', 'c.id')

                ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')

                ->leftjoin('variation_store_details as vsd', 'variations.id', '=', 'vsd.variation_id')

                ->where('p.business_id', $business_id)

                ->whereIn('p.type', ['single', 'variable']);



            if (!empty($module)) {

                $query->where(function ($q) use ($module) {

                    $q->whereNull('p.disabled_in')->orwhereRaw("NOT FIND_IN_SET(?, p.disabled_in)", [$module]);
                });
            }



            $permitted_locations = auth()->user()->permitted_locations();

            $location_filter = '';



            if ($permitted_locations != 'all') {

                $query->where(function ($q) use ($permitted_locations) {
                    $q->whereIn('vld.location_id', $permitted_locations)
                        ->orWhereIn('ft.location_id', $permitted_locations);
                });



                $locations_imploded = implode(', ', $permitted_locations);

                $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
            }



            if (!empty($filters['location_id'])) {

                $location_id = $filters['location_id'];



                $query->where(function ($q) use ($location_id) {
                    $q->where('vld.location_id', $location_id)
                        ->orWhere('ft.location_id', $location_id);
                });



                $location_filter .= "AND transactions.location_id=$location_id";



                //If filter by location then hide products not available in that location

                $query->join('product_locations as pl', 'pl.product_id', '=', 'p.id')

                    ->where(function ($q) use ($location_id) {

                        $q->where('pl.location_id', $location_id);
                    });
            }



            if (!empty($filters['category_id'])) {

                $query->where('p.category_id', $filters['category_id']);
            }

            if (!empty($filters['sub_category_id'])) {

                $query->where('p.sub_category_id', $filters['sub_category_id']);
            }

            if (!empty($filters['brand_id'])) {

                $query->where('p.brand_id', $filters['brand_id']);
            }

            if (!empty($filters['unit_id'])) {

                $query->where('p.unit_id', $filters['unit_id']);
            }



            if (!empty($filters['tax_id'])) {

                $query->where('p.tax', $filters['tax_id']);
            }



            if (!empty($filters['type'])) {

                $query->where('p.type', $filters['type']);
            }



            if (isset($filters['only_mfg_products']) && $filters['only_mfg_products'] == 1) {

                $query->join('mfg_recipes as mr', 'mr.variation_id', '=', 'variations.id');
            }



            if (isset($filters['active_state']) && $filters['active_state'] == 'active') {

                $query->where('p.is_inactive', 0);
            }

            if (isset($filters['active_state']) && $filters['active_state'] == 'inactive') {

                $query->where('p.is_inactive', 1);
            }

            if (isset($filters['not_for_selling']) && $filters['not_for_selling'] == 1) {

                $query->where('p.not_for_selling', 1);
            }



            if (!empty($filters['repair_model_id'])) {

                $query->where('p.repair_model_id', request()->get('repair_model_id'));
            }



            //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN

            $pl_query_string = $this->get_pl_quantity_sum_string('pl');



            if ($for == 'view_product' && !empty(request()->input('product_id'))) {

                $location_filter .= 'AND transactions.location_id=l.id';
            }



            if (!empty($filters['start_date']) && !empty($filters['end_date'])) {



                $start_date = date('Y-m-d', strtotime($filters['start_date']));



                $end_date = date('Y-m-d', strtotime($filters['end_date']));
            }



            // $location_filter = '';



            $location_filter_no_date = $location_filter;

            // Date-only filter for total_purchased (no location_id constraint)
            $date_filter = '';

            if (!empty($start_date) && !empty($end_date)) {
                // Use transaction dates instead of variation creation dates for better performance
                $location_filter .= " AND date(transactions.transaction_date) >= '$start_date' ";
                $location_filter .= " AND date(transactions.transaction_date) <= '$end_date' ";
                $date_filter .= " AND date(transactions.transaction_date) >= '$start_date' ";
                $date_filter .= " AND date(transactions.transaction_date) <= '$end_date' ";
            }

            // Check if stores are being used
            // When store_id filter is applied, we definitely need store-wise calculations
            $uses_stores = !empty($filters['store_id']);
            $store_filter = '';
            if (!empty($filters['store_id'])) {
                $location_filter .= " AND transactions.store_id =" . $filters['store_id'];
                $date_filter .= " AND transactions.store_id =" . $filters['store_id'];
                $query->where('vsd.store_id', $filters['store_id']);
                $store_filter = " AND ((vsd.store_id IS NULL AND transactions.store_id IS NULL) OR (vsd.store_id IS NOT NULL AND transactions.store_id=vsd.store_id))";
            }

            $before_start_filter_no_date = $location_filter_no_date;
            if (!empty($filters['store_id'])) {
                $before_start_filter_no_date .= " AND transactions.store_id =" . $filters['store_id'];
            }

            $before_start_filter = $before_start_filter_no_date;
            if (!empty($start_date)) {
                $before_start_filter .= " AND date(transactions.transaction_date) < '$start_date'";
            } else {
                // No date range selected: prevent opening_stock from absorbing all purchases.
                // Use today as the cutoff so opening_stock = balance before today,
                // and total_purchased = purchases on today (matching $location_filter with no date).
                $today = date('Y-m-d');
                $before_start_filter .= " AND date(transactions.transaction_date) < '$today'";
            }

            $products = $query->select(

                // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND

                //     transaction_sell_lines.product_id=products.id) as total_sold"),



                DB::raw("(SELECT COALESCE(SUM(TSL.quantity - TSL.quantity_returned), 0) FROM transactions

                  JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                  WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=vld.location_id 
                  $store_filter
                  $location_filter

                  AND TSL.variation_id=variations.id) as total_sold"),

                // DB::raw("(SELECT COALESCE(SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0)), 0) FROM transactions

                //   JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id

                //   WHERE transactions.status='final' AND transactions.type='sell_transfer' AND transactions.location_id=vld.location_id $location_filter AND (TSL.variation_id=variations.id)) as total_transfered"),

                DB::raw("(
                    COALESCE((
                        SELECT SUM(TSL.quantity)
                        FROM transactions
                        JOIN transaction_sell_lines TSL ON transactions.id = TSL.transaction_id
                        WHERE transactions.status = 'final'
                        AND transactions.type = 'sell_transfer'
                        AND transactions.location_id = vld.location_id
                        $location_filter
                        AND TSL.variation_id = variations.id
                    ), 0)

                    +

                    COALESCE((
                        SELECT SUM(DSTL.qty)
                        FROM distribution_stock_transfer_lines DSTL
                        JOIN distribution_stock_transfers DST
                            ON DST.id = DSTL.stock_transfer_id
                        AND DST.location_id = vld.location_id
                        AND DSTL.variation_id = variations.id
                    ), 0)

                ) as total_transfered"),


                DB::raw("(SELECT COALESCE(SUM(

                  CASE

                      WHEN COALESCE(SAL.stock_adjustment_type, SAL.type) = 'increase' THEN SAL.quantity

                      WHEN COALESCE(SAL.stock_adjustment_type, SAL.type) = 'decrease' THEN -SAL.quantity

                      ELSE 0

                  END), 0)

                  FROM transactions

                  JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id

                  WHERE transactions.type='stock_adjustment' AND transactions.location_id=vld.location_id $location_filter
                    AND COALESCE(transactions.sub_type, '') != 'dip_resetting'

                    AND (SAL.variation_id=variations.id)) as total_adjusted"),

                // DB::raw("(SELECT SUM( COALESCE(pl.quantity - ($pl_query_string), 0) * purchase_price_inc_tax) FROM transactions

                //       JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id

                //       WHERE (transactions.status='received' OR transactions.type='purchase_return')  AND transactions.location_id=vld.location_id $location_filter

                //       AND (pl.variation_id=variations.id)) as stock_price"),

                DB::raw("(
                    SELECT COALESCE(SUM(PL.quantity), 0)
                    FROM purchase_lines PL
                    INNER JOIN transactions t ON t.id = PL.transaction_id
                    WHERE t.type = 'purchase'
                    AND t.status = 'received'
                    AND PL.variation_id = variations.id
                ) as total_purchased"),

                DB::raw("(SELECT COALESCE(SUM(COALESCE(PL.quantity_returned, PL.quantity, 0)), 0) FROM transactions

                            JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id

                            WHERE transactions.status='final' AND transactions.type='purchase_return' AND transactions.location_id=vld.location_id

                            AND (PL.variation_id=variations.id) $location_filter) as total_purchase_returned"),

                DB::raw("(
                    COALESCE((
                        SELECT SUM(PL.quantity)
                        FROM transactions
                        JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='received'
                        AND (
                            (transactions.type = 'opening_stock' $before_start_filter_no_date)
                            OR
                            (transactions.type = 'purchase' $before_start_filter)
                        )
                        AND transactions.location_id=vld.location_id
                        AND PL.variation_id=variations.id
                        $store_filter
                    ), 0)
                    -
                    COALESCE((
                        SELECT SUM(TSL.quantity - TSL.quantity_returned)
                        FROM transactions
                        JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='sell'
                        AND transactions.location_id=vld.location_id
                        $store_filter
                        AND TSL.variation_id=variations.id
                        $before_start_filter
                    ), 0)
                    -
                    COALESCE((
                        SELECT SUM(COALESCE(PL.quantity_returned, PL.quantity, 0))
                        FROM transactions
                        JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                        WHERE transactions.status='final' AND transactions.type='purchase_return'
                        AND transactions.location_id=vld.location_id
                        AND PL.variation_id=variations.id
                        $store_filter
                        $before_start_filter
                    ), 0)
                ) as opening_stock"),

                // Use store-wise stock when stores are involved
                // This ensures correct stock balances when sales are done from different stores
                // When vsd.store_id is not null, use vsd.qty_available; otherwise use vld.qty_available
                DB::raw("COALESCE(
                    CASE 
                        WHEN vsd.store_id IS NOT NULL
                        THEN COALESCE(SUM(vsd.qty_available), 0) 
                        ELSE COALESCE(SUM(vld.qty_available), 0) 
                    END, 0) as stock"),

                'variations.sub_sku as sku',

                DB::raw("COALESCE(
                    (SELECT pl.purchase_price_inc_tax 
                    FROM transactions t 
                    JOIN purchase_lines pl ON t.id = pl.transaction_id 
                    WHERE t.status = 'received' AND t.type = 'purchase' 
                    AND pl.variation_id = variations.id 
                    ORDER BY t.transaction_date DESC, t.id DESC 
                    LIMIT 1), 
                    variations.dpp_inc_tax,
                    0
                ) as stock_price"),

                'variations.id as vid',

                'p.name as product',

                'p.type',

                'p.sku as prod_sku',

                'p.alert_quantity',

                'p.id as product_id',

                'units.short_name as unit',

                'p.enable_stock as enable_stock',

                'variations.sell_price_inc_tax as unit_price',

                'pv.name as product_variation',

                'variations.name as variation_name',

                DB::raw('COALESCE(l.name, ft_l.name) as location_name'),

                DB::raw('COALESCE(l.id, ft_l.id) as location_id'),

                'variations.id as variation_id',

                'c.name as category_name',

                'p.product_custom_field1',

                'p.product_custom_field2',

                'p.product_custom_field3',

                'p.product_custom_field4'

            )->groupBy('variations.id', 'p.id', 'vld.location_id', 'ft.location_id' . ($uses_stores ? ', vsd.store_id' : ''));



            if (isset($filters['show_manufacturing_data']) && $filters['show_manufacturing_data']) {

                $pl_query_string = $this->get_pl_quantity_sum_string('PL');

                $products->addSelect(

                    DB::raw("(SELECT COALESCE(SUM(PL.quantity - ($pl_query_string)), 0) FROM transactions

                    JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id

                    WHERE transactions.status='received' AND transactions.type='production_purchase' AND transactions.location_id=vld.location_id

                    AND (PL.variation_id=variations.id)) as total_mfg_stock")

                );
            }



            if (!empty($filters['product_id'])) {

                $products->where('p.id', $filters['product_id'])

                    ->groupBy('p.id', 'l.id');
            }

            if ($for == 'view_product') {
                $result = $products->get();
            } else if ($for == 'api') {
                $result = $products->paginate();
            } else {
                $result = $products;
            }

            // Temporarily disable caching to fix DataTable error
            // if ($for != 'view_product' && $for != 'api') {
            //     Cache::put($cache_key, $result, 300); // 5 minutes
            // }

            return $result;
        } catch (\Exception $e) {
            \Log::error('Product Stock Details Error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());

            // Return empty result to prevent DataTable error
            if ($for == 'view_product') {
                return collect();
            } else if ($for == 'api') {
                return collect()->paginate();
            } else {
                return collect();
            }
        }
    }



    public function updateProductVariationStock($purchases, $business_id, $store_id)
    {

        $fuel_category_id = Category::where('business_id', $business_id)->where('name', 'Fuel')->first();

        foreach ($purchases as $purchase) {

            $product = Product::findOrFail($purchase['product_id']);

            if ($product->category == $fuel_category_id) {

                continue;
            }



            $variation = Variation::where('id', $purchase['variation_id'])

                ->where('product_id', $purchase['product_id'])

                ->first();

            $variation_store_d = VariationStoreDetail

                ::where('variation_id', $variation->id)

                ->where('product_id', $purchase['product_id'])

                ->where('product_variation_id', $variation->product_variation_id)

                ->where('store_id', $store_id)

                ->first();

            if (empty($variation_store_d)) {

                $variation_store_d = new VariationStoreDetail();

                $variation_store_d->variation_id = $variation->id;

                $variation_store_d->product_id = $purchase['product_id'];

                $variation_store_d->store_id = $store_id;

                $variation_store_d->product_variation_id = $variation->product_variation_id;

                $variation_store_d->qty_available = 0;
            }

            $variation_store_d->qty_available += $purchase['quantity'];

            $variation_store_d->save();
        }

        return true;
    }
}
