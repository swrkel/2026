{{-- 
    Close Pump verification preview.
    Presentation-only: reads the existing Close Pump inputs and does not change
    submit URLs, validation, controller logic, database writes or permissions.
--}}
<style id="pdv-close-pump-style">
    .pdv-wrap {
        margin: 24px auto 8px;
        max-width: 1180px;
        font-family: "Segoe UI", Arial, sans-serif;
        color: #10213b;
    }

    .pdv-toolbar {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 8px;
    }

    .pdv-print-btn {
        border: 0;
        border-radius: 7px;
        background: #5a6470;
        color: #fff;
        font-weight: 700;
        padding: 9px 16px;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0,0,0,.12);
    }

    .pdv-print-btn:hover,
    .pdv-print-btn:focus {
        background: #424b54;
        color: #fff;
    }

    .pdv-card {
        background: #f4f8f8;
        border: 1px solid #d7e4e5;
        border-radius: 16px;
        padding: 12px;
        box-shadow: 0 8px 24px rgba(18,52,66,.10);
        overflow: hidden;
    }

    .pdv-header {
        min-height: 90px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 18px 24px;
        margin-bottom: 12px;
        border-radius: 12px;
        background: linear-gradient(135deg, #0b8a6b 0%, #08745d 50%, #075547 100%);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.18), 0 4px 10px rgba(0,0,0,.10);
    }

    .pdv-header h2 {
        margin: 0;
        color: #fff;
        font-size: 40px;
        line-height: 1.1;
        font-weight: 800;
        text-align: center;
        letter-spacing: -.4px;
        text-shadow: 0 2px 3px rgba(0,0,0,.15);
    }

    .pdv-top-grid {
        display: grid;
        grid-template-columns: 46% 54%;
        gap: 12px;
        margin-bottom: 12px;
    }

    .pdv-meter-visual {
        display: grid;
        grid-template-columns: 34% 66%;
        min-height: 355px;
        border-radius: 11px;
        overflow: hidden;
        background: #dce3e5;
        box-shadow: 0 2px 8px rgba(0,0,0,.08);
    }

    .pdv-nozzle {
        background-image: url("data:image/webp;base64,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");
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
        min-height: 355px;
    }

    .pdv-digital {
        background: linear-gradient(160deg, #1a252d, #10171c);
        padding: 20px 18px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 14px;
        border-left: 4px solid #c4cbce;
    }

    .pdv-digital-row label {
        display: block;
        margin: 0 0 5px;
        color: #f6f7f8;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: .2px;
    }

    .pdv-lcd {
        background: linear-gradient(#d6e6e6, #b8d0d2);
        border: 3px solid #71848b;
        border-radius: 6px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,.25);
        color: #07131a;
        padding: 10px 12px;
        text-align: right;
        font-family: "Courier New", monospace;
        font-size: 27px;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
    }

    .pdv-table-card {
        border-radius: 11px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,.07);
    }

    .pdv-table {
        width: 100%;
        height: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0;
    }

    .pdv-table th {
        padding: 13px 10px;
        background: linear-gradient(180deg, #1aa0c7, #0d82b2);
        color: #fff;
        font-size: 19px;
        font-weight: 800;
        text-align: center;
        border-right: 1px solid rgba(255,255,255,.75);
    }

    .pdv-table td {
        padding: 11px 12px;
        border-bottom: 1px solid #fff;
        vertical-align: middle;
        font-size: 18px;
        font-weight: 700;
    }

    .pdv-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .pdv-dot {
        width: 38px;
        height: 38px;
        flex: 0 0 38px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        font-weight: 800;
        box-shadow: 0 2px 4px rgba(0,0,0,.10);
    }

    .pdv-green { background: #07934a; }
    .pdv-red { background: #ef3340; }
    .pdv-blue { background: #1689d4; }
    .pdv-orange { background: #f3a317; }
    .pdv-purple { background: #7621c8; }

    .pdv-r-start { background: #e2f5e9; }
    .pdv-r-close { background: #fbe0e3; }
    .pdv-r-test  { background: #f7fafb; }
    .pdv-r-qty   { background: #dceefb; }
    .pdv-r-price { background: #fff0d7; }
    .pdv-r-total { background: #eadcfa; }

    .pdv-value {
        text-align: right;
        white-space: nowrap;
        font-size: 22px !important;
        color: #111827;
    }

    .pdv-r-total td {
        color: #5f158d;
    }

    .pdv-calc-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 13px 20px;
        margin-bottom: 10px;
        border-radius: 11px;
        background: linear-gradient(135deg, #1598be, #0d7eaa);
        color: #fff;
        font-size: 27px;
        font-weight: 800;
    }

    .pdv-calc-icon {
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 2px solid rgba(255,255,255,.85);
        border-radius: 6px;
        font-size: 22px;
        line-height: 1;
    }

    .pdv-calcs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 12px;
    }

    .pdv-calc-card {
        min-height: 210px;
        padding: 16px;
        border-radius: 12px;
    }

    .pdv-calc-left {
        background: linear-gradient(135deg, #f0f9ff, #e4f2fa);
        border: 1px solid #d0e8f4;
    }

    .pdv-calc-right {
        background: linear-gradient(135deg, #faf5ff, #f1e7fb);
        border: 1px solid #e1d2f2;
    }

    .pdv-calc-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 12px;
        font-size: 21px;
        font-weight: 800;
    }

    .pdv-num {
        width: 44px;
        height: 44px;
        flex: 0 0 44px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 23px;
        font-weight: 800;
    }

    .pdv-num-one { background: #0756ad; }
    .pdv-num-two { background: #7412a7; }

    .pdv-formula {
        padding: 10px 8px;
        margin-bottom: 11px;
        border-radius: 7px;
        text-align: center;
        font-size: 17px;
        font-weight: 700;
    }

    .pdv-calc-left .pdv-formula {
        background: #d4ebf9;
        color: #0a5297;
    }

    .pdv-calc-right .pdv-formula {
        background: #e6d5f8;
        color: #641593;
    }

    .pdv-values {
        min-height: 34px;
        margin-bottom: 9px;
        text-align: center;
        font-size: 22px;
        font-weight: 600;
    }

    .pdv-result {
        padding: 10px 8px;
        border-radius: 9px;
        text-align: center;
        font-size: 28px;
        font-weight: 800;
    }

    .pdv-result-qty {
        background: #c9efd7;
        color: #08702d;
    }

    .pdv-result-sales {
        background: #e0cef5;
        color: #681392;
    }

    .pdv-status {
        min-height: 92px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 28px;
        padding: 15px 20px;
        border-radius: 12px;
        background: linear-gradient(135deg, #078947, #0ca05d, #12a564);
    }

    .pdv-check {
        width: 62px;
        height: 62px;
        flex: 0 0 62px;
        border: 4px solid #fff;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 35px;
        font-weight: 800;
    }

    .pdv-status-badge {
        min-width: 150px;
        padding: 10px 24px;
        border-radius: 7px;
        background: #ff7b23;
        color: #fff;
        text-align: center;
        font-size: 25px;
        font-weight: 800;
        box-shadow: 0 2px 5px rgba(0,0,0,.12);
    }

    .pdv-status-badge.pdv-waiting {
        background: #7b8794;
    }

    .pdv-status-badge.pdv-error {
        background: #cc3d3d;
    }

    .pdv-footnote {
        margin-top: 7px;
        color: #5b6770;
        font-size: 12px;
        text-align: center;
    }

    @media (max-width: 900px) {
        .pdv-header h2 { font-size: 32px; }
        .pdv-top-grid,
        .pdv-calcs { grid-template-columns: 1fr; }
        .pdv-meter-visual { min-height: 320px; }
        .pdv-nozzle { min-height: 320px; }
    }

    @media (max-width: 560px) {
        .pdv-card { padding: 7px; }
        .pdv-header { min-height: 72px; padding: 14px 10px; }
        .pdv-header h2 { font-size: 25px; }
        .pdv-meter-visual { grid-template-columns: 31% 69%; min-height: 280px; }
        .pdv-nozzle { min-height: 280px; }
        .pdv-digital { padding: 14px 10px; gap: 10px; }
        .pdv-lcd { font-size: 20px; padding: 8px; }
        .pdv-table td { padding: 9px 7px; font-size: 14px; }
        .pdv-table th { font-size: 15px; }
        .pdv-value { font-size: 16px !important; }
        .pdv-dot { width: 31px; height: 31px; flex-basis: 31px; font-size: 12px; }
        .pdv-calc-heading { font-size: 21px; }
        .pdv-calc-title { font-size: 18px; }
        .pdv-values { font-size: 17px; }
        .pdv-result { font-size: 22px; }
        .pdv-status { gap: 16px; }
        .pdv-check { width: 52px; height: 52px; flex-basis: 52px; font-size: 30px; }
        .pdv-status-badge { min-width: 120px; font-size: 20px; padding: 9px 18px; }
    }

    @media print {
        .pdv-toolbar,
        .pdv-footnote {
            display: none !important;
        }

        .pdv-card {
            box-shadow: none !important;
            border: 0 !important;
        }
    }
</style>

<div class="pdv-wrap" id="pdvClosePumpWrap"
     data-currency-precision="{{ (int) ($currency_precision ?? 2) }}"
     data-initial-pump-no="{{ e($pump->pump_no ?? '') }}">
    <div class="pdv-toolbar">
        <button type="button" class="pdv-print-btn" id="pdvPrintClosePump">
            <i class="fa fa-print" aria-hidden="true"></i> Print
        </button>
    </div>

    <div class="pdv-card" id="pdvClosePumpCard">
        <div class="pdv-header">
            <h2>Close Pump <span id="pdvPumpNo">{{ $pump->pump_no ?? '' }}</span></h2>
        </div>

        <div class="pdv-top-grid">
            <div class="pdv-meter-visual" aria-label="Fuel pump meter visual">
                <div class="pdv-nozzle" aria-hidden="true"></div>
                <div class="pdv-digital">
                    <div class="pdv-digital-row">
                        <label>AMOUNT (Rs.)</label>
                        <div class="pdv-lcd" id="pdvDigitalAmount">0.00</div>
                    </div>
                    <div class="pdv-digital-row">
                        <label>VOLUME (L)</label>
                        <div class="pdv-lcd" id="pdvDigitalVolume">0.000</div>
                    </div>
                    <div class="pdv-digital-row">
                        <label>RATE (Rs./L)</label>
                        <div class="pdv-lcd" id="pdvDigitalRate">0.00</div>
                    </div>
                </div>
            </div>

            <div class="pdv-table-card">
                <table class="pdv-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Reading / Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="pdv-r-start">
                            <td><div class="pdv-item"><span class="pdv-dot pdv-green">S</span><span>Starting Meter</span></div></td>
                            <td class="pdv-value" id="pdvStarting">0.000</td>
                        </tr>
                        <tr class="pdv-r-close">
                            <td><div class="pdv-item"><span class="pdv-dot pdv-red">C</span><span>Closing Meter</span></div></td>
                            <td class="pdv-value" id="pdvClosing">0.000</td>
                        </tr>
                        <tr class="pdv-r-test">
                            <td><div class="pdv-item"><span class="pdv-dot pdv-blue">T</span><span>Testing Liters</span></div></td>
                            <td class="pdv-value" id="pdvTesting">0.000</td>
                        </tr>
                        <tr class="pdv-r-qty">
                            <td><div class="pdv-item"><span class="pdv-dot pdv-blue">L</span><span>Total Sold Quantity</span></div></td>
                            <td class="pdv-value" id="pdvSold">0.000</td>
                        </tr>
                        <tr class="pdv-r-price">
                            <td><div class="pdv-item"><span class="pdv-dot pdv-orange">Rs</span><span>Price per Liter</span></div></td>
                            <td class="pdv-value" id="pdvPrice">Rs. 0.00</td>
                        </tr>
                        <tr class="pdv-r-total">
                            <td><div class="pdv-item"><span class="pdv-dot pdv-purple">Σ</span><span>Total Sales Value</span></div></td>
                            <td class="pdv-value" id="pdvAmount">Rs. 0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pdv-calc-heading">
            <span class="pdv-calc-icon">=</span>
            <span>Correct Calculations</span>
        </div>

        <div class="pdv-calcs">
            <div class="pdv-calc-card pdv-calc-left">
                <div class="pdv-calc-title">
                    <span class="pdv-num pdv-num-one">1</span>
                    <span>Total Sold Quantity</span>
                </div>
                <div class="pdv-formula" id="pdvQtyFormula">Closing Meter − Starting Meter</div>
                <div class="pdv-values" id="pdvQtyValues">0.000 − 0.000</div>
                <div class="pdv-result pdv-result-qty" id="pdvQtyResult">= 0.000</div>
            </div>

            <div class="pdv-calc-card pdv-calc-right">
                <div class="pdv-calc-title">
                    <span class="pdv-num pdv-num-two">2</span>
                    <span>Total Sales Value</span>
                </div>
                <div class="pdv-formula">Total Sold Quantity × Selling Price</div>
                <div class="pdv-values" id="pdvSalesValues">0.000 × Rs. 0.00</div>
                <div class="pdv-result pdv-result-sales" id="pdvSalesResult">= Rs. 0.00</div>
            </div>
        </div>

        <div class="pdv-status">
            <div class="pdv-check" id="pdvStatusIcon">✓</div>
            <div class="pdv-status-badge pdv-waiting" id="pdvStatusBadge">Waiting</div>
        </div>
    </div>

    <div class="pdv-footnote">
        This preview is for operator verification and printing. The existing Close Pump save/validation process remains unchanged.
    </div>
</div>

<script>
(function () {
    'use strict';

    var root = document.getElementById('pdvClosePumpWrap');
    if (!root) {
        return;
    }

    var currencyPrecision = parseInt(root.getAttribute('data-currency-precision'), 10);
    if (isNaN(currencyPrecision) || currencyPrecision < 0 || currencyPrecision > 6) {
        currencyPrecision = 2;
    }

    function el(id) {
        return document.getElementById(id);
    }

    function numberFromInput(id) {
        var node = el(id);
        if (!node) {
            return 0;
        }

        var raw = String(node.value || '').replace(/,/g, '').trim();
        var value = parseFloat(raw);

        return isNaN(value) ? 0 : value;
    }

    function textFromInput(id, fallback) {
        var node = el(id);
        var value = node ? String(node.value || '').trim() : '';

        return value !== '' ? value : String(fallback || '').trim();
    }

    function fieldHasValue(id) {
        var node = el(id);
        return !!(node && String(node.value || '').trim() !== '');
    }

    function formatNumber(value, decimals) {
        var n = Number(value);
        if (!isFinite(n)) {
            n = 0;
        }

        return n.toLocaleString('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        });
    }

    function setText(id, value) {
        var node = el(id);
        if (node) {
            node.textContent = value;
        }
    }

    function refreshPreview() {
        // Pump number is read from the existing Close Pump form on every refresh.
        // The server-rendered value is only a safe fallback for the first paint.
        var pumpNo = textFromInput('pump_no', root.getAttribute('data-initial-pump-no'));
        setText('pdvPumpNo', pumpNo);

        var starting = numberFromInput('starting_meter');
        var closing = numberFromInput('closing_meter');
        var testing = Math.max(0, numberFromInput('testing_ltr'));
        var price = numberFromInput('sale_price');

        // IMPORTANT: mirrors the existing Close Pump calculation exactly.
        // No save/business rule is changed here.
        var sold = closing - starting - testing;
        var amount = sold * price;

        var hasClosing = fieldHasValue('closing_meter');
        var valid = hasClosing && closing >= starting && sold >= 0;

        var displaySold = valid ? sold : 0;
        var displayAmount = valid ? amount : 0;

        setText('pdvStarting', formatNumber(starting, 3));
        setText('pdvClosing', hasClosing ? formatNumber(closing, 3) : '—');
        setText('pdvTesting', formatNumber(testing, 3));
        setText('pdvSold', formatNumber(displaySold, 3));
        setText('pdvPrice', 'Rs. ' + formatNumber(price, currencyPrecision));
        setText('pdvAmount', 'Rs. ' + formatNumber(displayAmount, currencyPrecision));

        setText('pdvDigitalAmount', formatNumber(displayAmount, currencyPrecision));
        setText('pdvDigitalVolume', formatNumber(displaySold, 3));
        setText('pdvDigitalRate', formatNumber(price, currencyPrecision));

        if (testing > 0) {
            setText('pdvQtyFormula', 'Closing Meter − Starting Meter − Testing Liters');
            setText(
                'pdvQtyValues',
                (hasClosing ? formatNumber(closing, 3) : '—') +
                ' − ' + formatNumber(starting, 3) +
                ' − ' + formatNumber(testing, 3)
            );
        } else {
            setText('pdvQtyFormula', 'Closing Meter − Starting Meter');
            setText(
                'pdvQtyValues',
                (hasClosing ? formatNumber(closing, 3) : '—') +
                ' − ' + formatNumber(starting, 3)
            );
        }

        setText('pdvQtyResult', '= ' + formatNumber(displaySold, 3));
        setText(
            'pdvSalesValues',
            formatNumber(displaySold, 3) + ' × Rs. ' + formatNumber(price, currencyPrecision)
        );
        setText(
            'pdvSalesResult',
            '= Rs. ' + formatNumber(displayAmount, currencyPrecision)
        );

        var badge = el('pdvStatusBadge');
        var icon = el('pdvStatusIcon');

        if (!badge || !icon) {
            return;
        }

        badge.classList.remove('pdv-waiting', 'pdv-error');

        if (!hasClosing) {
            badge.textContent = 'Waiting';
            badge.classList.add('pdv-waiting');
            icon.textContent = '…';
        } else if (!valid) {
            badge.textContent = 'Check Values';
            badge.classList.add('pdv-error');
            icon.textContent = '!';
        } else {
            badge.textContent = 'Correct';
            icon.textContent = '✓';
        }
    }

    ['pump_no', 'starting_meter', 'closing_meter', 'testing_ltr', 'sale_price'].forEach(function (id) {
        var node = el(id);
        if (node) {
            node.addEventListener('input', refreshPreview);
            node.addEventListener('change', refreshPreview);
            node.addEventListener('blur', refreshPreview);
        }
    });

    var calcButton = el('calculate_total_btn');
    if (calcButton) {
        calcButton.addEventListener('click', function () {
            window.setTimeout(refreshPreview, 0);
        });
    }

    var cancelButton = el('cancel');
    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            window.setTimeout(refreshPreview, 0);
        });
    }

    var printButton = el('pdvPrintClosePump');
    if (printButton) {
        printButton.addEventListener('click', function () {
            refreshPreview();

            var card = el('pdvClosePumpCard');
            var style = el('pdv-close-pump-style');

            if (!card || !style) {
                return;
            }

            var printWindow = window.open('', '_blank', 'width=1200,height=900');
            if (!printWindow) {
                if (window.toastr) {
                    toastr.error('Please allow pop-ups to print the Close Pump verification.');
                }
                return;
            }

            printWindow.document.open();
            var pumpNo = textFromInput('pump_no', root.getAttribute('data-initial-pump-no'));
            var printTitle = 'Close Pump ' + (pumpNo || '') + ' Verification';

            printWindow.document.write(
                '<!doctype html><html><head><meta charset="utf-8">' +
                '<title>' + printTitle.replace(/[<>&"']/g, '') + '</title>' +
                '<style>@page{size:A4 landscape;margin:8mm;}' +
                'html,body{margin:0;padding:0;background:#fff;}' +
                '.pdv-card{max-width:1120px;margin:0 auto;}' +
                '</style>' +
                '<style>' + style.textContent + '</style>' +
                '</head><body>' +
                card.outerHTML +
                '<script>window.onload=function(){setTimeout(function(){window.print();},150);};<\/script>' +
                '</body></html>'
            );
            printWindow.document.close();
        });
    }

    refreshPreview();
})();
</script>
