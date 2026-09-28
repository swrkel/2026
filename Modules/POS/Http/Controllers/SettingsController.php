<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SettingsController extends Controller
{
    protected function businessId(): int
    {
        $user = auth()->user();
        return (int)($user->business_id ?? session('business.id') ?? session('business_id') ?? 0);
    }

    protected function locationId(): int
    {
        return (int)(session('business_location_id') ?? session('location_id') ?? 0);
    }

    protected function ensureTable(): void
    {
        if (!Schema::hasTable('pos_settings')) {
            DB::statement("CREATE TABLE IF NOT EXISTS `pos_settings` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `business_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
                `location_id` BIGINT UNSIGNED NULL,
                `setting_group` VARCHAR(80) NOT NULL,
                `setting_key` VARCHAR(120) NOT NULL,
                `setting_value` TEXT NULL,
                `value_type` VARCHAR(30) NOT NULL DEFAULT 'string',
                `is_active` TINYINT(1) NOT NULL DEFAULT 1,
                `created_by` BIGINT UNSIGNED NULL,
                `updated_by` BIGINT UNSIGNED NULL,
                `created_at` TIMESTAMP NULL DEFAULT NULL,
                `updated_at` TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `pos_settings_unique_key` (`business_id`,`location_id`,`setting_group`,`setting_key`),
                KEY `pos_settings_group_idx` (`business_id`,`setting_group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    protected function defaults(): array
    {
        return [
            'receipt' => [
                'business_name' => 'SYZYGY POS',
                'header_text' => 'Thank you for shopping with us',
                'footer_text' => 'Goods once sold can be returned only as per company policy.',
                'show_logo' => '1',
                'show_cashier' => '1',
                'show_customer' => '1',
                'paper_width' => '80mm',
                'auto_print_after_sale' => '0',
            ],
            'tax_discount' => [
                'default_tax_rate' => '0',
                'allow_line_discount' => '1',
                'allow_bill_discount' => '1',
                'max_discount_percent' => '100',
                'manager_approval_discount_percent' => '25',
                'price_includes_tax' => '0',
            ],
            'terminal' => [
                'default_register_name' => 'Main Register',
                'require_shift_open' => '1',
                'allow_negative_stock' => '0',
                'barcode_quantity_mode' => '1',
                'cash_drawer_enabled' => '1',
                'rounding_mode' => 'none',
            ],
            'security' => [
                'manager_approval_void' => '1',
                'manager_approval_return' => '1',
                'manager_approval_exchange' => '1',
                'manager_approval_price_override' => '1',
                'manager_approval_discount_percent' => '25',
                'lock_sale_after_print' => '0',
                'audit_all_changes' => '1',
                'allow_backdated_sale' => '0',
                'session_timeout_minutes' => '60',
            ],
            'receipt_template' => [
                'template_name' => 'Default Thermal Receipt',
                'receipt_type' => 'thermal',
                'paper_width' => '80mm',
                'logo_position' => 'center',
                'show_qr_code' => '1',
                'show_barcode' => '1',
                'show_tax_summary' => '1',
                'show_terms' => '1',
                'terms_text' => 'Thank you for your business.',
            ],
            'barcode_template' => [
                'template_name' => 'Standard Product Label',
                'barcode_type' => 'CODE128',
                'label_width_mm' => '38',
                'label_height_mm' => '25',
                'show_product_name' => '1',
                'show_price' => '1',
                'show_sku' => '1',
            ],
            'hardware' => [
                'receipt_printer_name' => '',
                'barcode_printer_name' => '',
                'cash_drawer_command' => '',
                'scanner_mode' => 'keyboard',
                'customer_display_enabled' => '0',
            ],
            'number_series' => [
                'sale_prefix' => 'POS',
                'return_prefix' => 'RET',
                'exchange_prefix' => 'EXC',
                'shift_prefix' => 'SHF',
                'next_sale_no' => '1',
                'next_return_no' => '1',
            ],
        ];
    }

    protected function currentSettings(): array
    {
        $this->ensureTable();
        $settings = $this->defaults();
        $rows = DB::table('pos_settings')
            ->where('business_id', $this->businessId())
            ->where(function ($q) {
                $q->whereNull('location_id')->orWhere('location_id', $this->locationId());
            })
            ->where('is_active', 1)
            ->get();

        foreach ($rows as $row) {
            if (!isset($settings[$row->setting_group])) {
                $settings[$row->setting_group] = [];
            }
            $settings[$row->setting_group][$row->setting_key] = (string)$row->setting_value;
        }
        return $settings;
    }

    public function index()
    {
        return view('pos::settings.index', [
            'settings' => $this->currentSettings(),
            'groups' => [
                'receipt' => 'Receipt Settings',
                'receipt_template' => 'Receipt Designer',
                'barcode_template' => 'Barcode & Labels',
                'tax_discount' => 'Tax & Discount Rules',
                'terminal' => 'Terminal / Register Settings',
                'hardware' => 'Hardware & Printer Settings',
                'security' => 'Security & Approval Rules',
                'number_series' => 'Number Series',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTable();
        $payload = $request->except(['_token']);
        $businessId = $this->businessId();
        $locationId = $this->locationId() ?: null;
        $userId = auth()->id();

        foreach ($payload as $group => $values) {
            if (!is_array($values)) {
                continue;
            }
            foreach ($values as $key => $value) {
                DB::table('pos_settings')->updateOrInsert(
                    [
                        'business_id' => $businessId,
                        'location_id' => $locationId,
                        'setting_group' => $group,
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => is_array($value) ? json_encode($value) : (string)$value,
                        'value_type' => is_numeric($value) ? 'number' : 'string',
                        'is_active' => 1,
                        'updated_by' => $userId,
                        'updated_at' => now(),
                        'created_by' => $userId,
                        'created_at' => now(),
                    ]
                );
            }
        }

        return redirect()->route('pos.settings.index')->with('status', 'POS settings saved successfully.');
    }

    public function receiptDesigner()
    {
        return view('pos::settings.receipt_designer', ['settings' => $this->currentSettings()]);
    }

    public function barcodeDesigner()
    {
        return view('pos::settings.barcode_designer', ['settings' => $this->currentSettings()]);
    }

    public function terminalSettings()
    {
        return view('pos::settings.terminal', ['settings' => $this->currentSettings()]);
    }

    public function securitySettings()
    {
        return view('pos::settings.security', ['settings' => $this->currentSettings()]);
    }

    public function hardwareSettings()
    {
        return view('pos::settings.hardware', ['settings' => $this->currentSettings()]);
    }

    public function numberSeries()
    {
        return view('pos::settings.number_series', ['settings' => $this->currentSettings()]);
    }

    public function status()
    {
        return view('pos::dashboard.status');
    }
}
