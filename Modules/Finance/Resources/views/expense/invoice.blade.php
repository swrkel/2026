@extends('layouts.printlayout')
@section('title')
Expense
@endsection
@section('content')
@php
    $latestPayment = $payments->sortByDesc('id')->first();
    $latestMethod = strtolower((string) optional($latestPayment)->method);
    $displayAmount = (!empty($latestPayment) && (float) $latestPayment->amount > 0 && $latestMethod !== 'credit_expense')
        ? (float) $latestPayment->amount
        : (float) $transaction->final_total;
    $displayDate = !empty($latestPayment) && !empty($latestPayment->paid_on)
        ? \Carbon\Carbon::parse($latestPayment->paid_on)->format('Y-m-d')
        : \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d');
    $displayMethod = !empty($latestPayment) ? ucfirst(str_replace('_', ' ', $latestPayment->method)) : '--';
    if (!empty($latestPayment) && strtolower((string) $latestPayment->method) === 'credit_expense') {
        $displayMethod = '--';
    }
    $paymentNote = !empty($latestPayment) ? ($latestPayment->note ?? '') : '';
    $expenseNote = $transaction->additional_notes ?? '';
    $arrangedByUser = !empty($transaction->created_by) ? \App\User::find($transaction->created_by) : null;
    $arrangedBy = !empty($arrangedByUser)
        ? trim(($arrangedByUser->first_name ?? '') . ' ' . ($arrangedByUser->last_name ?? ''))
        : '';
    $arrangedBy = trim($arrangedBy) !== '' ? $arrangedBy : (!empty($arrangedByUser) ? ($arrangedByUser->username ?? '') : '');
    $arrangedByDesignation = !empty($arrangedByUser) ? ($arrangedByUser->designation ?? '') : '';

    $numberToWords = function ($num) use (&$numberToWords) {
        $num = (int) $num;
        $belowTen = ['Zero', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine'];
        $belowTwenty = ['Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $belowHundred = ['Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        if ($num < 10) {
            return $belowTen[$num];
        }
        if ($num < 20) {
            return $belowTwenty[$num - 10];
        }
        if ($num < 100) {
            return $belowHundred[intdiv($num, 10) - 2] . ($num % 10 !== 0 ? ' ' . $belowTen[$num % 10] : '');
        }
        if ($num < 1000) {
            return $belowTen[intdiv($num, 100)] . ' Hundred' . ($num % 100 !== 0 ? ' ' . $numberToWords($num % 100) : '');
        }
        if ($num < 1000000) {
            return $numberToWords(intdiv($num, 1000)) . ' Thousand' . ($num % 1000 !== 0 ? ' ' . $numberToWords($num % 1000) : '');
        }
        return (string) $num;
    };
    $displayAmountWords = $numberToWords((int) round($displayAmount)) . ' only';
@endphp

<style>
  .cus-table table { width: 100%; }
  .cus-table table, td, th { height: 40px; padding-left: 5px; font-weight: bold; vertical-align: top; }
</style>

<div class="modal-dialog" role="document" style="width: 80%;">
    <div class="modal-content" style="min-height:510px">
        <div class="modal-header" style="border-bottom:none;">
            <div class="text-center"><h4 style="font-weight: bold">{{ $business->name }}</h4></div>
            <div class="text-center"><h4 style="font-weight: bold">{{ $business_locations->address_1 ?? '' }}</h4></div>
            <div class="text-center"><h4 style="font-weight: bold">Payment Voucher</h4></div>
        </div>

        <div class="cus-table" style="font-weight: bold; margin:0 20px; border: 1px solid #e5e5e5;">
            <div style="border: 1px solid #e5e5e5; width: 160px; height: 30px; padding-top:3px; padding-left:5px; padding-right:5px;">
                PV No: {{ $transaction->ref_no }}
            </div>
            <br/>
            <table>
                <tr>
                    <td>Amount: {{ number_format($displayAmount, 2) }}</td>
                    <td colspan="2">Date: {{ $displayDate }}</td>
                </tr>
                <tr><td colspan="3"></td></tr>
                <tr><td colspan="3">Payment Method: {{ $displayMethod }}</td></tr>
                <tr><td colspan="3">To: {{ optional($transaction->contact)->name }}</td></tr>
                <tr><td colspan="3">The sum of: <span class="print_sum">{{ $displayAmountWords }}</span></td></tr>
                <tr>
                    <td colspan="3">Being:</td>
                </tr>
                <tr>
                    <td colspan="2">
                        @if(!empty($show_payment_note_in_print))
                            Payment Note: {{ $paymentNote }}
                        @endif
                    </td>
                    <td>
                        @if(!empty($show_expense_note_in_print))
                            Expense Note: {{ $expenseNote }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td colspan="2">Arranged By: {{ $arrangedBy }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="2">Designation: {{ $arrangedByDesignation }}</td>
                    <td></td>
                </tr>
                <tr>
                    <td>Approved By: <input type="text" style="border:none; margin-left:5px; border-bottom: 1px solid #e5e5e5" width="100"/></td>
                    <td>Paid By: <input type="text" style="border:none; margin-left:5px; border-bottom: 1px solid #e5e5e5" width="100"/></td>
                    <td><span style="vertical-align: 15px;">Signature: </span><img style="display:none" class="new-img"/><canvas id="signature-pad" class="signature-pad" width="100" height="50"></canvas></td>
                </tr>
            </table>
        </div>

        <div class="modal-footer no-print" style="border-top:none; text-align:left;">
            <button type="button" class="btn btn-primary sub-button" aria-label="Print">
                <i class="fa fa-print"></i> @lang('messages.print')
            </button>
            <a href="{{ url('expenses') }}" class="btn btn-default">@lang('messages.close')</a>
        </div>
    </div>
</div>

<script>
$(document).ready(function(){
    var signaturePad = null;
    var signatureDataURL = '';
    if (typeof SignaturePad !== 'undefined') {
        signaturePad = new SignaturePad(document.getElementById('signature-pad'), {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)'
        });
        signatureDataURL = signaturePad.toDataURL('image/png');
    }

    $('.modal-footer').on('click', 'button[aria-label="Print"]', function () {
        $(this).closest('.no-print').hide();
        if (signatureDataURL) {
            var signatureImage = new Image();
            signatureImage.src = signatureDataURL;
            $('.new-img').replaceWith(signatureImage);
        }

        if (typeof $.fn.printThis === 'function') {
            $(this).closest('div.modal-dialog').printThis({
                importCSS: true,
                loadCSS: "",
                pageTitle: "",
                removeInline: false,
                printDelay: 333,
                header: null,
                footer: null,
                base: false,
                formValues: true,
                canvas: true,
                doctypeString: '<!DOCTYPE html>',
                removeScripts: false,
                copyTagClasses: false,
            });
            setTimeout(() => { $(this).closest('.no-print').show(); }, 1000);
        } else {
            window.print();
            $(this).closest('.no-print').show();
        }
    });
});
</script>
@endsection
