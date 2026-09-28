<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PumperDashboardEnterMetersFinalizeTest extends TestCase
{
    /** @test */
    public function close_pumps_dashboard_link_is_always_active(): void
    {
        foreach ([
            'Modules/PumperDashboard/Resources/views/actions/closing_meter.blade.php',
            'Modules/Petro/Resources/views/pump_operators/actions/closing_meter.blade.php',
        ] as $viewPath) {
            $view = file_get_contents(dirname(__DIR__, 2) . '/' . $viewPath);

            $this->assertMatchesRegularExpression(
                '/id="dashboard"(?:(?!disabled-link|disabled).)*>/s',
                $view,
                $viewPath . ' dashboard link must render without disabled state'
            );
            $this->assertStringNotContainsString('$("#dashboard").attr(\'disabled\', true)', $view, $viewPath);
            $this->assertStringNotContainsString('$("#dashboard").addClass(\'disabled-link\')', $view, $viewPath);
        }
    }

    /** @test */
    public function enter_meters_finalize_stays_active_when_new_meter_equals_last_entered_meter(): void
    {
        foreach ([
            'public/modules/petro/js/po_payment.js',
            'public/modules/pumper-dashboard/js/po_payment.js',
            'public/Modules/pumper-dashboard/js/po_payment.js',
        ] as $scriptPath) {
            $this->assertSameMeterFinalizeEnabledFor($scriptPath);
        }
    }

    private function assertSameMeterFinalizeEnabledFor(string $scriptPath): void
    {
        $script = <<<'JS'
const fs = require('fs');
const vm = require('vm');

let finalizeDisabled = null;
const rows = [
    { baseline: 250, newMeter: 270, unitPrice: 295, soldQty: '', amount: '', soldQtyText: '', amountText: '' },
    { baseline: 102, newMeter: 102, unitPrice: 368.75, soldQty: '', amount: '', soldQtyText: '', amountText: '' },
];
const totals = {};

function collection(items) {
    return {
        length: items.length,
        val(value) {
            if (value === undefined) {
                const item = items[0] || {};
                if (item.kind === 'todayDeposited') return '0';
                if (item.kind === 'baseline') return String(item.row.baseline);
                if (item.kind === 'newMeter') return String(item.row.newMeter);
                if (item.kind === 'unitPrice') return String(item.row.unitPrice);
                if (item.kind === 'soldQty') return item.row.soldQty;
                if (item.kind === 'amount') return item.row.amount;
                return '';
            }
            items.forEach(item => {
                if (item.kind === 'soldQty') item.row.soldQty = String(value);
                if (item.kind === 'amount') item.row.amount = String(value);
                if (item.kind === 'total') totals.total = String(value);
                if (item.kind === 'balance') totals.balance = String(value);
            });
            return this;
        },
        text(value) {
            if (value === undefined) return '';
            items.forEach(item => {
                if (item.kind === 'soldQtyText') item.row.soldQtyText = String(value);
                if (item.kind === 'amountText') item.row.amountText = String(value);
                if (item.kind === 'totalText') totals.totalText = String(value);
                if (item.kind === 'balanceText') totals.balanceText = String(value);
            });
            return this;
        },
        find(selector) {
            const row = items[0].row || items[0];
            const map = {
                '.other_sale_qty_baseline': 'baseline',
                '.other_sale_starting_meter': 'baseline',
                '.other_sale_new_meter': 'newMeter',
                '.other_sale_unit_price': 'unitPrice',
                '.other_sale_span_sold_qty': 'soldQtyText',
                '.other_sale_span_amount': 'amountText',
                '.other_sale_sold_qty': 'soldQty',
                '.other_sale_amount': 'amount',
            };
            return collection([{ kind: map[selector], row }]);
        },
        each(callback) {
            items.forEach((item, index) => callback.call(item.row || item, index, item.row || item));
            return this;
        },
        prop(name, value) {
            if (name === 'disabled') finalizeDisabled = value;
            return this;
        },
        on() { return this; },
        focus() { return this; },
        attr() { return this; },
        removeClass() { return this; },
        addClass() { return this; },
        siblings() { return collection([]); },
        find() { return collection([]); },
    };
}

function wrapRow(row) {
    const wrapper = collection([{ row }]);
    wrapper.find = function(selector) {
        const map = {
            '.other_sale_qty_baseline': 'baseline',
            '.other_sale_starting_meter': 'baseline',
            '.other_sale_new_meter': 'newMeter',
            '.other_sale_unit_price': 'unitPrice',
            '.other_sale_span_sold_qty': 'soldQtyText',
            '.other_sale_span_amount': 'amountText',
            '.other_sale_sold_qty': 'soldQty',
            '.other_sale_amount': 'amount',
        };
        return collection([{ kind: map[selector], row }]);
    };
    return wrapper;
}

function $(selector) {
    if (selector && rows.includes(selector)) return wrapRow(selector);
    if (selector === document) return collection([]);
    if (selector === '#other_sale_table tbody tr') return collection(rows);
    if (selector === '.other_sale_grand_today_deposited_input') return collection([{ kind: 'todayDeposited' }]);
    if (selector === '.other_sale_finalize') return collection([{ kind: 'finalize' }]);
    if (selector === '.other_sale_grand_total_amount') return collection([{ kind: 'totalText' }]);
    if (selector === '.other_sale_grand_total_amount_input') return collection([{ kind: 'total' }]);
    if (selector === '.other_sale_grand_balance_to_deposit') return collection([{ kind: 'balanceText' }]);
    if (selector === '.other_sale_grand_balance_to_deposit_input') return collection([{ kind: 'balance' }]);
    return collection([]);
}

global.document = { querySelector() { return null; } };
global.$ = $;
global.__read_number = element => parseFloat(element.val()) || 0;
global.__number_f = value => Number(value || 0).toFixed(2);
global.__number_uf = value => parseFloat(value) || 0;
global.console = { log() {} };

vm.runInThisContext(fs.readFileSync(process.argv[2], 'utf8'));
calculate_other_sales_totals();

if (finalizeDisabled !== false) {
    throw new Error('Expected finalize enabled for a same-meter row, got disabled=' + finalizeDisabled);
}
JS;

        $tmpFile = tempnam(sys_get_temp_dir(), 'po-payment-test-') . '.js';
        file_put_contents($tmpFile, $script);

        $output = [];
        $exitCode = 0;
        exec('node ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg($scriptPath) . ' 2>&1', $output, $exitCode);
        @unlink($tmpFile);

        $this->assertSame(0, $exitCode, $scriptPath . "\n" . implode("\n", $output));
    }

    /** @test */
    public function enter_meters_amounts_are_formatted_with_comma_separators(): void
    {
        foreach ([
            'public/modules/petro/js/po_payment.js',
            'public/modules/pumper-dashboard/js/po_payment.js',
            'public/Modules/pumper-dashboard/js/po_payment.js',
        ] as $scriptPath) {
            $this->assertAmountsAreFormattedWithCommaSeparatorsFor($scriptPath);
        }
    }

    private function assertAmountsAreFormattedWithCommaSeparatorsFor(string $scriptPath): void
    {
        $script = <<<'JS'
const fs = require('fs');
const vm = require('vm');

let finalizeDisabled = null;
const rows = [
    { baseline: 250, newMeter: 270, unitPrice: 295, soldQty: '', amount: '', soldQtyText: '', amountText: '' },
    { baseline: 102, newMeter: 102, unitPrice: 368.75, soldQty: '', amount: '', soldQtyText: '', amountText: '' },
];
const totals = {};

function collection(items) {
    return {
        length: items.length,
        val(value) {
            if (value === undefined) {
                const item = items[0] || {};
                if (item.kind === 'todayDeposited') return '0';
                if (item.kind === 'baseline') return String(item.row.baseline);
                if (item.kind === 'newMeter') return String(item.row.newMeter);
                if (item.kind === 'unitPrice') return String(item.row.unitPrice);
                if (item.kind === 'soldQty') return item.row.soldQty;
                if (item.kind === 'amount') return item.row.amount;
                return '';
            }
            items.forEach(item => {
                if (item.kind === 'soldQty') item.row.soldQty = String(value);
                if (item.kind === 'amount') item.row.amount = String(value);
                if (item.kind === 'total') totals.total = String(value);
                if (item.kind === 'balance') totals.balance = String(value);
            });
            return this;
        },
        text(value) {
            if (value === undefined) return '';
            items.forEach(item => {
                if (item.kind === 'soldQtyText') item.row.soldQtyText = String(value);
                if (item.kind === 'amountText') item.row.amountText = String(value);
                if (item.kind === 'totalText') totals.totalText = String(value);
                if (item.kind === 'balanceText') totals.balanceText = String(value);
            });
            return this;
        },
        find(selector) {
            const row = items[0].row || items[0];
            const map = {
                '.other_sale_qty_baseline': 'baseline',
                '.other_sale_starting_meter': 'baseline',
                '.other_sale_new_meter': 'newMeter',
                '.other_sale_unit_price': 'unitPrice',
                '.other_sale_span_sold_qty': 'soldQtyText',
                '.other_sale_span_amount': 'amountText',
                '.other_sale_sold_qty': 'soldQty',
                '.other_sale_amount': 'amount',
            };
            return collection([{ kind: map[selector], row }]);
        },
        each(callback) {
            items.forEach((item, index) => callback.call(item.row || item, index, item.row || item));
            return this;
        },
        prop(name, value) {
            if (name === 'disabled') finalizeDisabled = value;
            return this;
        },
        on() { return this; },
        focus() { return this; },
        attr() { return this; },
        removeClass() { return this; },
        addClass() { return this; },
        siblings() { return collection([]); },
    };
}

function wrapRow(row) {
    const wrapper = collection([{ row }]);
    wrapper.find = function(selector) {
        const map = {
            '.other_sale_qty_baseline': 'baseline',
            '.other_sale_starting_meter': 'baseline',
            '.other_sale_new_meter': 'newMeter',
            '.other_sale_unit_price': 'unitPrice',
            '.other_sale_span_sold_qty': 'soldQtyText',
            '.other_sale_span_amount': 'amountText',
            '.other_sale_sold_qty': 'soldQty',
            '.other_sale_amount': 'amount',
        };
        return collection([{ kind: map[selector], row }]);
    };
    return wrapper;
}

function $(selector) {
    if (selector && rows.includes(selector)) return wrapRow(selector);
    if (selector === document) return collection([]);
    if (selector === '#other_sale_table tbody tr') return collection(rows);
    if (selector === '.other_sale_grand_today_deposited_input') return collection([{ kind: 'todayDeposited' }]);
    if (selector === '.other_sale_finalize') return collection([{ kind: 'finalize' }]);
    if (selector === '.other_sale_grand_total_amount') return collection([{ kind: 'totalText' }]);
    if (selector === '.other_sale_grand_total_amount_input') return collection([{ kind: 'total' }]);
    if (selector === '.other_sale_grand_balance_to_deposit') return collection([{ kind: 'balanceText' }]);
    if (selector === '.other_sale_grand_balance_to_deposit_input') return collection([{ kind: 'balance' }]);
    return collection([]);
}

global.document = { querySelector() { return null; } };
global.$ = $;
global.__read_number = element => parseFloat(element.val()) || 0;
global.__number_f = value => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
global.__number_uf = value => parseFloat(value) || 0;
global.console = { log() {} };

vm.runInThisContext(fs.readFileSync(process.argv[2], 'utf8'));
calculate_other_sales_totals();

if (rows[0].amountText !== '5,900.00') {
    throw new Error('Expected row amount text to be formatted with comma separator, got: ' + rows[0].amountText);
}
if (totals.totalText !== '5,900.00') {
    throw new Error('Expected total amount text to be formatted with comma separator, got: ' + totals.totalText);
}
if (totals.balanceText !== '5,900.00') {
    throw new Error('Expected balance amount text to be formatted with comma separator, got: ' + totals.balanceText);
}
JS;

        $tmpFile = tempnam(sys_get_temp_dir(), 'po-payment-test-') . '.js';
        file_put_contents($tmpFile, $script);

        $output = [];
        $exitCode = 0;
        exec('node ' . escapeshellarg($tmpFile) . ' ' . escapeshellarg($scriptPath) . ' 2>&1', $output, $exitCode);
        @unlink($tmpFile);

        $this->assertSame(0, $exitCode, $scriptPath . "\n" . implode("\n", $output));
    }

    /** @test */
    public function refresh_real_time_payment_tables_is_exposed_globally(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/Modules/RealTimeEntries/Resources/views/index.blade.php';
        $viewContent = file_get_contents($viewPath);

        $this->assertStringContainsString('window.refreshRealTimePaymentTables = refreshRealTimePaymentTables;', $viewContent);
    }

    /** @test */
    public function cheques_payment_button_is_removed(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/Modules/RealTimeEntries/Resources/views/partials/payment_section.blade.php';
        $viewContent = file_get_contents($viewPath);

        $this->assertStringNotContainsString('cheques_payment_btn', $viewContent);
    }

    /** @test */
    public function only_cash_and_cheque_payments_show_reload_confirmation_modal(): void
    {
        $viewPath = dirname(__DIR__, 2) . '/Modules/RealTimeEntries/Resources/views/index.blade.php';
        $viewContent = file_get_contents($viewPath);

        // Assert we don't trigger the reload modal for credit or card on success
        $this->assertStringNotContainsString("data('payment-type', 'card').modal('show')", $viewContent);
        $this->assertStringNotContainsString("data('payment-type', 'credit')", $viewContent);
    }
}
