<?php

namespace App\Utils\TransactionUtil;

use App\AccountTransaction;
use App\Account;
use App\AccountType;
use Modules\Fleet\Entities\Fleet;
use App\Business;
use App\BusinessLocation;
use App\Utils\Util;
use App\Category;
use App\Contact;
use App\ContactLedger;
use App\Currency;
use App\Events\TransactionPaymentAdded;
use App\Events\TransactionPaymentDeleted;
use App\Events\TransactionPaymentUpdated;
use App\Exceptions\PurchaseSellMismatch;
use App\Http\Controllers\Ecom\ContactController;
use App\InvoiceScheme;
use App\Product;
use App\PurchaseLine;
use App\Restaurant\ResTable;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\StockAdjustmentLine;
use App\TransactionSellLinesPurchaseLines;
use App\Variation;
use App\VariationLocationDetails;
use App\VariationStoreDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\PaymentMethod;
use App\System;;
use Illuminate\Support\Facades\Auth;
use Modules\Petro\Entities\FuelTank;
use Modules\Petro\Entities\PumpOperator;
use Modules\Petro\Entities\TankSellLine;
use Modules\Petro\Entities\TankPurchaseLine;
use Modules\Property\Entities\Property;
use Modules\Property\Entities\PropertyBlock;
use Modules\Property\Entities\PropertySellLine;
use Modules\Property\Entities\PropertyAccountSetting;
use Modules\Petro\Entities\DipReading;
use Modules\Petro\Entities\PumpOperatorCommission;
use App\Variation_store_detail;
use App\ExpenseCategory;
use App\Utils\ModuleUtil;
use App\Utils\ContactUtil;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\TankTransfer;
use Modules\Vat\Entities\VatCustomerStatement;
use Modules\Vat\Entities\VatCustomerStatementDetail;
use Modules\Shipping\Entities\ShippingAgentCommission;
use Modules\Shipping\Entities\ShippingPartnerCommission;
use Modules\SMS\Entities\SmsListInterest;
use Modules\Superadmin\Entities\RefillBusiness;
use App\SmsLog;
use Modules\Superadmin\Entities\Subscription;
use Modules\Vat\Entities\VatSetting;
use Modules\Superadmin\Entities\SmsApiClient;
use Modules\Superadmin\Entities\SmsReminderSetting;
use Modules\Petro\Entities\OtherSale;
use Modules\Petro\Entities\PumpOperatorOtherSale;
use App\ProductVariation;
use App\Unit;
use App\Brands;
use Modules\Petro\Entities\DailyVoucherItem;
use Modules\Petro\Entities\DailyVoucher;
use App\Http\Controllers\SellController;
use Illuminate\Http\Request;

/**
 * Receipt and invoice detail building for printing.
 *
 * MA-002: split out of App\Utils\TransactionUtil, which was 11,666 lines in
 * a single file with 180 methods.
 *
 * THIS IS CORE, NOT A MODULE - it is used across the whole system, so the
 * split is deliberately the safest kind available: a trait. The class keeps
 * its name, its namespace and every one of its methods, so all 485 call sites
 * that reach into TransactionUtil resolve exactly as before. Nothing outside
 * this directory needed to change.
 *
 * Method bodies are byte-identical to the original.
 *
 * Methods here: getReceiptDetails, getOtherSaleReceiptDetails, getCreditSaleReceiptDetails, getCustomerDetails, _receiptDetailsSellLines, _receiptOtherSaleDetailsSellLines, _receiptDetailsSellReturnLines, getInvoiceNumber, getInvoiceScheme
 */
