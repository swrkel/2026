{{--
    MA-002 (IS-1915 #2): a proper printed receipt for F10.

    WHAT IT REPLACED
    The form called window.print() on the page itself, so the printout was the
    data-entry screen - input boxes, buttons and all. That is why it came out
    looking like separate fields rather than a document.

    This is a standalone document: a header, the receipt details, the four
    amount breakdowns, a total, and signature lines.

    THE FOUR BREAKDOWNS come from the columns storeReceipt() already writes:
        cash_amount     ->  Cash
        bank_amount     ->  Bank / ATM Deposits
        cheque_amount   ->  Cheques
        card_amount     ->  Credit Vouchers

    Note on the fourth: the header table has a card_amount column and the
    controller already saves it, but the ENTRY FORM has no input for it, so it
    is always 0 today. The row is included and will fill itself the moment an
    input is added. See the parcel notes.

    Everything is inline - no external stylesheet - so the printed page is not
    at the mercy of whichever CSS happens to load.
--}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>F10 Receipt {{ $header->form_no ?? '' }}</title>
    <style>
        /*
         * IS2015: remove the browser's own print header and footer.
         *
         * The marked areas in the ticket - "8/13/26, 5:29 PM  F10 Receipt 4444"
         * at the top and the source URL with "1/1" at the bottom - are drawn by
         * the BROWSER into the page margin, not by this document. The only way
         * CSS can suppress them is to leave no margin for them to occupy, so the
         * page margin goes to zero and the equivalent white space is recreated
         * as padding on .f10-doc below.
         *
         * Caveat worth knowing: this works in Chrome and Edge. Firefox and
         * Safari may still print them, and any browser will bring them back if
         * the user re-ticks "Headers and footers" in the print dialog - that
         * setting overrides page CSS by design.
         */
        @page { size: A4; margin: 0; }

        * { box-sizing: border-box; }

        body {
            font-family: "DejaVu Sans", Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 0;
        }

        /* IS2015: padding replaces the @page margin removed above, so the
           content keeps the same white border it had before while leaving the
           browser no margin in which to draw its header and footer. */
        .f10-doc { max-width: 180mm; margin: 0 auto; padding: 14mm 14mm 12mm; }

        .f10-head {
            text-align: center;
            border-bottom: 2px solid #111;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .f10-head .company {
            font-size: 17px;
            font-weight: 700;
            letter-spacing: .3px;
        }

        .f10-head .sub { font-size: 12px; color: #444; margin-top: 2px; }

        .f10-head .title {
            font-size: 14px;
            font-weight: 700;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .f10-meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .f10-meta td { padding: 3px 0; vertical-align: top; }
        .f10-meta .lbl { color: #555; width: 32%; }
        .f10-meta .val { font-weight: 700; }

        .f10-amounts {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .f10-amounts th {
            text-align: left;
            background: #f2f4f7;
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
            font-size: 12px;
        }

        .f10-amounts th.amt, .f10-amounts td.amt { text-align: right; }

        .f10-amounts td {
            border: 1px solid #cbd5e1;
            padding: 7px 10px;
        }

        .f10-amounts tr.total td {
            font-weight: 700;
            font-size: 13px;
            background: #f8fafc;
            border-top: 2px solid #111;
        }

        .f10-words {
            margin: 10px 0 22px;
            padding: 8px 10px;
            border: 1px dashed #94a3b8;
            font-size: 12px;
        }

        .f10-sign {
            width: 100%;
            margin-top: 34px;
            border-collapse: collapse;
        }

        .f10-sign td {
            width: 33.33%;
            text-align: center;
            padding-top: 34px;
            font-size: 11px;
            color: #333;
        }

        .f10-sign .line {
            border-top: 1px solid #111;
            margin: 0 10px 5px;
        }

        .f10-foot {
            margin-top: 18px;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
            font-size: 10px;
            color: #777;
            text-align: center;
        }

        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body onload="window.print();">
<div class="f10-doc">

    <div class="f10-head">
        <div class="company">{{ $business->name ?? '' }}</div>
        @if(!empty($location))
            <div class="sub">{{ $location->name ?? '' }}</div>
        @endif
        <div class="title">F10 &mdash; Cash Receipt</div>
    </div>

    @php
        $cash   = (float) ($header->cash_amount   ?? 0);
        $bank   = (float) ($header->bank_amount   ?? 0);
        $cheque = (float) ($header->cheque_amount ?? 0);
        $card   = (float) ($header->card_amount   ?? 0);

        /*
         * The stored total is used when it is present, rather than re-adding
         * the four parts. If the two ever disagree that is a real discrepancy
         * and the printed receipt should show what was saved, not a figure
         * this view invented.
         */
        $total = (float) ($header->total_amount ?? 0);
        $sumOfParts = $cash + $bank + $cheque + $card;
    @endphp

    <table class="f10-meta">
        <tr>
            <td class="lbl">F10 No</td>
            <td class="val">{{ $header->form_no ?? '' }}</td>
            <td class="lbl">Date</td>
            <td class="val">{{ !empty($header->form_date) ? \Carbon\Carbon::parse($header->form_date)->format('d/m/Y') : '' }}</td>
        </tr>
        <tr>
            <td class="lbl">Manager</td>
            <td class="val">{{ $manager->manager_name ?? '-' }}</td>{{-- MA-002: the column is manager_name, not name --}}
            <td class="lbl">Prepared by</td>
            <td class="val">{{ $preparedBy ?? '-' }}</td>
        </tr>
    </table>

    <table class="f10-amounts">
        <thead>
            <tr>
                <th style="width: 8%;">#</th>
                <th>Description</th>
                <th class="amt" style="width: 28%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>Cash</td>
                <td class="amt">{{ number_format($cash, 2) }}</td>
            </tr>
            <tr>
                <td>2</td>
                <td>Bank / ATM Deposits</td>
                <td class="amt">{{ number_format($bank, 2) }}</td>
            </tr>
            <tr>
                <td>3</td>
                <td>Cheques</td>
                <td class="amt">{{ number_format($cheque, 2) }}</td>
            </tr>
            <tr>
                <td>4</td>
                <td>Credit Vouchers</td>
                <td class="amt">{{ number_format($card, 2) }}</td>
            </tr>
            <tr class="total">
                <td colspan="2">Total</td>
                <td class="amt">{{ number_format($total, 2) }}</td>
            </tr>
        </tbody>
    </table>

    @if(abs($total - $sumOfParts) > 0.009)
        {{-- Shown only when the saved total does not equal the four parts.
             Silently printing one or the other would hide a real problem. --}}
        <div class="f10-words" style="border-color:#dc2626; color:#b91c1c;">
            <strong>Note:</strong> the recorded total ({{ number_format($total, 2) }})
            does not match the sum of the amounts above ({{ number_format($sumOfParts, 2) }}).
        </div>
    @endif

    <table class="f10-sign">
        <tr>
            <td><div class="line"></div>Prepared By</td>
            <td><div class="line"></div>Checked By</td>
            <td><div class="line"></div>Manager</td>
        </tr>
    </table>

    <div class="f10-foot">
        Printed {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}
    </div>

</div>
</body>
</html>
