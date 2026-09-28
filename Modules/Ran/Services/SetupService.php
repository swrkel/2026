<?php

namespace Modules\Ran\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ran\Support\RanContext;

class SetupService
{
    public function ensureBusinessDefaults(?int $businessId = null): void
    {
        $businessId = $businessId ?: RanContext::businessId();
        $now = now();

        foreach (DB::table('ran_metals')->where('business_id', 0)->get() as $row) {
            DB::table('ran_metals')->insertOrIgnore([
                'business_id' => $businessId, 'code' => $row->code, 'name' => $row->name,
                'symbol' => $row->symbol, 'default_density' => $row->default_density,
                'is_active' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $sequenceDefaults = [
            'purchase' => 'PUR-', 'supplier_payment' => 'SPY-', 'stock_lot' => 'LOT-',
            'transfer' => 'TRF-', 'stocktake' => 'STK-', 'production_order' => 'PRO-',
            'material_issue' => 'MIS-', 'production_receipt' => 'PRC-', 'sale' => 'INV-',
            'customer_payment' => 'PAY-', 'sale_return' => 'RET-'
        ];
        foreach ($sequenceDefaults as $type => $prefix) {
            $exists = DB::table('ran_number_sequences')
                ->where('business_id', $businessId)->whereNull('location_id')
                ->where('document_type', $type)->exists();
            if (! $exists) {
                DB::table('ran_number_sequences')->insert([
                    'business_id' => $businessId, 'location_id' => null, 'document_type' => $type,
                    'prefix' => $prefix, 'next_number' => 1, 'padding' => 5, 'reset_period' => 'never',
                    'created_by' => RanContext::userId(), 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        foreach (DB::table('ran_document_templates')->where('business_id', 0)->get() as $template) {
            $existingId = DB::table('ran_document_templates')
                ->where('business_id', $businessId)->where('document_type', $template->document_type)
                ->where('name', $template->name)->value('id');
            if (! $existingId) {
                $existingId = DB::table('ran_document_templates')->insertGetId([
                    'business_id' => $businessId, 'document_type' => $template->document_type,
                    'name' => $template->name, 'paper_size' => $template->paper_size,
                    'orientation' => $template->orientation, 'is_default' => $template->is_default,
                    'style_options' => $template->style_options, 'is_active' => 1,
                    'created_by' => RanContext::userId(), 'created_at' => $now, 'updated_at' => $now,
                ]);
                foreach (DB::table('ran_document_template_sections')->where('template_id', $template->id)->get() as $section) {
                    DB::table('ran_document_template_sections')->insertOrIgnore([
                        'business_id' => $businessId, 'template_id' => $existingId,
                        'section_key' => $section->section_key, 'title' => $section->title,
                        'sort_order' => $section->sort_order, 'enabled_by_default' => $section->enabled_by_default,
                        'allow_print' => $section->allow_print, 'allow_sms' => $section->allow_sms,
                        'allow_email' => $section->allow_email, 'allow_whatsapp' => $section->allow_whatsapp,
                        'settings' => $section->settings, 'created_by' => RanContext::userId(),
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