trait BuildsReceipts
{
public function getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type)
    {
        $il = $invoice_layout;
        $transaction = Transaction::find($transaction_id);
        $rep = $transaction->reprint_no + 1;
        $transaction->reprint_no = $rep;
        $transaction->save();

        $transaction_type = $transaction->type;
        $footer_top_margin = System::getProperty('footer_top_margin');
        $admin_invoice_footer = System::getProperty('admin_invoice_footer');
        $output = [
            'header_text' => isset($il->header_text) ? $il->header_text : '',
            'business_name' => ($il->show_business_name == 1) ? $business_details->name : '',
            'location_name' => ($il->show_location_name == 1) ? $location_details->name : '',
            'sub_heading_line1' => trim($il->sub_heading_line1),
            'sub_heading_line2' => trim($il->sub_heading_line2),
            'sub_heading_line3' => trim($il->sub_heading_line3),
            'sub_heading_line4' => trim($il->sub_heading_line4),
            'sub_heading_line5' => trim($il->sub_heading_line5),
            'table_product_label' => $il->table_product_label,
            'table_qty_label' => $il->table_qty_label,
            'table_unit_price_label' => $il->table_unit_price_label,
            'table_subtotal_label' => $il->table_subtotal_label,
            'font_size' => $il->font_size,
            'header_font_size' => $il->header_font_size,
            'footer_font_size' => $il->footer_font_size,
            'business_name_font_size' => $il->business_name_font_size,
            'invoice_heading_font_size' => $il->invoice_heading_font_size,
            'footer_top_margin' => $footer_top_margin,
            'admin_invoice_footer' => $admin_invoice_footer,
            'logo_height' => $il->logo_height,
            'logo_width' => $il->logo_width,
            'logo_margin_top' => $il->logo_margin_top,
            'logo_margin_bottom' => $il->logo_margin_bottom,
            'header_align' => $il->header_align,
            'reprint' => $rep,
            'tax_amount' => $transaction->tax_amount
        ];
        //Display name
        $output['display_name'] = $output['business_name'];
        if (!empty($output['location_name'])) {
            if (!empty($output['display_name'])) {
                $output['display_name'] .= ', ';
            }
            $output['display_name'] .= $output['location_name'];
        }
        $contact_details = $this->getCustomerDetails($transaction->contact_id);
        $output['contact_details'] = $contact_details;
        //Logo
        $output['logo'] = $il->show_logo != 0 && !empty($il->logo) && file_exists(public_path('uploads/invoice_logos/' . $il->logo)) ? asset('uploads/invoice_logos/' . $il->logo) : false;
        //Address
        $output['address'] = '';
        $temp = [];
        if ($il->show_landmark == 1) {
            $output['address'] .= $location_details->landmark . "\n";
        }
        if ($il->show_city == 1 && !empty($location_details->city)) {
            $temp[] = $location_details->city;
        }
        if ($il->show_state == 1 && !empty($location_details->state)) {
            $temp[] = $location_details->state;
        }
        if ($il->show_zip_code == 1 && !empty($location_details->zip_code)) {
            $temp[] = $location_details->zip_code;
        }
        if ($il->show_country == 1 && !empty($location_details->country)) {
            $temp[] = $location_details->country;
        }
        if (!empty($temp)) {
            $output['address'] .= implode(',', $temp);
        }
        $output['website'] = $location_details->website;
        $output['location_custom_fields'] = '';
        $temp = [];
        $location_custom_field_settings = !empty($il->location_custom_fields) ? $il->location_custom_fields : [];
        if (!empty($location_details->custom_field1) && in_array('custom_field1', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field1;
        }
        if (!empty($location_details->custom_field2) && in_array('custom_field2', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field2;
        }
        if (!empty($location_details->custom_field3) && in_array('custom_field3', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field3;
        }
        if (!empty($location_details->custom_field4) && in_array('custom_field4', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field4;
        }
        if (!empty($temp)) {
            $output['location_custom_fields'] .= implode(', ', $temp);
        }
        //Tax Info
        $output['tax_label1'] = !empty($business_details->tax_label_1) ? $business_details->tax_label_1 . ': ' : '';
        $output['tax_info1'] = $business_details->tax_number_1;

        $output['tax_label2'] = !empty($business_details->tax_label_2) ? $business_details->tax_label_2 . ': ' : '';
        $output['tax_info2'] = $business_details->tax_number_2;

        if ($il->show_tax_1 == 1 && !empty($business_details->tax_number_1)) {
            $output['tax_label1'] = !empty($business_details->tax_label_1) ? $business_details->tax_label_1 . ': ' : '';
            $output['tax_info1'] = $business_details->tax_number_1;
        }
        if ($il->show_tax_2 == 1 && !empty($business_details->tax_number_2)) {
            if (!empty($output['tax_info1'])) {
                $output['tax_info1'] .= ', ';
            }
            $output['tax_label2'] = !empty($business_details->tax_label_2) ? $business_details->tax_label_2 . ': ' : '';
            $output['tax_info2'] = $business_details->tax_number_2;
        }
        //Shop Contact Info
        $output['contact'] = '';
        if ($il->show_mobile_number == 1 && !empty($location_details->mobile)) {
            $output['contact'] .= __('contact.mobile') . ': ' . $location_details->mobile;
        }
        if ($il->show_alternate_number == 1 && !empty($location_details->alternate_number)) {
            if (empty($output['contact'])) {
                $output['contact'] .= __('contact.mobile') . ': ' . $location_details->alternate_number;
            } else {
                $output['contact'] .= ', ' . $location_details->alternate_number;
            }
        }
        if ($il->show_email == 1 && !empty($location_details->email)) {
            if (!empty($output['contact'])) {
                // $output['contact'] .= "\n";
            }
            $output['contact'] .= __('business.email') . ': ' . $location_details->email;
        }
        //Customer show_customer
        $customer = Contact::find($transaction->contact_id);
        $output['customer_info'] = '';
        $output['customer_tax_number'] = '';
        $output['customer_tax_label'] = '';
        $output['customer_custom_fields'] = '';
        if ($il->show_customer == 1) {
            $output['customer_label'] = !empty($il->customer_label) ? $il->customer_label : '';
            $output['customer_name'] = !empty($customer->name) ? $customer->name : '';
            if (!empty($output['customer_name']) && $receipt_printer_type != 'printer') {
                $output['customer_info'] .= $customer->landmark;
                // $output['customer_info'] .= '<br>' . implode(',', array_filter([$customer->city, $customer->state, $customer->country]));
                $output['customer_info'] .= '<br>' . $customer->mobile;
            }
            $output['customer_tax_number'] = !empty($customer->tax_number) ? $customer->tax_number : null;
            $output['customer_tax_label'] = !empty($il->client_tax_label) ? $il->client_tax_label : '';
            $temp = [];
            $customer_custom_fields_settings = !empty($il->contact_custom_fields) ? $il->contact_custom_fields : [];
            if (!empty($customer->custom_field1) && in_array('custom_field1', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field1;
            }
            if (!empty($customer->custom_field2) && in_array('custom_field2', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field2;
            }
            if (!empty($customer->custom_field3) && in_array('custom_field3', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field3;
            }
            if (!empty($customer->custom_field4) && in_array('custom_field4', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field4;
            }
            if (!empty($temp)) {
                $output['customer_custom_fields'] .= implode(',', $temp);
            }
        }
        if ($il->show_reward_point == 1) {
            $output['customer_rp_label'] = $business_details->rp_name;
            $output['customer_total_rp'] = $customer->total_rp;
        }
        $output['client_id'] = '';
        $output['client_id_label'] = '';
        if ($il->show_client_id == 1) {
            $output['client_id_label'] = !empty($il->client_id_label) ? $il->client_id_label : '';
            $output['client_id'] = !empty($customer->contact_id) ? $customer->contact_id : '';
        }
        //Sales person info
        $output['sales_person'] = '';
        $output['sales_person_label'] = '';
        if ($il->show_sales_person == 1) {
            $output['sales_person_label'] = !empty($il->sales_person_label) ? $il->sales_person_label : '';
            $output['sales_person'] = !empty($transaction->sales_person->user_full_name) ? $transaction->sales_person->user_full_name : '';
        }
        //Invoice info
        $output['invoice_no'] = $transaction->is_quotation ? $transaction->ref_no : $transaction->invoice_no;
        $output['quotation_no'] = $transaction->is_quotation ? $transaction->invoice_no : '';
        $output['shipping_address'] = !empty($transaction->shipping_address()) ? $transaction->shipping_address() : $transaction->shipping_address;
        //Heading & invoice label, when quotation use the quotation heading.
        if ($transaction_type == 'sell_return') {
            $output['invoice_heading'] = $il->cn_heading;
            $output['invoice_no_prefix'] = $il->cn_no_label;
        } elseif ($transaction->status == 'draft' && $transaction->is_quotation == 1) {
            $output['invoice_heading'] = $il->quotation_heading;
            $output['invoice_no_prefix'] = $il->quotation_no_prefix;
        } else {
            $output['invoice_no_prefix'] = $il->invoice_no_prefix;
            $output['invoice_heading'] = $il->invoice_heading;
            if ($transaction->payment_status == 'paid' && !empty($il->invoice_heading_paid)) {
                $output['invoice_heading'] .= ' ' . $il->invoice_heading_paid;
            } elseif (in_array($transaction->payment_status, ['due', 'partial']) && !empty($il->invoice_heading_not_paid)) {
                $output['invoice_heading'] .= ' ' . $il->invoice_heading_not_paid;
            }
        }
        $output['date_label'] = $il->date_label;
        if (blank($il->date_time_format)) {
            $output['invoice_date'] = $this->format_date($transaction->transaction_date, true, $business_details);
        } else {
            $output['invoice_date'] = \Carbon::createFromFormat('Y-m-d H:i:s', $transaction->transaction_date)->format($il->date_time_format);
        }
        if (!empty($il->common_settings['show_due_date'])) {
            $output['due_date_label'] = !empty($il->common_settings['due_date_label']) ? $il->common_settings['due_date_label'] : '';
            $due_date = $transaction->due_date;
            if (!empty($due_date)) {
                if (blank($il->date_time_format)) {
                    $output['due_date'] = $this->format_date($due_date->toDateTimeString(), true, $business_details);
                } else {
                    $output['due_date'] = \Carbon::createFromFormat('Y-m-d H:i:s', $due_date->toDateTimeString())->format($il->date_time_format);
                }
            }
        }
        $show_currency = true;
        if ($receipt_printer_type == 'printer' && trim($business_details->currency_symbol) != '$') {
            $show_currency = false;
        }
        //Invoice product lines
        $is_lot_number_enabled = $business_details->enable_lot_number;
        $is_product_expiry_enabled = $business_details->enable_product_expiry;
        $output['lines'] = [];
        // Debug: Log the transaction type and lines count
        \Log::info('Receipt generation - Transaction type: ' . $transaction_type . ', Transaction ID: ' . $transaction_id);

        // Debug: Check if transaction exists and has sell lines
        $sell_lines_count = $transaction->sell_lines()->count();
        \Log::info('Receipt generation - Transaction sell lines count: ' . $sell_lines_count);

        // Ensure $details is always defined to avoid undefined variable notices
        $details = ['lines' => []];
        if ($transaction_type == 'sell' || $transaction_type == 'opening_stock') {
            $sell_line_relations = ['product', 'product.unit', 'product.brand', 'product.category', 'variations', 'variations.product_variation', 'modifiers', 'sub_unit', 'warranties'];
            if ($is_lot_number_enabled == 1) {
                $sell_line_relations[] = 'lot_details';
            }
            $lines = $transaction->sell_lines()
                ->where(function ($q) {
                    $q->whereNull('parent_sell_line_id')
                        ->orWhere('parent_sell_line_id', 0);
                })
                ->with($sell_line_relations)
                ->get();
            foreach ($lines as $key => $value) {
                if (!empty($value->sub_unit_id)) {
                    $formated_sell_line = $this->recalculateSellLineTotals($business_details->id, $value);
                    $lines[$key] = $formated_sell_line;
                }
            }

            $details = $this->_receiptDetailsSellLines($lines, $il, $business_details);
            \Log::info('Receipt generation - ' . ucfirst($transaction_type) . ' lines count: ' . $lines->count() . ', Details lines count: ' . count($details['lines']));

            $output['lines'] = $details['lines'];
            $output['taxes'] = [];
            foreach ($details['lines'] as $line) {
                if (!empty($line['group_tax_details'])) {
                    foreach ($line['group_tax_details'] as $tax_group_detail) {
                        if (!isset($output['taxes'][$tax_group_detail['name']])) {
                            $output['taxes'][$tax_group_detail['name']] = 0;
                        }
                        $output['taxes'][$tax_group_detail['name']] += $tax_group_detail['calculated_tax'];
                    }
                } elseif (!empty($line['tax_unformatted']) && $line['tax_unformatted'] != 0) {
                    if (!isset($output['taxes'][$line['tax_name']])) {
                        $output['taxes'][$line['tax_name']] = 0;
                    }
                    $output['taxes'][$line['tax_name']] += $line['tax_unformatted'];
                }
            }
        } elseif ($transaction_type == 'autoservice') {
            $sell_line_relations = ['product', 'product.unit', 'product.brand', 'product.category', 'variations', 'variations.product_variation', 'modifiers', 'sub_unit', 'warranties'];
            if ($is_lot_number_enabled == 1) {
                $sell_line_relations[] = 'lot_details';
            }
            $lines = $transaction->sell_lines()
                ->where(function ($q) {
                    $q->whereNull('parent_sell_line_id')
                        ->orWhere('parent_sell_line_id', 0);
                })
                ->with($sell_line_relations)
                ->get();
            foreach ($lines as $key => $value) {
                if (!empty($value->sub_unit_id)) {
                    $formated_sell_line = $this->recalculateSellLineTotals($business_details->id, $value);
                    $lines[$key] = $formated_sell_line;
                }
            }

            $details = $this->_receiptDetailsSellLines($lines, $il, $business_details);
            \Log::info('Receipt generation - Autoservice lines count: ' . $lines->count() . ', Details lines count: ' . count($details['lines']));

            $output['lines'] = $details['lines'];
            $output['taxes'] = [];
            foreach ($details['lines'] as $line) {
                if (!empty($line['group_tax_details'])) {
                    foreach ($line['group_tax_details'] as $tax_group_detail) {
                        if (!isset($output['taxes'][$tax_group_detail['name']])) {
                            $output['taxes'][$tax_group_detail['name']] = 0;
                        }
                        $output['taxes'][$tax_group_detail['name']] += $tax_group_detail['calculated_tax'];
                    }
                } elseif (!empty($line['tax_unformatted']) && $line['tax_unformatted'] != 0) {
                    if (!isset($output['taxes'][$line['tax_name']])) {
                        $output['taxes'][$line['tax_name']] = 0;
                    }
                    $output['taxes'][$line['tax_name']] += $line['tax_unformatted'];
                }
            }
        } elseif ($transaction_type == 'sell_return') {
            $parent_sell = Transaction::find($transaction->return_parent_id);
            $lines = $parent_sell->sell_lines;
            foreach ($lines as $key => $value) {
                if (!empty($value->sub_unit_id)) {
                    $formated_sell_line = $this->recalculateSellLineTotals($business_details->id, $value);
                    $lines[$key] = $formated_sell_line;
                }
            }
            $details = $this->_receiptDetailsSellReturnLines($lines, $il, $business_details);
            $output['lines'] = $details['lines'];
            $output['taxes'] = [];
            foreach ($details['lines'] as $line) {
                if (!empty($line['group_tax_details'])) {
                    foreach ($line['group_tax_details'] as $tax_group_detail) {
                        if (!isset($output['taxes'][$tax_group_detail['name']])) {
                            $output['taxes'][$tax_group_detail['name']] = 0;
                        }
                        $output['taxes'][$tax_group_detail['name']] += $tax_group_detail['calculated_tax'];
                    }
                }
            }
        }



        //show cat code
        $output['show_cat_code'] = $il->show_cat_code;
        $output['cat_code_label'] = $il->cat_code_label;
        
        // Calculate totals from line items
        // 1. Total Price (excluding tax) - sum of (unit_price_exc_tax * qty)
        $total_price_exc_tax = 0;
        foreach ($details['lines'] as $line) {
            $unit_price_exc = isset($line['unit_price_exc_tax']) ? (float) str_replace(',', '', $line['unit_price_exc_tax']) : 0;
            $qty = isset($line['quantity']) ? (float) str_replace(',', '', $line['quantity']) : 0;
            $total_price_exc_tax += ($unit_price_exc * $qty);
        }
        
        // Get tax percentage from line items (use the first non-zero tax percentage found)
        $tax_percentage = 0;
        foreach ($details['lines'] as $line) {
            if (isset($line['tax_percent']) && $line['tax_percent'] > 0) {
                $tax_percentage = $line['tax_percent'];
                break;
            }
        }
        
        // 2. Subtotal (including tax, before discount) - sum of (unit_price_inc_tax * qty)
        $subtotal_inc_tax = 0;
        foreach ($details['lines'] as $line) {
            $unit_price_inc = isset($line['unit_price_inc_tax']) ? (float) str_replace(',', '', $line['unit_price_inc_tax']) : 0;
            $qty = isset($line['quantity']) ? (float) str_replace(',', '', $line['quantity']) : 0;
            $subtotal_inc_tax += ($unit_price_inc * $qty);
        }
        
        // 3. Total Discount
        //    - Start with sum of all line-level discounts
        $total_line_discount = 0;
        foreach ($details['lines'] as $line) {
            $line_discount = isset($line['line_discount']) ? (float) str_replace(',', '', $line['line_discount']) : 0;
            $total_line_discount += $line_discount;
        }

        //    - Use the authoritative final_total from the DB to derive discount
        //      and tax values. This guarantees the receipt Total always matches
        //      what was actually charged, regardless of rounding or discount type.
        $actual_final_total = (float) $transaction->final_total;
        $actual_shipping = (float) ($transaction->shipping_charges ?? 0);

        // The effective total after discount (excluding shipping) is what the
        // customer pays for the products alone.
        $total_after_discount = $actual_final_total - $actual_shipping;

        // Total discount = subtotal (inc tax, before discount) minus the product total.
        $total_discount = $subtotal_inc_tax - $total_after_discount;
        if ($total_discount < 0.01) {
            $total_discount = 0;
            // Recalculate total_after_discount in case subtotal_inc_tax was slightly off
            $total_after_discount = $subtotal_inc_tax;
        }
        
        // 4. Calculate tax & totals based on the total AFTER discount.
        $total_tax_from_lines = 0;

        if ($tax_percentage > 0 && $total_after_discount > 0) {
            // Tax = Total (inc. tax) - [Total (inc. tax) / (1 + Tax Rate)]
            $total_tax_from_lines = $total_after_discount - ($total_after_discount / (1 + ($tax_percentage / 100)));
            // Total before tax = Total (inc. tax) - Tax
            $total_price_exc_tax = $total_after_discount - $total_tax_from_lines;
        } else {
            // Fallback: sum tax from line items if no tax percentage found
            $total_tax_from_lines = array_sum(
                array_map(function ($value) {
                    return isset($value) ? (float) str_replace(',', '', $value) : 0;
                }, array_column($details['lines'], 'tax_unformatted'))
            );
            // $total_price_exc_tax was already calculated above from unit_price_exc_tax
        }
        
        // Set the new output fields
        $output['total_price_before_discount_label'] = 'Total Price Before Discount:';
        $output['total_price_before_discount'] = $this->num_f($subtotal_inc_tax, $show_currency, $business_details);

        // 2. Discount shown in the footer should match the explicit discounts.
        //    Using subtotal - final_total gets corrupted by shipping & order tax.
        $output['total_discount_label'] = 'Discount:';
        $output['total_discount'] = $this->num_f($total_discount, $show_currency, $business_details);
        
        // 3. Total Before Tax (exc. tax, based on subtotal before discount)
        $output['total_price_exc_tax_label'] = 'Total Before Tax:';
        $output['total_price_exc_tax'] = $this->num_f($total_price_exc_tax, $show_currency, $business_details);
        
        // 4. Total Tax
        $output['total_tax_label'] = 'Total Tax' . ($tax_percentage > 0 ? ' (' . $this->floattostr($tax_percentage) . '%):' : ':');
        $output['total_tax'] = $this->num_f($total_tax_from_lines, $show_currency, $business_details);
        
        $output['subtotal_inc_tax_label'] = $il->sub_total_label . ':';
        $output['subtotal_inc_tax'] = $this->num_f($subtotal_inc_tax, $show_currency, $business_details);
        
        //Subtotal (old - keeping for compatibility)
        $output['subtotal_label'] = $il->sub_total_label . ':';
        $output['subtotal'] = ($transaction->total_before_tax != 0) ? $this->num_f($transaction->total_before_tax, $show_currency, $business_details) : 0;

        $subtotal_final = array_sum(
            array_map(function ($value) {
                return isset($value) ? (float) str_replace(',', '', $value) : 0;
            }, array_column($details['lines'], 'sub_total_final'))
        );

        $output['subtotal_final'] = $this->num_f($subtotal_final, false, $business_details);
        
        $final_total = floatval(str_replace(',', '', $transaction->final_total));
        $calculated_subtotal_plus_tax = $subtotal_final + $total_tax_from_lines;
        
        if (abs($final_total - $calculated_subtotal_plus_tax) > 0.01) {
            $total_tax_from_lines = $final_total - $subtotal_final;
        }

        $output['subtotal_unformatted'] = ($transaction->total_before_tax != 0) ? $transaction->total_before_tax : 0;
        //Discount (bill-wise summary)
        $output['line_discount_label'] = $invoice_layout->discount_label;
        $output['discount_label'] = $invoice_layout->discount_label;
        $output['discount_label'] .= ($transaction->discount_type == 'percentage') ? ' (' . $this->floattostr($transaction->discount_amount) . '%) :' : '';
        $output['discount'] = ($total_discount != 0) ? $this->num_f($total_discount, $show_currency, $business_details) : 0;
        //reward points
        if ($business_details->enable_rp == 1 && !empty($transaction->rp_redeemed)) {
            $output['reward_point_label'] = $business_details->rp_name;
            $output['reward_point_amount'] = $this->num_f($transaction->rp_redeemed_amount, $show_currency, $business_details);
        }
        //Format tax
        if (!empty($output['taxes'])) {
            foreach ($output['taxes'] as $key => $value) {
                $output['taxes'][$key] = $this->num_f($value, $show_currency, $business_details);
            }
        }
        //Order Tax
        $tax = $transaction->tax;
        $output['tax_label'] = $invoice_layout->tax_label;
        $output['line_tax_label'] = $invoice_layout->tax_label;
        if (!empty($tax) && !empty($tax->name)) {
            $output['tax_label'] .= ' (' . $tax->name . ')';
        }
        $output['tax_label'] .= ':';

        $output['tax'] = ($total_tax_from_lines > 0) ? $this->num_f($total_tax_from_lines, $show_currency, $business_details) : (($transaction->tax_amount != 0) ? $this->num_f($transaction->tax_amount, $show_currency, $business_details) : 0);

        if ($transaction->tax_amount != 0 && !empty($tax) && $tax->is_tax_group) {
            $transaction_group_tax_details = $this->groupTaxDetails($tax, $transaction->tax_amount);
            $output['group_tax_details'] = [];
            foreach ($transaction_group_tax_details as $value) {
                $output['group_tax_details'][$value['name']] = $this->num_f($value['calculated_tax'], $show_currency, $business_details);
            }
        }
        //Shipping charges
        $output['shipping_charges'] = ($transaction->shipping_charges != 0) ? $this->num_f($transaction->shipping_charges, $show_currency, $business_details) : 0;
        $output['shipping_charges_label'] = trans("sale.shipping_charges");
        //Shipping details
        $output['shipping_details'] = $transaction->shipping_details;
        $output['shipping_details_label'] = trans("sale.shipping_details");
        //Total
        if ($transaction_type == 'sell_return') {
            $output['total_label'] = $invoice_layout->cn_amount_label . ':';
            $output['total'] = $this->num_f($transaction->final_total, $show_currency, $business_details);
        } else {
            $output['total_label'] = $invoice_layout->total_label . ':';
            // For opening_stock, calculate total from sell lines instead of using stored final_total
            if ($transaction_type == 'opening_stock' && !empty($details['lines'])) {
                $calculated_total = array_sum(array_map(function ($line) {
                    return isset($line['line_total']) ? (float) str_replace(',', '', $line['line_total']) : 0;
                }, $details['lines']));
                $output['total'] = $this->num_f($calculated_total, $show_currency, $business_details);
                \Log::info('Opening stock total calculated from lines: ' . $calculated_total . ', original final_total: ' . $transaction->final_total);
            } else {
                $output['total'] = $this->num_f($transaction->final_total, $show_currency, $business_details);
            }
        }
        //Paid & Amount due, only if final
        $output['total_paid_label'] = $il->paid_label;
        $output['total_due_label'] = $il->total_due_label;

        if (($transaction_type == 'sell' || $transaction_type == 'opening_stock') && $transaction->status == 'final') {
            $paid_amount = $this->getTotalPaid($transaction->id);
            // For opening_stock, use calculated total from lines
            $final_total_for_calculation = $transaction->final_total;
            if ($transaction_type == 'opening_stock' && !empty($details['lines'])) {
                $final_total_for_calculation = array_sum(array_map(function ($line) {
                    return isset($line['line_total']) ? (float) str_replace(',', '', $line['line_total']) : 0;
                }, $details['lines']));
            }
            $due = $final_total_for_calculation - $paid_amount;
            $output['total_paid'] = ($paid_amount == 0) ? 0 : $this->num_f($paid_amount, $show_currency, $business_details);
            $output['total_due'] = ($due == 0) ? 0 : $this->num_f($due, $show_currency, $business_details);

            if ($il->show_previous_bal == 1) {
                $all_due = $this->getContactDue($transaction->contact_id);
                if (!empty($all_due)) {
                    $output['all_bal_label'] = $il->prev_bal_label;
                    $output['all_due'] = $this->num_f($all_due, $show_currency, $business_details);
                }
            }
            if ($paid_amount > $final_total_for_calculation) {
                $output['paid_greater'] = true;
                $output['due_amount'] = $this->num_f($due, $show_currency, $business_details);
            } else {
                $output['paid_greater'] = false;
                $output['due_amount'] = $this->num_f($due, $show_currency, $business_details);
            }
            //Get payment details
            $output['payments'] = [];
            if ($il->show_payments == 1) {
                $payments = $transaction->payment_lines->toArray();
                $payment_types = $this->payment_types();
                if (!empty($payments)) {
                    foreach ($payments as $value) {
                        $method = !empty($payment_types[$value['method']]) ? $payment_types[$value['method']] : '';
                        if ($value['method'] == 'cash') {
                            $output['payments'][] =
                                [
                                    'method' => $method . ($value['is_return'] == 1 ? ' (' . $il->change_return_label . ')(-)' : ''),
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                            if ($value['is_return'] == 1) {
                            }
                        } elseif ($value['method'] == 'card') {
                            $output['payments'][] =
                                [
                                    'method' => $method . (!empty($value['card_transaction_number']) ? (', Transaction Number:' . $value['card_transaction_number']) : ''),
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        } elseif ($value['method'] == 'cheque') {
                            $cheque_meta = '';
                            if (!empty($value['bank_name'])) {
                                $cheque_meta .= ', Bank Name:' . $value['bank_name'];
                            }
                            if (!empty($value['cheque_number'])) {
                                $cheque_meta .= ', Cheque Number:' . $value['cheque_number'];
                            }
                            if (!empty($value['cheque_date'])) {
                                $cheque_meta .= ', Cheque Date:' . $this->format_date($value['cheque_date'], false, $business_details);
                            }
                            $output['payments'][] =
                                [
                                    'method' => $method . $cheque_meta,
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        } elseif (in_array($value['method'], ['bank_transfer', 'direct_bank_deposit', 'bank'])) {
                            $bank_meta = '';
                            if (!empty($value['bank_name'])) {
                                $bank_meta .= ', Bank Name:' . $value['bank_name'];
                            }
                            if (!empty($value['bank_account_number'])) {
                                $bank_meta .= ', Account Number:' . $value['bank_account_number'];
                            }
                            if (!empty($value['cheque_number'])) {
                                $bank_meta .= ', Cheque Number:' . $value['cheque_number'];
                            }
                            if (!empty($value['cheque_date'])) {
                                $bank_meta .= ', Cheque Date:' . $this->format_date($value['cheque_date'], false, $business_details);
                            }
                            $output['payments'][] =
                                [
                                    'method' => $method . $bank_meta,
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        } elseif ($value['method'] == 'other') {
                            $output['payments'][] =
                                [
                                    'method' => $method,
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        } elseif ($value['method'] == 'custom_pay_1') {
                            $output['payments'][] =
                                [
                                    'method' => $method . (!empty($value['transaction_no']) ? (', ' . trans("lang_v1.transaction_no") . ':' . $value['transaction_no']) : ''),
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        } elseif ($value['method'] == 'custom_pay_2') {
                            $output['payments'][] =
                                [
                                    'method' => $method . (!empty($value['transaction_no']) ? (', ' . trans("lang_v1.transaction_no") . ':' . $value['transaction_no']) : ''),
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        } elseif ($value['method'] == 'custom_pay_3') {
                            $output['payments'][] =
                                [
                                    'method' => $method . (!empty($value['transaction_no']) ? (', ' . trans("lang_v1.transaction_no") . ':' . $value['transaction_no']) : ''),
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        } else {
                            // Default case for all other payment methods
                            $output['payments'][] =
                                [
                                    'method' => $method,
                                    'amount' => $this->num_f($value['amount'], $show_currency, $business_details),
                                    'date' => $this->format_date($value['paid_on'], false, $business_details)
                                ];
                        }
                    }
                }
            }
        }
        //Check for barcode
        $output['barcode'] = ($il->show_barcode == 1) ? $transaction->invoice_no : false;
        //Additional notes
        $output['additional_notes'] = $transaction->additional_notes;
        $output['footer_text'] = $invoice_layout->footer_text;
        //Barcode related information.
        $output['show_barcode'] = !empty($il->show_barcode) ? true : false;
        //Module related information.
        $il->module_info = !empty($il->module_info) ? json_decode($il->module_info, true) : [];
        if (!empty($il->module_info['tables']) && $this->isModuleEnabled('tables')) {
            //Table label & info
            $output['table_label'] = null;
            $output['table'] = null;
            if (isset($il->module_info['tables']['show_table'])) {
                $output['table_label'] = !empty($il->module_info['tables']['table_label']) ? $il->module_info['tables']['table_label'] : '';
                if (!empty($transaction->res_table_id)) {
                    $table = ResTable::find($transaction->res_table_id);
                }
                //res_table_id
                $output['table'] = !empty($table->name) ? $table->name : '';
            }
        }
        if (!empty($il->module_info['types_of_service']) && $this->isModuleEnabled('types_of_service') && !empty($transaction->types_of_service_id)) {
            //Table label & info
            $output['types_of_service_label'] = null;
            $output['types_of_service'] = null;
            if (isset($il->module_info['types_of_service']['show_types_of_service'])) {
                $output['types_of_service_label'] = !empty($il->module_info['types_of_service']['types_of_service_label']) ? $il->module_info['types_of_service']['types_of_service_label'] : '';
                $output['types_of_service'] = $transaction->types_of_service->name;
            }
            if (isset($il->module_info['types_of_service']['show_tos_custom_fields'])) {
                $output['types_of_service_custom_fields'] = [];
                if (!empty($transaction->service_custom_field_1)) {
                    $output['types_of_service_custom_fields'][__('lang_v1.service_custom_field_1')] = $transaction->service_custom_field_1;
                }
                if (!empty($transaction->service_custom_field_2)) {
                    $output['types_of_service_custom_fields'][__('lang_v1.service_custom_field_2')] = $transaction->service_custom_field_2;
                }
                if (!empty($transaction->service_custom_field_3)) {
                    $output['types_of_service_custom_fields'][__('lang_v1.service_custom_field_3')] = $transaction->service_custom_field_3;
                }
                if (!empty($transaction->service_custom_field_4)) {
                    $output['types_of_service_custom_fields'][__('lang_v1.service_custom_field_4')] = $transaction->service_custom_field_4;
                }
            }
        }
        if (!empty($il->module_info['service_staff']) && $this->isModuleEnabled('service_staff')) {
            //Waiter label & info
            $output['service_staff_label'] = null;
            $output['service_staff'] = null;
            if (isset($il->module_info['service_staff']['show_service_staff'])) {
                $output['service_staff_label'] = !empty($il->module_info['service_staff']['service_staff_label']) ? $il->module_info['service_staff']['service_staff_label'] : '';
                if (!empty($transaction->res_waiter_id)) {
                    $waiter = \App\User::find($transaction->res_waiter_id);
                }
                //res_table_id
                $output['service_staff'] = !empty($waiter->id) ? implode(' ', [$waiter->first_name, $waiter->last_name]) : '';
            }
        }
        //Repair module details
        if (!empty($il->module_info['repair']) && $transaction->sub_type == 'repair') {
            if (!empty($il->module_info['repair']['show_repair_status'])) {
                $output['repair_status_label'] = $il->module_info['repair']['repair_status_label'];
                $output['repair_status'] = '';
                if (!empty($transaction->repair_status_id)) {
                    $repair_status = \Modules\Repair\Entities\RepairStatus::find($transaction->repair_status_id);
                    $output['repair_status'] = $repair_status->name;
                }
            }
            if (!empty($il->module_info['repair']['show_repair_warranty'])) {
                $output['repair_warranty_label'] = $il->module_info['repair']['repair_warranty_label'];
                $output['repair_warranty'] = '';
                if (!empty($transaction->repair_warranty_id)) {
                    $repair_warranty = \Modules\Repair\Entities\Warranty::find($transaction->repair_warranty_id);
                    $output['repair_warranty'] = $repair_warranty->name;
                }
            }
            if (!empty($il->module_info['repair']['show_serial_no'])) {
                $output['serial_no_label'] = $il->module_info['repair']['serial_no_label'];
                $output['repair_serial_no'] = $transaction->repair_serial_no;
            }
            if (!empty($il->module_info['repair']['show_defects'])) {
                $output['defects_label'] = $il->module_info['repair']['defects_label'];
                $output['repair_defects'] = $transaction->repair_defects;
            }
        }
        $output['design'] = $il->design;
        $output['table_tax_headings'] = !empty($il->table_tax_headings) ? array_filter(json_decode($il->table_tax_headings), 'strlen') : null;
        return (object) $output;
    }

    public function getOtherSaleReceiptDetails($print_other_sale_ids, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type)
    {
        $il = $invoice_layout;
        $rep = $print_other_sale_ids[0];

        $transaction_type = "sell";
        $footer_top_margin = System::getProperty('footer_top_margin');
        $admin_invoice_footer = System::getProperty('admin_invoice_footer');
        $output = [
            'header_text' => isset($il->header_text) ? $il->header_text : '',
            'business_name' => ($il->show_business_name == 1) ? $business_details->name : '',
            'location_name' => ($il->show_location_name == 1) ? $location_details->name : '',
            'sub_heading_line1' => trim($il->sub_heading_line1),
            'sub_heading_line2' => trim($il->sub_heading_line2),
            'sub_heading_line3' => trim($il->sub_heading_line3),
            'sub_heading_line4' => trim($il->sub_heading_line4),
            'sub_heading_line5' => trim($il->sub_heading_line5),
            'table_product_label' => $il->table_product_label,
            'table_qty_label' => $il->table_qty_label,
            'table_unit_price_label' => $il->table_unit_price_label,
            'table_subtotal_label' => $il->table_subtotal_label,
            'font_size' => $il->font_size,
            'header_font_size' => $il->header_font_size,
            'footer_font_size' => $il->footer_font_size,
            'business_name_font_size' => $il->business_name_font_size,
            'invoice_heading_font_size' => $il->invoice_heading_font_size,
            'footer_top_margin' => $footer_top_margin,
            'admin_invoice_footer' => $admin_invoice_footer,
            'logo_height' => $il->logo_height,
            'logo_width' => $il->logo_width,
            'logo_margin_top' => $il->logo_margin_top,
            'logo_margin_bottom' => $il->logo_margin_bottom,
            'header_align' => $il->header_align,
            'reprint' => $rep,
            'tax_amount' => "..."
        ];
        //Display name
        $output['display_name'] = $output['business_name'];
        if (!empty($output['location_name'])) {
            if (!empty($output['display_name'])) {
                $output['display_name'] .= ', ';
            }
            $output['display_name'] .= $output['location_name'];
        }
        $contact_details = ['due_amount' => 0, 'customer_name' => 'Customer', 'sol_with_approval' => false];
        $output['contact_details'] = $contact_details;
        //Logo
        $output['logo'] = $il->show_logo != 0 && !empty($il->logo) && file_exists(public_path('uploads/invoice_logos/' . $il->logo)) ? asset('uploads/invoice_logos/' . $il->logo) : false;
        //Address
        $output['address'] = '';
        $temp = [];
        if ($il->show_landmark == 1) {
            $output['address'] .= $location_details->landmark . "\n";
        }
        if ($il->show_city == 1 && !empty($location_details->city)) {
            $temp[] = $location_details->city;
        }
        if ($il->show_state == 1 && !empty($location_details->state)) {
            $temp[] = $location_details->state;
        }
        if ($il->show_zip_code == 1 && !empty($location_details->zip_code)) {
            $temp[] = $location_details->zip_code;
        }
        if ($il->show_country == 1 && !empty($location_details->country)) {
            $temp[] = $location_details->country;
        }
        if (!empty($temp)) {
            $output['address'] .= implode(',', $temp);
        }
        $output['website'] = $location_details->website;
        $output['location_custom_fields'] = '';
        $temp = [];
        $location_custom_field_settings = !empty($il->location_custom_fields) ? $il->location_custom_fields : [];
        if (!empty($location_details->custom_field1) && in_array('custom_field1', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field1;
        }
        if (!empty($location_details->custom_field2) && in_array('custom_field2', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field2;
        }
        if (!empty($location_details->custom_field3) && in_array('custom_field3', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field3;
        }
        if (!empty($location_details->custom_field4) && in_array('custom_field4', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field4;
        }
        if (!empty($temp)) {
            $output['location_custom_fields'] .= implode(', ', $temp);
        }
        //Tax Info
        if ($il->show_tax_1 == 1 && !empty($business_details->tax_number_1)) {
            $output['tax_label1'] = !empty($business_details->tax_label_1) ? $business_details->tax_label_1 . ': ' : '';
            $output['tax_info1'] = $business_details->tax_number_1;
        }
        if ($il->show_tax_2 == 1 && !empty($business_details->tax_number_2)) {
            if (!empty($output['tax_info1'])) {
                $output['tax_info1'] .= ', ';
            }
            $output['tax_label2'] = !empty($business_details->tax_label_2) ? $business_details->tax_label_2 . ': ' : '';
            $output['tax_info2'] = $business_details->tax_number_2;
        }
        //Shop Contact Info
        $output['contact'] = '';
        if ($il->show_mobile_number == 1 && !empty($location_details->mobile)) {
            $output['contact'] .= __('contact.mobile') . ': ' . $location_details->mobile;
        }
        if ($il->show_alternate_number == 1 && !empty($location_details->alternate_number)) {
            if (empty($output['contact'])) {
                $output['contact'] .= __('contact.mobile') . ': ' . $location_details->alternate_number;
            } else {
                $output['contact'] .= ', ' . $location_details->alternate_number;
            }
        }
        if ($il->show_email == 1 && !empty($location_details->email)) {
            if (!empty($output['contact'])) {
                // $output['contact'] .= "\n";
            }
            $output['contact'] .= __('business.email') . ': ' . $location_details->email;
        }
        //Customer show_customer
        $business_id = request()->session()->get('business.id') ?: Auth::user()->business_id;
        $customer = Contact::where('business_id', $business_id)->where('active', 1)->where('name', "Walk-In Customer")->orderBy('id', 'asc')->first();
        $output['customer_info'] = '';
        $output['customer_tax_number'] = '';
        $output['customer_tax_label'] = '';
        $output['customer_custom_fields'] = '';
        if ($il->show_customer == 1) {
            $output['customer_label'] = !empty($il->customer_label) ? $il->customer_label : '';
            $output['customer_name'] = !empty($customer->name) ? $customer->name : '';
            if (!empty($output['customer_name']) && $receipt_printer_type != 'printer') {
                $output['customer_info'] .= $customer->landmark;
                // $output['customer_info'] .= '<br>' . implode(',', array_filter([$customer->city, $customer->state, $customer->country]));
                $output['customer_info'] .= '<br>' . $customer->mobile;
            }
            $output['customer_tax_number'] = !empty($customer->tax_number) ? $customer->tax_number : null;
            $output['customer_tax_label'] = !empty($il->client_tax_label) ? $il->client_tax_label : '';
            $temp = [];
            $customer_custom_fields_settings = !empty($il->contact_custom_fields) ? $il->contact_custom_fields : [];
            if (!empty($customer->custom_field1) && in_array('custom_field1', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field1;
            }
            if (!empty($customer->custom_field2) && in_array('custom_field2', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field2;
            }
            if (!empty($customer->custom_field3) && in_array('custom_field3', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field3;
            }
            if (!empty($customer->custom_field4) && in_array('custom_field4', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field4;
            }
            if (!empty($temp)) {
                $output['customer_custom_fields'] .= implode(',', $temp);
            }
        }
        if ($il->show_reward_point == 1) {
            $output['customer_rp_label'] = $business_details->rp_name;
            $output['customer_total_rp'] = $customer->total_rp;
        }
        $output['client_id'] = '';
        $output['client_id_label'] = '';
        if ($il->show_client_id == 1) {
            $output['client_id_label'] = !empty($il->client_id_label) ? $il->client_id_label : '';
            $output['client_id'] = !empty($customer->contact_id) ? $customer->contact_id : '';
        }
        //Sales person info
        $output['sales_person'] = '';
        $output['sales_person_label'] = '';
        $pump_operator_id = Auth::user()->pump_operator_id;
        // $pump_operator = PumpOperator::findOrFail($pump_operator_id);

        $pump_operator = null;
        $operator_name = 'Staff'; // Default fallback name

        if ($pump_operator_id) {
            $pump_operator = PumpOperator::find($pump_operator_id);
            if ($pump_operator) {
                $operator_name = $pump_operator->name ?? 'Staff';
            }
        } else {
            // Fallback to current user's name if no pump operator
            $current_user = Auth::user();
            $operator_name = $current_user->username ?? $current_user->first_name ?? 'Staff';
        }

        if ($il->show_sales_person == 1) {
            $output['sales_person_label'] = !empty($il->sales_person_label) ? $il->sales_person_label : '';
            $output['sales_person'] = $operator_name;
        }
        // if ($il->show_sales_person == 1) {
        //     $output['sales_person_label'] = !empty($il->sales_person_label) ? $il->sales_person_label : '';
        //     $output['sales_person'] = !empty($pump_operator->name) ? $pump_operator->name : '';
        // }
        //Invoice info
        $output['invoice_no'] = "00" . $rep;
        $output['quotation_no'] = "";
        $output['shipping_address'] = "...";
        //Heading & invoice label, when quotation use the quotation heading.
        $output['invoice_no_prefix'] = $il->invoice_no_prefix;
        $output['invoice_heading'] = $il->invoice_heading;
        $output['invoice_heading'] .= ' ' . $il->invoice_heading_paid;

        $output['date_label'] = $il->date_label;
        if (blank($il->date_time_format)) {
            $output['invoice_date'] = $this->format_date(date('Y-m-d H:i:s', time()), true, $business_details);
        } else {
            $output['invoice_date'] = \Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()))->format($il->date_time_format);
        }
        \Log::debug("other sale receipt details", [
            "date" => date('Y-m-d H:i:s', time()),
            "invoice_date" => $output['invoice_date'],
        ]);
        if (!empty($il->common_settings['show_due_date'])) {
            $output['due_date_label'] = !empty($il->common_settings['due_date_label']) ? $il->common_settings['due_date_label'] : '';
            $due_date = date('Y-m-d H:i:s', time());
            if (!empty($due_date)) {
                if (blank($il->date_time_format)) {
                    $output['due_date'] = $this->format_date($due_date->toDateTimeString(), true, $business_details);
                } else {
                    $output['due_date'] = \Carbon::createFromFormat('Y-m-d H:i:s', $due_date->toDateTimeString())->format($il->date_time_format);
                }
            }
        }
        $show_currency = true;
        if ($receipt_printer_type == 'printer' && trim($business_details->currency_symbol) != '$') {
            $show_currency = false;
        }
        //Invoice product lines
        $is_lot_number_enabled = $business_details->enable_lot_number;
        $is_product_expiry_enabled = $business_details->enable_product_expiry;
        $output['lines'] = [];
        $sub_total = 0;
        if ($transaction_type == 'sell') {
            $sell_line_relations = ['modifiers', 'sub_unit', 'warranties'];
            if ($is_lot_number_enabled == 1) {
                $sell_line_relations[] = 'lot_details';
            }
            $lines = PumpOperatorOtherSale::whereIn('id', $print_other_sale_ids)->get();
            foreach ($lines as $line) {
                $line->product = Product::where('id', $line->product_id)->first();
                $line->variations = Variation::where('id', $line->product_id)->first();
                $line->variations->product_variation = ProductVariation::where('product_id', $line->product_id)->first();
                $line->product->unit = Unit::where('id', $line->product->unit_id)->first();
                $line->product->brand = Brands::where('id', $line->product->brand_id)->first();
                $line->product->category = Category::where('id', $line->product->category_id)->first();
                $sub_total = $sub_total + $line->sub_total;
            }
            $details = $this->_receiptOtherSaleDetailsSellLines($lines, $il, $business_details);

            $output['lines'] = $details['lines'];
            $output['taxes'] = [];
            foreach ($details['lines'] as $line) {
                if (!empty($line['group_tax_details'])) {
                    foreach ($line['group_tax_details'] as $tax_group_detail) {
                        if (!isset($output['taxes'][$tax_group_detail['name']])) {
                            $output['taxes'][$tax_group_detail['name']] = 0;
                        }
                        $output['taxes'][$tax_group_detail['name']] += $tax_group_detail['calculated_tax'];
                    }
                } elseif (!empty($line['tax_unformatted']) && $line['tax_unformatted'] != 0) {
                    if (!isset($output['taxes'][$line['tax_name']])) {
                        $output['taxes'][$line['tax_name']] = 0;
                    }
                    $output['taxes'][$line['tax_name']] += $line['tax_unformatted'];
                }
            }
        }



        //show cat code
        $output['show_cat_code'] = $il->show_cat_code;
        $output['cat_code_label'] = $il->cat_code_label;
        //Subtotal
        $output['subtotal_label'] = $il->sub_total_label . ':';
        $output['subtotal'] = $this->num_f($sub_total, $show_currency, $business_details);

        $subtotal_final = array_sum(
            array_map(function ($value) {
                return isset($value) ? (float) str_replace(',', '', $value) : 0;
            }, array_column($details['lines'], 'sub_total_final'))
        );

        $output['subtotal_final'] = $this->num_f($subtotal_final, false, $business_details);

        $output['subtotal_unformatted'] = $sub_total;
        //Discount
        $output['line_discount_label'] = $invoice_layout->discount_label;
        $output['discount_label'] = $invoice_layout->discount_label;
        $output['discount_label'] .= '';
        $discount = 0;
        $output['discount'] = ($discount != 0) ? $this->num_f($discount, $show_currency, $business_details) : 0;
        //Format tax
        if (!empty($output['taxes'])) {
            foreach ($output['taxes'] as $key => $value) {
                $output['taxes'][$key] = $this->num_f($value, $show_currency, $business_details);
            }
        }
        //Order Tax
        $tax = null;
        $output['tax_label'] = $invoice_layout->tax_label;
        $output['line_tax_label'] = $invoice_layout->tax_label;
        if (!empty($tax) && !empty($tax->name)) {
            $output['tax_label'] .= ' (' . $tax->name . ')';
        }
        $output['tax_label'] .= ':';
        $output['tax'] = 0;
        //Shipping charges
        $output['shipping_charges'] = 0;
        $output['shipping_charges_label'] = trans("sale.shipping_charges");
        //Shipping details
        $output['shipping_details'] = "...";
        $output['shipping_details_label'] = trans("sale.shipping_details");
        //Total
        $output['total_label'] = $invoice_layout->total_label . ':';
        $output['total'] = $this->num_f($sub_total, $show_currency, $business_details);
        //Paid & Amount due, only if final
        $output['total_paid_label'] = $il->paid_label;
        $output['total_due_label'] = $il->total_due_label;

        if ($transaction_type == 'sell') {
            $paid_amount = $sub_total;
            $due = 0;
            $output['total_paid'] = ($paid_amount == 0) ? 0 : $this->num_f($paid_amount, $show_currency, $business_details);
            $output['total_due'] = ($due == 0) ? 0 : $this->num_f($due, $show_currency, $business_details);

            if ($il->show_previous_bal == 1) {
                $all_due = $this->getContactDue(0);
                if (!empty($all_due)) {
                    $output['all_bal_label'] = $il->prev_bal_label;
                    $output['all_due'] = $this->num_f($all_due, $show_currency, $business_details);
                }
            }
            if ($paid_amount > $sub_total) {
                $output['paid_greater'] = true;
                $output['due_amount'] = $this->num_f($due, $show_currency, $business_details);
            } else {
                $output['paid_greater'] = false;
                $output['due_amount'] = $this->num_f($due, $show_currency, $business_details);
            }
            //Get payment details
            $output['payments'] = [];
        }
        //Check for barcode
        $output['barcode'] = ($il->show_barcode == 1) ? "00" . $rep : false;
        //Additional notes
        $output['additional_notes'] = "...";
        $output['footer_text'] = $invoice_layout->footer_text;
        //Barcode related information.
        $output['show_barcode'] = !empty($il->show_barcode) ? true : false;
        $output['design'] = $il->design;
        $output['table_tax_headings'] = !empty($il->table_tax_headings) ? array_filter(json_decode($il->table_tax_headings), 'strlen') : null;
        return (object) $output;
    }

    public function getCreditSaleReceiptDetails($daily_voucher_item_ids, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type)
    {
        $il = $invoice_layout;
        $rep = $daily_voucher_item_ids[0];

        $transaction_type = "sell";
        $footer_top_margin = System::getProperty('footer_top_margin');
        $admin_invoice_footer = System::getProperty('admin_invoice_footer');
        $output = [
            'header_text' => isset($il->header_text) ? $il->header_text : '',
            'business_name' => ($il->show_business_name == 1) ? $business_details->name : '',
            'location_name' => ($il->show_location_name == 1) ? $location_details->name : '',
            'sub_heading_line1' => trim($il->sub_heading_line1),
            'sub_heading_line2' => trim($il->sub_heading_line2),
            'sub_heading_line3' => trim($il->sub_heading_line3),
            'sub_heading_line4' => trim($il->sub_heading_line4),
            'sub_heading_line5' => trim($il->sub_heading_line5),
            'table_product_label' => $il->table_product_label,
            'table_qty_label' => $il->table_qty_label,
            'table_unit_price_label' => $il->table_unit_price_label,
            'table_subtotal_label' => $il->table_subtotal_label,
            'font_size' => $il->font_size,
            'header_font_size' => $il->header_font_size,
            'footer_font_size' => $il->footer_font_size,
            'business_name_font_size' => $il->business_name_font_size,
            'invoice_heading_font_size' => $il->invoice_heading_font_size,
            'footer_top_margin' => $footer_top_margin,
            'admin_invoice_footer' => $admin_invoice_footer,
            'logo_height' => $il->logo_height,
            'logo_width' => $il->logo_width,
            'logo_margin_top' => $il->logo_margin_top,
            'logo_margin_bottom' => $il->logo_margin_bottom,
            'header_align' => $il->header_align,
            'reprint' => $rep,
            'tax_amount' => "..."
        ];
        //Display name
        $output['display_name'] = $output['business_name'];
        if (!empty($output['location_name'])) {
            if (!empty($output['display_name'])) {
                $output['display_name'] .= ', ';
            }
            $output['display_name'] .= $output['location_name'];
        }
        $contact_details = ['due_amount' => 0, 'customer_name' => 'Customer', 'sol_with_approval' => false];
        $output['contact_details'] = $contact_details;
        //Logo
        $output['logo'] = $il->show_logo != 0 && !empty($il->logo) && file_exists(public_path('uploads/invoice_logos/' . $il->logo)) ? asset('uploads/invoice_logos/' . $il->logo) : false;
        //Address
        $output['address'] = '';
        $temp = [];
        if ($il->show_landmark == 1) {
            $output['address'] .= $location_details->landmark . "\n";
        }
        if ($il->show_city == 1 && !empty($location_details->city)) {
            $temp[] = $location_details->city;
        }
        if ($il->show_state == 1 && !empty($location_details->state)) {
            $temp[] = $location_details->state;
        }
        if ($il->show_zip_code == 1 && !empty($location_details->zip_code)) {
            $temp[] = $location_details->zip_code;
        }
        if ($il->show_country == 1 && !empty($location_details->country)) {
            $temp[] = $location_details->country;
        }
        if (!empty($temp)) {
            $output['address'] .= implode(',', $temp);
        }
        $output['website'] = $location_details->website;
        $output['location_custom_fields'] = '';
        $temp = [];
        $location_custom_field_settings = !empty($il->location_custom_fields) ? $il->location_custom_fields : [];
        if (!empty($location_details->custom_field1) && in_array('custom_field1', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field1;
        }
        if (!empty($location_details->custom_field2) && in_array('custom_field2', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field2;
        }
        if (!empty($location_details->custom_field3) && in_array('custom_field3', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field3;
        }
        if (!empty($location_details->custom_field4) && in_array('custom_field4', $location_custom_field_settings)) {
            $temp[] = $location_details->custom_field4;
        }
        if (!empty($temp)) {
            $output['location_custom_fields'] .= implode(', ', $temp);
        }
        //Tax Info
        if ($il->show_tax_1 == 1 && !empty($business_details->tax_number_1)) {
            $output['tax_label1'] = !empty($business_details->tax_label_1) ? $business_details->tax_label_1 . ': ' : '';
            $output['tax_info1'] = $business_details->tax_number_1;
        }
        if ($il->show_tax_2 == 1 && !empty($business_details->tax_number_2)) {
            if (!empty($output['tax_info1'])) {
                $output['tax_info1'] .= ', ';
            }
            $output['tax_label2'] = !empty($business_details->tax_label_2) ? $business_details->tax_label_2 . ': ' : '';
            $output['tax_info2'] = $business_details->tax_number_2;
        }
        //Shop Contact Info
        $output['contact'] = '';
        if ($il->show_mobile_number == 1 && !empty($location_details->mobile)) {
            $output['contact'] .= __('contact.mobile') . ': ' . $location_details->mobile;
        }
        if ($il->show_alternate_number == 1 && !empty($location_details->alternate_number)) {
            if (empty($output['contact'])) {
                $output['contact'] .= __('contact.mobile') . ': ' . $location_details->alternate_number;
            } else {
                $output['contact'] .= ', ' . $location_details->alternate_number;
            }
        }
        if ($il->show_email == 1 && !empty($location_details->email)) {
            if (!empty($output['contact'])) {
                // $output['contact'] .= "\n";
            }
            $output['contact'] .= __('business.email') . ': ' . $location_details->email;
        }
        //Customer show_customer
        $daily_voucher_item = DailyVoucherItem::where('id', $rep)->select('daily_voucher_id')->first();
        $daily_voucher = DailyVoucher::where('id', $daily_voucher_item->daily_voucher_id)->select('customer_id')->first();
        $customer = Contact::find($daily_voucher->customer_id);
        $output['customer_info'] = '';
        $output['customer_tax_number'] = '';
        $output['customer_tax_label'] = '';
        $output['customer_custom_fields'] = '';
        if ($il->show_customer == 1) {
            $output['customer_label'] = !empty($il->customer_label) ? $il->customer_label : '';
            $output['customer_name'] = !empty($customer->name) ? $customer->name : '';
            if (!empty($output['customer_name']) && $receipt_printer_type != 'printer') {
                $output['customer_info'] .= $customer->landmark;
                // $output['customer_info'] .= '<br>' . implode(',', array_filter([$customer->city, $customer->state, $customer->country]));
                $output['customer_info'] .= '<br>' . $customer->mobile;
            }
            $output['customer_tax_number'] = !empty($customer->tax_number) ? $customer->tax_number : null;
            $output['customer_tax_label'] = !empty($il->client_tax_label) ? $il->client_tax_label : '';
            $temp = [];
            $customer_custom_fields_settings = !empty($il->contact_custom_fields) ? $il->contact_custom_fields : [];
            if (!empty($customer->custom_field1) && in_array('custom_field1', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field1;
            }
            if (!empty($customer->custom_field2) && in_array('custom_field2', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field2;
            }
            if (!empty($customer->custom_field3) && in_array('custom_field3', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field3;
            }
            if (!empty($customer->custom_field4) && in_array('custom_field4', $customer_custom_fields_settings)) {
                $temp[] = $customer->custom_field4;
            }
            if (!empty($temp)) {
                $output['customer_custom_fields'] .= implode(',', $temp);
            }
        }
        if ($il->show_reward_point == 1) {
            $output['customer_rp_label'] = $business_details->rp_name;
            $output['customer_total_rp'] = $customer->total_rp;
        }
        $output['client_id'] = '';
        $output['client_id_label'] = '';
        if ($il->show_client_id == 1) {
            $output['client_id_label'] = !empty($il->client_id_label) ? $il->client_id_label : '';
            $output['client_id'] = !empty($customer->contact_id) ? $customer->contact_id : '';
        }
        //Sales person info
        $output['sales_person'] = '';
        $output['sales_person_label'] = '';
        $pump_operator_id = Auth::user()->pump_operator_id;
        $pump_operator = PumpOperator::findOrFail($pump_operator_id);
        if ($il->show_sales_person == 1) {
            $output['sales_person_label'] = !empty($il->sales_person_label) ? $il->sales_person_label : '';
            $output['sales_person'] = !empty($pump_operator->name) ? $pump_operator->name : '';
        }
        //Invoice info
        $output['invoice_no'] = "00" . $rep;
        $output['quotation_no'] = "";
        $output['shipping_address'] = "...";
        //Heading & invoice label, when quotation use the quotation heading.
        $output['invoice_no_prefix'] = $il->invoice_no_prefix;
        $output['invoice_heading'] = $il->invoice_heading;
        $output['invoice_heading'] .= ' ' . $il->invoice_heading_paid;

        $output['date_label'] = $il->date_label;
        if (blank($il->date_time_format)) {
            $output['invoice_date'] = $this->format_date(date('Y-m-d H:i:s', time()), true, $business_details);
        } else {
            $output['invoice_date'] = \Carbon::createFromFormat('Y-m-d H:i:s', date('Y-m-d H:i:s', time()))->format($il->date_time_format);
        }
        \Log::debug("other sale receipt details", [
            "date" => date('Y-m-d H:i:s', time()),
            "invoice_date" => $output['invoice_date'],
        ]);
        if (!empty($il->common_settings['show_due_date'])) {
            $output['due_date_label'] = !empty($il->common_settings['due_date_label']) ? $il->common_settings['due_date_label'] : '';
            $due_date = date('Y-m-d H:i:s', time());
            if (!empty($due_date)) {
                if (blank($il->date_time_format)) {
                    $output['due_date'] = $this->format_date($due_date->toDateTimeString(), true, $business_details);
                } else {
                    $output['due_date'] = \Carbon::createFromFormat('Y-m-d H:i:s', $due_date->toDateTimeString())->format($il->date_time_format);
                }
            }
        }
        $show_currency = true;
        if ($receipt_printer_type == 'printer' && trim($business_details->currency_symbol) != '$') {
            $show_currency = false;
        }
        //Invoice product lines
        $is_lot_number_enabled = $business_details->enable_lot_number;
        $is_product_expiry_enabled = $business_details->enable_product_expiry;
        $output['lines'] = [];
        $sub_total = 0;
        if ($transaction_type == 'sell') {
            $sell_line_relations = ['modifiers', 'sub_unit', 'warranties'];
            if ($is_lot_number_enabled == 1) {
                $sell_line_relations[] = 'lot_details';
            }
            $lines = DailyVoucherItem::whereIn('id', $daily_voucher_item_ids)->get();
            foreach ($lines as $line) {
                $line->product = Product::where('id', $line->product_id)->first();
                $line->variations = Variation::where('id', $line->product_id)->first();
                $line->variations->product_variation = ProductVariation::where('product_id', $line->product_id)->first();
                $line->product->unit = Unit::where('id', $line->product->unit_id)->first();
                $line->product->brand = Brands::where('id', $line->product->brand_id)->first();
                $line->product->category = Category::where('id', $line->product->category_id)->first();
                $sub_total = $sub_total + $line->sub_total;
            }
            $details = $this->_receiptOtherSaleDetailsSellLines($lines, $il, $business_details);
            $output['lines'] = $details['lines'];
            $output['taxes'] = [];
            foreach ($details['lines'] as $line) {
                if (!empty($line['group_tax_details'])) {
                    foreach ($line['group_tax_details'] as $tax_group_detail) {
                        if (!isset($output['taxes'][$tax_group_detail['name']])) {
                            $output['taxes'][$tax_group_detail['name']] = 0;
                        }
                        $output['taxes'][$tax_group_detail['name']] += $tax_group_detail['calculated_tax'];
                    }
                } elseif (!empty($line['tax_unformatted']) && $line['tax_unformatted'] != 0) {
                    if (!isset($output['taxes'][$line['tax_name']])) {
                        $output['taxes'][$line['tax_name']] = 0;
                    }
                    $output['taxes'][$line['tax_name']] += $line['tax_unformatted'];
                }
            }
        }

        //show cat code
        $output['show_cat_code'] = $il->show_cat_code;
        $output['cat_code_label'] = $il->cat_code_label;
        //Subtotal
        $output['subtotal_label'] = $il->sub_total_label . ':';
        $output['subtotal'] = $this->num_f($sub_total, $show_currency, $business_details);

        $subtotal_final = array_sum(
            array_map(function ($value) {
                return isset($value) ? (float) str_replace(',', '', $value) : 0;
            }, array_column($details['lines'], 'sub_total_final'))
        );

        $output['subtotal_final'] = $this->num_f($subtotal_final, false, $business_details);

        $output['subtotal_unformatted'] = $sub_total;
        //Discount
        $output['line_discount_label'] = $invoice_layout->discount_label;
        $output['discount_label'] = $invoice_layout->discount_label;
        $output['discount_label'] .= '';
        $discount = 0;
        $output['discount'] = ($discount != 0) ? $this->num_f($discount, $show_currency, $business_details) : 0;
        //Format tax
        if (!empty($output['taxes'])) {
            foreach ($output['taxes'] as $key => $value) {
                $output['taxes'][$key] = $this->num_f($value, $show_currency, $business_details);
            }
        }
        //Order Tax
        $tax = null;
        $output['tax_label'] = $invoice_layout->tax_label;
        $output['line_tax_label'] = $invoice_layout->tax_label;
        if (!empty($tax) && !empty($tax->name)) {
            $output['tax_label'] .= ' (' . $tax->name . ')';
        }
        $output['tax_label'] .= ':';
        $output['tax'] = 0;
        //Shipping charges
        $output['shipping_charges'] = 0;
        $output['shipping_charges_label'] = trans("sale.shipping_charges");
        //Shipping details
        $output['shipping_details'] = "...";
        $output['shipping_details_label'] = trans("sale.shipping_details");
        //Total
        $output['total_label'] = $invoice_layout->total_label . ':';
        $output['total'] = $this->num_f($sub_total, $show_currency, $business_details);
        //Paid & Amount due, only if final
        $output['total_paid_label'] = $il->paid_label;
        $output['total_due_label'] = $il->total_due_label;

        if ($transaction_type == 'sell') {
            $paid_amount = $sub_total;
            $due = 0;
            $output['total_paid'] = ($paid_amount == 0) ? 0 : $this->num_f($paid_amount, $show_currency, $business_details);
            $output['total_due'] = ($due == 0) ? 0 : $this->num_f($due, $show_currency, $business_details);

            if ($il->show_previous_bal == 1) {
                $all_due = $this->getContactDue(0);
                if (!empty($all_due)) {
                    $output['all_bal_label'] = $il->prev_bal_label;
                    $output['all_due'] = $this->num_f($all_due, $show_currency, $business_details);
                }
            }
            if ($paid_amount > $sub_total) {
                $output['paid_greater'] = true;
                $output['due_amount'] = $this->num_f($due, $show_currency, $business_details);
            } else {
                $output['paid_greater'] = false;
                $output['due_amount'] = $this->num_f($due, $show_currency, $business_details);
            }
            //Get payment details
            $output['payments'] = [];
        }
        //Check for barcode
        $output['barcode'] = ($il->show_barcode == 1) ? "00" . $rep : false;
        //Additional notes
        $output['additional_notes'] = "...";
        $output['footer_text'] = $invoice_layout->footer_text;
        //Barcode related information.
        $output['show_barcode'] = !empty($il->show_barcode) ? true : false;
        $output['design'] = $il->design;
        $output['table_tax_headings'] = !empty($il->table_tax_headings) ? array_filter(json_decode($il->table_tax_headings), 'strlen') : null;
        return (object) $output;
    }

    public function getCustomerDetails($contact_id)
    {
        // Normalize and validate provided contact id to prevent undefined variable usage
        $customer_id = !empty($contact_id) ? $contact_id : null;
        if (empty($customer_id)) {
            return ['due_amount' => 0, 'customer_name' => '', 'sol_with_approval' => ''];
        }
        $business_id = $this->resolveSessionBusinessIdForStock();
        $query = Contact::leftjoin('transactions AS t', 'contacts.id', '=', 't.contact_id')
            ->leftjoin('contact_groups AS cg', 'contacts.customer_group_id', '=', 'cg.id')
            ->where('contacts.business_id', $business_id)
            ->where('contacts.id', $customer_id)
            ->onlyCustomers()
            ->select([
                'contacts.contact_id',
                'contacts.name',
                'contacts.created_at',
                'total_rp',
                'cg.name as customer_group',
                'sol_with_approval',
                'state',
                'country',
                'landmark',
                'mobile',
                'contacts.id',
                'is_default',
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', final_total, 0)) as total_invoice"),
                DB::raw("SUM(IF(t.type = 'sell' AND t.status = 'final', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as invoice_received"),
                DB::raw("SUM(IF(t.type = 'sell_return', final_total, 0)) as total_sell_return"),
                DB::raw("SUM(IF(t.type = 'sell_return', (SELECT SUM(amount) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as sell_return_paid"),
                DB::raw("SUM(IF(t.type = 'opening_balance', final_total, 0)) as opening_balance"),
                DB::raw("SUM(IF(t.type = 'advance_payment', -1*final_total, 0)) as advance_payment"),
                DB::raw("SUM(IF(t.type = 'opening_balance', (SELECT SUM(IF(is_return = 1,-1*amount,amount)) FROM transaction_payments WHERE transaction_payments.transaction_id=t.id), 0)) as opening_balance_paid"),
                'email',
                'tax_number',
                'contacts.pay_term_number',
                'contacts.pay_term_type',
                'contacts.credit_limit',
                'contacts.custom_field1',
                'contacts.custom_field2',
                'contacts.custom_field3',
                'contacts.custom_field4',
                'contacts.type'
            ])
            ->groupBy('contacts.id')->first();


        if (!isset($query)) {
            return ['due_amount' => 0, 'customer_name' => '', 'sol_with_approval' => ''];
        }

        $due = $query->total_invoice - $query->invoice_received + $query->advance_payment;
        $return_due = $query->total_sell_return - $query->sell_return_paid;
        $opening_balance = $query->opening_balance;
        $total_outstanding = $due - $return_due + $opening_balance;
        if (empty($total_outstanding)) {
            $total_outstanding = 0.00;
        }
        $total_outstanding = $this->num_f($total_outstanding, false);
        return ['due_amount' => $total_outstanding, 'customer_name' => $query->name, 'sol_with_approval' => $query->sol_with_approval];
    }
    /**
     * Returns each line details for sell invoice display
     *
     * @return array
     */

    protected function _receiptDetailsSellLines($lines, $il, $business_details)
    {
        $is_lot_number_enabled = $business_details->enable_lot_number;
        $is_product_expiry_enabled = $business_details->enable_product_expiry;
        $output_lines = [];
        //$output_taxes = ['taxes' => []];
        $product_custom_fields_settings = !empty($il->product_custom_fields) ? $il->product_custom_fields : [];
        $is_warranty_enabled = !empty($business_details->common_settings['enable_product_warranty']) ? true : false;

        \Log::info('_receiptDetailsSellLines - Processing ' . count($lines) . ' lines');

        foreach ($lines as $line) {
            // Add null checks and error handling
            if (empty($line)) {
                \Log::warning('_receiptDetailsSellLines - Skipping empty line');
                continue;
            }

            if (empty($line->product)) {
                \Log::error('_receiptDetailsSellLines - Line missing product. Line ID: ' . (!empty($line->id) ? $line->id : 'unknown') . ', Product ID: ' . (!empty($line->product_id) ? $line->product_id : 'unknown'));
                continue;
            }

            $product = $line->product;
            $variation = $line->variations;
            $product_variation = !empty($variation) && !empty($variation->product_variation) ? $variation->product_variation : null;
            $unit = !empty($product->unit) ? $product->unit : null;
            $brand = !empty($product->brand) ? $product->brand : null;
            $cat = !empty($product->category) ? $product->category : null;
            $tax_details = null;
            $tax_rate_for_calc = 0;

            $current_product = Product::find($line->product_id);
            $current_variation = Variation::with('product_variation')->find($line->variation_id);
            $product_has_tax_id = false;

            if (!empty($current_product) && !empty($current_product->tax)) {
                $product_has_tax_id = true;
                $product_tax = TaxRate::find($current_product->tax);
                if (!empty($product_tax) && !empty($product_tax->amount)) {
                    $tax_rate_for_calc = $product_tax->amount;
                    $tax_details = $product_tax;
                }
            }

            if ($tax_rate_for_calc == 0 && !empty($current_variation) && !empty($current_variation->product_variation)) {
                $current_product_variation = $current_variation->product_variation;
                if (!empty($current_product_variation->tax_id)) {
                    $variation_tax = TaxRate::find($current_product_variation->tax_id);
                    if (!empty($variation_tax) && !empty($variation_tax->amount)) {
                        $tax_rate_for_calc = $variation_tax->amount;
                        $tax_details = $variation_tax;
                    }
                }
            }

            if ($tax_rate_for_calc == 0 && !empty($product) && !empty($product->tax)) {
                $product_tax_fallback = TaxRate::find($product->tax);
                if (!empty($product_tax_fallback) && !empty($product_tax_fallback->amount)) {
                    $tax_rate_for_calc = $product_tax_fallback->amount;
                    $tax_details = $product_tax_fallback;
                }
            }

            if ($tax_rate_for_calc == 0 && !empty($line->tax_id)) {
                $tax_details_fallback = TaxRate::find($line->tax_id);
                if (!empty($tax_details_fallback) && !empty($tax_details_fallback->amount)) {
                    $tax_rate_for_calc = $tax_details_fallback->amount;
                    $tax_details = $tax_details_fallback;
                }
            }
            $unit_name = !empty($unit) && !empty($unit->short_name) ? $unit->short_name : '';

            if (!empty($line->sub_unit) && !empty($line->sub_unit->short_name)) {
                $unit_name = $line->sub_unit->short_name;
            }

            $unit_price_before_discount = floatval($line->unit_price_before_discount);
            $unit_price = floatval($line->unit_price); // This is the price excluding tax AFTER discount
            $unit_price_inc_tax_from_db = floatval($line->unit_price_inc_tax);
            $unit_discount = floatval($line->line_discount_amount);
            
            // Debug logging - more detailed
            \Log::info('=== Receipt Line Debug ===');
            \Log::info('Product: ' . (!empty($product->name) ? $product->name : 'Unknown'));
            \Log::info('Quantity: ' . $line->quantity);
            \Log::info('unit_price_before_discount (from DB): ' . $line->unit_price_before_discount);
            \Log::info('unit_price (exc tax, from DB): ' . $line->unit_price);
            \Log::info('unit_price_inc_tax (from DB): ' . $line->unit_price_inc_tax);
            \Log::info('item_tax (from DB): ' . $line->item_tax);
            \Log::info('========================');
            
            if ($line->line_discount_type !== 'fixed') {
                $unit_discount = ($unit_price_before_discount * $unit_discount) / 100;
            }

            $unit_price_after_discount = $unit_price_before_discount - $unit_discount;

            $item_tax = floatval($line->item_tax);

            if (empty($item_tax) || $item_tax == 0) {
                if (!empty($tax_details) && !empty($tax_details->amount)) {
                    $unit_price_exc_tax_calc = $this->calc_percentage_base($unit_price_inc_tax_from_db, $tax_details->amount);
                    $item_tax = $unit_price_inc_tax_from_db - $unit_price_exc_tax_calc;
                } elseif ($unit_price > 0 && $unit_price_inc_tax_from_db > $unit_price && $unit_discount == 0) {
                    $item_tax = $unit_price_inc_tax_from_db - $unit_price;
                }
            }

            if ($tax_rate_for_calc == 0 && !$product_has_tax_id && $item_tax > 0 && $unit_price_inc_tax_from_db > $item_tax) {
                $unit_price_exc_from_tax = $unit_price_inc_tax_from_db - $item_tax;
                if ($unit_price_exc_from_tax > 0) {
                    $calculated_rate = ($item_tax / $unit_price_exc_from_tax) * 100;
                    if ($calculated_rate > 0 && $calculated_rate < 100) {
                        $tax_rate_for_calc = $calculated_rate;
                    }
                }
            }

            // Calculate the correct unit_price_inc_tax
            // The unit_price_inc_tax should be based on the DEFAULT SELLING PRICE, not the wholesaler unit price
            // Get the default selling price from the variation
            $default_sell_price = 0;
            if (!empty($variation) && !empty($variation->default_sell_price)) {
                $default_sell_price = floatval($variation->default_sell_price);
            }
            
            // If we have a default selling price and tax, calculate the including-tax price from that
            if ($default_sell_price > 0 && $item_tax > 0) {
                // Calculate tax rate
                $tax_rate = 0;
                if (!empty($tax_details) && !empty($tax_details->amount)) {
                    $tax_rate = $tax_details->amount;
                } elseif ($tax_rate_for_calc > 0) {
                    $tax_rate = $tax_rate_for_calc;
                }
                
                // Calculate unit_price_inc_tax from default selling price
                if ($tax_rate > 0) {
                    $unit_price_inc_tax = $default_sell_price * (1 + $tax_rate / 100);
                    \Log::info('Using default_sell_price + tax: ' . $unit_price_inc_tax . ' = ' . $default_sell_price . ' * (1 + ' . $tax_rate . '%)');
                } else {
                    // Fallback: use default_sell_price + item_tax
                    $unit_price_inc_tax = $default_sell_price + $item_tax;
                    \Log::info('Using default_sell_price + item_tax: ' . $unit_price_inc_tax . ' = ' . $default_sell_price . ' + ' . $item_tax);
                }
            } elseif ($item_tax > 0) {
                // Fallback to original logic if no default_sell_price
                if ($unit_price > 0) {
                    $unit_price_inc_tax = $unit_price + $item_tax;
                    \Log::info('Using unit_price + item_tax: ' . $unit_price_inc_tax . ' = ' . $unit_price . ' + ' . $item_tax);
                } else {
                    $unit_price_inc_tax = $unit_price_before_discount + $item_tax;
                    \Log::info('Using unit_price_before_discount + item_tax: ' . $unit_price_inc_tax . ' = ' . $unit_price_before_discount . ' + ' . $item_tax);
                }
            } else {
                $unit_price_inc_tax = $unit_price_inc_tax_from_db;
                \Log::info('Using DB unit_price_inc_tax: ' . $unit_price_inc_tax_from_db . ' (item_tax was ' . $item_tax . ')');
            }
            
            // Set unit_price_exc_tax - use default_sell_price if available, otherwise unit_price
            $unit_price_exc_tax = $default_sell_price > 0 ? $default_sell_price : ($unit_price > 0 ? $unit_price : $unit_price_inc_tax - $item_tax);

            // Recalculate the actual tax amount based on the calculated prices
            $actual_item_tax = $unit_price_inc_tax - $unit_price_exc_tax;

            // Calculate line discount amount
            // For percentage: discount is applied on the full line amount.
            // For fixed: stored value is per-unit; convert to a full line amount.
            $line_discount_amount = 0;
            if ($line->line_discount_type == 'percentage') {
                $line_discount_amount = ($unit_price_inc_tax * $line->quantity * $line->line_discount_amount) / 100;
            } else {
                $line_discount_amount = floatval($line->line_discount_amount);
            }
            
            // Calculate line_total: (unit_price_inc_tax * quantity) - discount
            $line_total_before_discount = $unit_price_inc_tax * $line->quantity;
            $line_total_after_discount = $line_total_before_discount - $line_discount_amount;

            \Log::info('FINAL VALUES - unit_price_inc_tax: ' . $unit_price_inc_tax . ', unit_price_exc_tax: ' . $unit_price_exc_tax . ', item_tax: ' . $item_tax . ', line_discount: ' . $line_discount_amount . ', line_total: ' . $line_total_after_discount);

            // Reconstruct the ORIGINAL unit price inc tax from the
            // unit_price_before_discount field, which is NEVER altered
            // by discounts.  This guarantees the "Price inc tax" column
            // on the receipt always shows the undiscounted price.
            $original_unit_price_exc = floatval($line->unit_price_before_discount);
            if ($tax_rate_for_calc > 0) {
                $original_unit_price_inc_tax = $original_unit_price_exc * (1 + $tax_rate_for_calc / 100);
            } elseif ($actual_item_tax > 0 && $unit_price_exc_tax > 0) {
                $original_unit_price_inc_tax = $original_unit_price_exc + ($actual_item_tax);
            } else {
                $original_unit_price_inc_tax = $original_unit_price_exc;
            }

            // Round unit price to avoid line item sum errors
            $original_unit_price_inc_tax = floatval(str_replace(',', '', $this->num_f($original_unit_price_inc_tax, false, $business_details)));


            // Line total = (original price inc tax * qty) - line discount
            $original_line_total = $original_unit_price_inc_tax * $line->quantity;
            $line_total_display = $original_line_total - $line_discount_amount;

            $line_array = [
                'name' => !empty($product->name) ? $product->name : 'Unknown Product',
                'variation' => (!empty($variation) && !empty($variation->name) && $variation->name != 'DUMMY') ? $variation->name : '',
                'product_variation' => (!empty($product_variation) && !empty($product_variation->name) && $product_variation->name != 'DUMMY') ? $product_variation->name : '',
                'quantity' => $this->num_f($line->quantity, false, $business_details, true),
                'units' => $unit_name,
                'unit_price' => $this->num_f($original_unit_price_inc_tax, false, $business_details),
                'tax' => $this->num_f($actual_item_tax, false, $business_details),
                'tax_unformatted' => $actual_item_tax * $line->quantity,
                'tax_name' => !empty($tax_details) ? $tax_details->name : null,
                'tax_percent' => !empty($tax_details) ? $tax_details->amount : null,
                'unit_price_inc_tax' => $this->num_f($original_unit_price_inc_tax, false, $business_details),
                'unit_price_exc_tax' => $this->num_f($original_unit_price_exc, false, $business_details),
                'price_exc_tax' => $line->quantity * $original_unit_price_exc,
                'unit_price_before_discount' => $this->num_f($original_unit_price_inc_tax, false, $business_details),
                'line_total' => $this->num_f($line_total_display, false, $business_details),
                'inital_unit_price' => $this->num_f($original_unit_price_exc, false, $business_details),
                'default_sale_price' => (!empty($variation) && !empty($variation->default_sell_price)) ? $this->num_f($variation->default_sell_price, false, $business_details) : $this->num_f($original_unit_price_exc, false, $business_details),
                'sub_total_final' => $this->num_f($original_unit_price_exc * $line->quantity, false, $business_details)
            ];
            $temp = [];
            if (!empty($product->product_custom_field1) && in_array('product_custom_field1', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field1;
            }
            if (!empty($product->product_custom_field2) && in_array('product_custom_field2', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field2;
            }
            if (!empty($product->product_custom_field3) && in_array('product_custom_field3', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field3;
            }
            if (!empty($product->product_custom_field4) && in_array('product_custom_field4', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field4;
            }
            if (!empty($temp)) {
                $line_array['product_custom_fields'] = implode(',', $temp);
            }
            //Group product taxes by name.
            if (!empty($tax_details)) {
                if ($tax_details->is_tax_group) {
                    $group_tax_details = $this->groupTaxDetails($tax_details, $line->quantity * $line->item_tax);
                    $line_array['group_tax_details'] = $group_tax_details;
                    // foreach ($group_tax_details as $key => $value) {
                    //     if (!isset($output_taxes['taxes'][$key])) {
                    //         $output_taxes['taxes'][$key] = 0;
                    //     }
                    //     $output_taxes['taxes'][$key] += $value;
                    // }
                }
                // else {
                //     $tax_name = $tax_details->name;
                //     if (!isset($output_taxes['taxes'][$tax_name])) {
                //         $output_taxes['taxes'][$tax_name] = 0;
                //     }
                //     $output_taxes['taxes'][$tax_name] += ($line->quantity * $line->item_tax);
                // }
            }
            // $line_array['line_discount'] = method_exists($line, 'get_discount_amount') ? $this->num_uf($line->get_discount_amount()) : 0;
            $line_array['line_discount'] = $this->num_f($line_discount_amount, false, $business_details);
            $line_array['line_discount_percentage'] = '';
            if ($line->line_discount_type == 'percentage') {
                $line_array['line_discount_percentage'] .= ' (' . $this->num_uf($line->line_discount_amount) . '%)';
            }
            if ($il->show_brand == 1) {
                $line_array['brand'] = (!empty($brand) && !empty($brand->name)) ? $brand->name : '';
            }
            if ($il->show_sku == 1) {
                $line_array['sub_sku'] = (!empty($variation) && !empty($variation->sub_sku)) ? $variation->sub_sku : '';
            }
            if ($il->show_image == 1) {
                if (!empty($variation) && !empty($variation->media)) {
                    $media = $variation->media;
                    if (count($media)) {
                        $first_img = $media->first();
                        $line_array['image'] = !empty($first_img->display_url) ? $first_img->display_url : asset('/img/default.png');
                    } else {
                        $line_array['image'] = !empty($product->image_url) ? $product->image_url : asset('/img/default.png');
                    }
                } else {
                    $line_array['image'] = !empty($product->image_url) ? $product->image_url : asset('/img/default.png');
                }
            }
            if ($il->show_cat_code == 1) {
                $line_array['cat_code'] = (!empty($cat) && !empty($cat->short_code)) ? $cat->short_code : '';
            }
            if ($il->show_sale_description == 1) {
                $line_array['sell_line_note'] = !empty($line->sell_line_note) ? $line->sell_line_note : '';
            }
            if ($is_lot_number_enabled == 1 && $il->show_lot == 1) {
                $line_array['lot_number'] = (!empty($line->lot_details) && !empty($line->lot_details->lot_number)) ? $line->lot_details->lot_number : null;
                $line_array['lot_number_label'] = __('lang_v1.lot');
            }
            if ($is_product_expiry_enabled == 1 && $il->show_expiry == 1) {
                $line_array['product_expiry'] = (!empty($line->lot_details) && !empty($line->lot_details->exp_date)) ? $this->format_date($line->lot_details->exp_date, false, $business_details) : null;
                $line_array['product_expiry_label'] = __('lang_v1.expiry');
            }
            //Set warranty data if enabled
            if ($is_warranty_enabled && !empty($line->warranties) && !empty($line->warranties->first())) {
                $warranty = $line->warranties->first();
                if (!empty($il->common_settings['show_warranty_name'])) {
                    $line_array['warranty_name'] = !empty($warranty->name) ? $warranty->name : '';
                }
                if (!empty($il->common_settings['show_warranty_description'])) {
                    $line_array['warranty_description'] = !empty($warranty->description) ? $warranty->description : '';
                }
                if (!empty($il->common_settings['show_warranty_exp_date']) && !empty($line->transaction)) {
                    $line_array['warranty_exp_date'] = $warranty->getEndDate($line->transaction->transaction_date);
                }
            }
            //If modifier is set set modifiers line to parent sell line
            if (!empty($line->modifiers)) {
                foreach ($line->modifiers as $modifier_line) {
                    if (empty($modifier_line) || empty($modifier_line->product)) {
                        continue;
                    }
                    $product = $modifier_line->product;
                    $variation = $modifier_line->variations;
                    $unit = !empty($product->unit) ? $product->unit : null;
                    $brand = !empty($product->brand) ? $product->brand : null;
                    $cat = !empty($product->category) ? $product->category : null;
                    $modifier_line_array = [
                        //Field for 1st column
                        'name' => !empty($product->name) ? $product->name : 'Unknown Product',
                        'variation' => (!empty($variation) && !empty($variation->name) && $variation->name != 'DUMMY') ? $variation->name : '',
                        //Field for 2nd column
                        'quantity' => $this->num_f($modifier_line->quantity, false, $business_details),
                        'units' => (!empty($unit) && !empty($unit->short_name)) ? $unit->short_name : '',
                        //Field for 3rd column
                        'unit_price_inc_tax' => $this->num_f($modifier_line->unit_price_inc_tax, false, $business_details),
                        'unit_price_exc_tax' => $this->num_f($modifier_line->unit_price, false, $business_details),
                        'price_exc_tax' => $modifier_line->quantity * $modifier_line->unit_price,
                        //Fields for 4th column
                        'line_total' => $this->num_f($modifier_line->unit_price_inc_tax * $line->quantity, false, $business_details),
                    ];
                    if ($il->show_sku == 1) {
                        $modifier_line_array['sub_sku'] = (!empty($variation) && !empty($variation->sub_sku)) ? $variation->sub_sku : '';
                    }
                    if ($il->show_cat_code == 1) {
                        $modifier_line_array['cat_code'] = (!empty($cat) && !empty($cat->short_code)) ? $cat->short_code : '';
                    }
                    if ($il->show_sale_description == 1) {
                        $modifier_line_array['sell_line_note'] = !empty($line->sell_line_note) ? $line->sell_line_note : '';
                    }
                    $line_array['modifiers'][] = $modifier_line_array;
                }
            }
            $output_lines[] = $line_array;
        }

        \Log::info('_receiptDetailsSellLines - Generated ' . count($output_lines) . ' output lines');
        return ['lines' => $output_lines];
    }

    protected function _receiptOtherSaleDetailsSellLines($lines, $il, $business_details)
    {
        $is_lot_number_enabled = $business_details->enable_lot_number;
        $is_product_expiry_enabled = $business_details->enable_product_expiry;
        $output_lines = [];
        //$output_taxes = ['taxes' => []];
        $product_custom_fields_settings = !empty($il->product_custom_fields) ? $il->product_custom_fields : [];
        $is_warranty_enabled = !empty($business_details->common_settings['enable_product_warranty']) ? true : false;
        foreach ($lines as $line) {
            $product = $line->product;
            $variation = $line->variations;
            $product_variation = $line->variations->product_variation;
            $unit = $line->product->unit;
            $brand = $line->product->brand;
            $cat = $line->product->category;
            $tax_details = TaxRate::find(0);
            $unit_name = !empty($unit->short_name) ? $unit->short_name : '';

            $line_array = [
                //Field for 1st column
                'name' => $product->name,
                'variation' => (empty($variation->name) || $variation->name == 'DUMMY') ? '' : $variation->name,
                'product_variation' => (empty($product_variation->name) || $product_variation->name == 'DUMMY') ? '' : $product_variation->name,
                //Field for 2nd column
                'quantity' => $this->num_f($line->qty, false, $business_details, true),
                'units' => $unit_name,
                'unit_price' => $this->num_f($line->price ?? $line->unit_price, false, $business_details),
                'tax' => 0,
                'tax_unformatted' => 0,
                'tax_name' => !empty($tax_details) ? $tax_details->name : null,
                'tax_percent' => !empty($tax_details) ? $tax_details->amount : null,
                //Field for 3rd column
                'unit_price_inc_tax' => $this->num_f($line->price ?? $line->unit_price, false, $business_details),
                'unit_price_exc_tax' => $this->num_f($line->price ?? $line->unit_price, false, $business_details),
                'price_exc_tax' => $line->qty * $line->price ?? $line->unit_price,
                'unit_price_before_discount' => $this->num_f($line->price ?? $line->unit_price, false, $business_details),
                //Fields for 4th column
                'line_total' => $this->num_f(($line->price ?? $line->unit_price) * $line->qty, false, $business_details),
                'inital_unit_price' => $this->num_f($line->unit_price_inc_tax + $line->line_discount_amount, false, $business_details),
                'default_sale_price' => $this->num_f($variation->default_sell_price, false, $business_details),
                'sub_total_final' => $this->num_f($line->unit_price, false, $business_details)

            ];

            $temp = [];
            if (!empty($product->product_custom_field1) && in_array('product_custom_field1', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field1;
            }
            if (!empty($product->product_custom_field2) && in_array('product_custom_field2', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field2;
            }
            if (!empty($product->product_custom_field3) && in_array('product_custom_field3', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field3;
            }
            if (!empty($product->product_custom_field4) && in_array('product_custom_field4', $product_custom_fields_settings)) {
                $temp[] = $product->product_custom_field4;
            }
            if (!empty($temp)) {
                $line_array['product_custom_fields'] = implode(',', $temp);
            }
            //Group product taxes by name.
            if (!empty($tax_details)) {
                if ($tax_details->is_tax_group) {
                    $group_tax_details = $this->groupTaxDetails($tax_details, $line->qty * 0);
                    $line_array['group_tax_details'] = $group_tax_details;
                    // foreach ($group_tax_details as $key => $value) {
                    //     if (!isset($output_taxes['taxes'][$key])) {
                    //         $output_taxes['taxes'][$key] = 0;
                    //     }
                    //     $output_taxes['taxes'][$key] += $value;
                    // }
                }
                // else {
                //     $tax_name = $tax_details->name;
                //     if (!isset($output_taxes['taxes'][$tax_name])) {
                //         $output_taxes['taxes'][$tax_name] = 0;
                //     }
                //     $output_taxes['taxes'][$tax_name] += ($line->quantity * $line->item_tax);
                // }
            }
            $line_array['line_discount'] = 0;
            $line_array['line_discount_percentage'] = '';
            if ($il->show_brand == 1) {
                $line_array['brand'] = !empty($brand->name) ? $brand->name : '';
            }
            if ($il->show_sku == 1) {
                $line_array['sub_sku'] = !empty($variation->sub_sku) ? $variation->sub_sku : '';
            }
            if ($il->show_image == 1) {
                $media = $variation->media;
                if (count($media)) {
                    $first_img = $media->first();
                    $line_array['image'] = !empty($first_img->display_url) ? $first_img->display_url : asset('/img/default.png');
                } else {
                    $line_array['image'] = $product->image_url;
                }
            }
            if ($il->show_cat_code == 1) {
                $line_array['cat_code'] = !empty($cat->short_code) ? $cat->short_code : '';
            }
            if ($il->show_sale_description == 1) {
                $line_array['sell_line_note'] = '';
            }
            if ($is_product_expiry_enabled == 1 && $il->show_expiry == 1) {
                $line_array['product_expiry'] = null;
                $line_array['product_expiry_label'] = __('lang_v1.expiry');
            }
            $output_lines[] = $line_array;
        }
        return ['lines' => $output_lines];
    }
    /**
     * Returns each line details for sell return invoice display
     *
     * @return array
     */

    protected function _receiptDetailsSellReturnLines($lines, $il, $business_details)
    {
        $is_lot_number_enabled = $business_details->enable_lot_number;
        $is_product_expiry_enabled = $business_details->enable_product_expiry;
        $output_lines = [];
        $output_taxes = ['taxes' => []];
        foreach ($lines as $line) {
            //Group product taxes by name.
            $tax_details = TaxRate::find($line->tax_id);
            $product = $line->product;
            $variation = $line->variations;
            $unit = $line->product->unit;
            $brand = $line->product->brand;
            $cat = $line->product->category;
            $unit_name = !empty($unit->short_name) ? $unit->short_name : '';
            if (!empty($line->sub_unit->short_name)) {
                $unit_name = $line->sub_unit->short_name;
            }
            $line_array = [
                //Field for 1st column
                'name' => $product->name,
                'variation' => (empty($variation->name) || $variation->name == 'DUMMY') ? '' : $variation->name,
                //Field for 2nd column
                'quantity' => $this->num_f($line->quantity_returned, false, $business_details, true),
                'units' => $unit_name,
                'unit_price' => $this->num_f($line->unit_price_inc_tax, false, $business_details),
                'tax' => $this->num_f($line->item_tax, false, $business_details),
                'tax_name' => !empty($tax_details) ? $tax_details->name : null,
                //Field for 3rd column
                'unit_price_inc_tax' => $this->num_f($line->unit_price_inc_tax, false, $business_details),
                'unit_price_exc_tax' => $this->num_f($line->unit_price, false, $business_details),
                //Fields for 4th column
                'line_total' => $this->num_f($line->unit_price_inc_tax * $line->quantity_returned, false, $business_details),
            ];
            $line_array['line_discount'] = 0;
            //Group product taxes by name.
            if (!empty($tax_details)) {
                if ($tax_details->is_tax_group) {
                    $group_tax_details = $this->groupTaxDetails($tax_details, $line->quantity * $line->item_tax);
                    $line_array['group_tax_details'] = $group_tax_details;
                }
            }
            if ($il->show_brand == 1) {
                $line_array['brand'] = !empty($brand->name) ? $brand->name : '';
            }
            if ($il->show_sku == 1) {
                $line_array['sub_sku'] = !empty($variation->sub_sku) ? $variation->sub_sku : '';
            }
            if ($il->show_cat_code == 1) {
                $line_array['cat_code'] = !empty($cat->short_code) ? $cat->short_code : '';
            }
            if ($il->show_sale_description == 1) {
                $line_array['sell_line_note'] = !empty($line->sell_line_note) ? $line->sell_line_note : '';
            }
            $output_lines[] = $line_array;
        }
        return ['lines' => $output_lines, 'taxes' => $output_taxes];
    }
    /**
     * Gives the invoice number for a Final/Draft invoice
     *
     * @param int $business_id
     * @param string $status
     * @param string $location_id
     *
     * @return string
     */

    public function getInvoiceNumber($business_id, $status, $location_id, $invoice_scheme_id = null)
    {
        if ($status == 'final') {
            if (empty($invoice_scheme_id)) {
                $scheme = $this->getInvoiceScheme($business_id, $location_id);
            } else {
                $scheme = InvoiceScheme::where('business_id', $business_id)
                    ->find($invoice_scheme_id);
            }
            if ($scheme->scheme_type == 'blank') {
                $prefix = $scheme->prefix;
            } else {
                $prefix = date('Y') . '-';
            }
            //Count
            $count = $scheme->start_number + $scheme->invoice_count;
            $count = str_pad($count, $scheme->total_digits, '0', STR_PAD_LEFT);
            //Prefix + count
            $invoice_no = $prefix . $count;
            //Increment the invoice count
            $scheme->invoice_count = $scheme->invoice_count + 1;
            $scheme->save();
            return $invoice_no;
        } else {
            // return str_random(5);
            return \Illuminate\Support\Str::random(5);
        }
    }

    private function getInvoiceScheme($business_id, $location_id)
    {
        $scheme_id = BusinessLocation::where('business_id', $business_id)
            ->where('id', $location_id)
            ->first()
            ->invoice_scheme_id;
        if (!empty($scheme_id) && $scheme_id != 0) {
            $scheme = InvoiceScheme::find($scheme_id);
        }
        //Check if scheme is not found then return default scheme
        if (empty($scheme)) {
            $scheme = InvoiceScheme::where('business_id', $business_id)
                ->where('is_default', 1)
                ->first();
        }
        return $scheme;
    }
    /**
     * Gives the list of products for a purchase transaction
     *
     * @param int $business_id
     * @param int $transaction_id
     *
     * @return array
     */
}
