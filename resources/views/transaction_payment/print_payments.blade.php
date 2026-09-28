@php
    $latestPayment = $payments->first();
    $latestMethod = strtolower((string) optional($latestPayment)->method);
    $displayAmount = (!empty($latestPayment) && (float) $latestPayment->amount > 0 && $latestMethod !== 'credit_expense')
        ? (float) $latestPayment->amount
        : (float) $transaction->final_total;
    $displayDate = !empty($latestPayment) && !empty($latestPayment->paid_on) ? \Carbon\Carbon::parse($latestPayment->paid_on)->format('Y-m-d') : \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d');
    $displayMethod = !empty($latestPayment) ? ucfirst(str_replace('_', ' ', $latestPayment->method)) : '--';
    if (!empty($latestPayment) && strtolower((string) $latestPayment->method) === 'credit_expense') {
        $displayMethod = '--';
    }
    $paymentNote = !empty($latestPayment) ? ($latestPayment->note ?? '') : '';
    $expenseNote = $transaction->additional_notes ?? '';
    $arrangedBy = !empty($arranged_by_user) ? trim(($arranged_by_user->first_name ?? '') . ' ' . ($arranged_by_user->last_name ?? '')) : '';
    $arrangedBy = !empty(trim($arrangedBy)) ? $arrangedBy : (!empty($arranged_by_user) ? ($arranged_by_user->username ?? '') : '');
    $arrangedByDesignation = !empty($arranged_by_user) ? ($arranged_by_user->designation ?? '') : '';

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

<div class="modal-dialog" role="document" style="width: 80%; margin: 20px auto;">
    <div class="modal-content" style="min-height:510px">
        <div class="modal-header" style="border-bottom:none;">
            <div class="text-center"><h4 style="font-weight: bold">{{ $business->name }}</h4></div>
            <div class="text-center"><h4 style="font-weight: bold">{{ $business_locations->address_1 ?? '' }}</h4></div>
            <div class="text-center"><h4 style="font-weight: bold">Payment Voucher</h4></div>
        </div>

        <div class="cus-table" style="font-weight: bold; margin:0px 20px; border: 1px solid #e5e5e5;">
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
                <tr><td colspan="3">To: {{ $transaction->name ?? optional($transaction->contact)->name }}</td></tr>
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
                    <td>Approved By: <input type="text" style="border:none; margin-left:5px; border-bottom: 1px solid #e5e5e5" width=100/></td>
                    <td>Paid By: <input type="text" style="border:none; margin-left:5px; border-bottom: 1px solid #e5e5e5" width=100/></td>
                    <td><span style="vertical-align: 15px;">Signature: </span><img style="display:none" class="new-img"/><canvas id="signature-pad" class="signature-pad" width=100 height=50></canvas></td>
                </tr>
            </table>
        </div>

        <div class="modal-footer no-print" style="border-top:none; text-align:left;">
            <button type="button" class="btn btn-primary sub-button" aria-label="Print">
                <i class="fa fa-print"></i> @lang('messages.print')
            </button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

<script>
window.initExpenseVoucherPrintModal = function(containerSelector) {
    var $scope = containerSelector ? $(containerSelector) : $(document);
    if (!$scope.length) {
        $scope = $(document);
    }

    var signatureDataURL = '';
    var signatureCanvas = $scope.find('#signature-pad').get(0);
    if (signatureCanvas && typeof SignaturePad !== 'undefined') {
        var signaturePad = new SignaturePad(signatureCanvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(0, 0, 0)'
        });
        signatureDataURL = signaturePad.toDataURL('image/png');
    }

    $scope.off('click.expenseVoucherPrint', '.modal-footer button[aria-label="Print"]');
    $scope.on('click.expenseVoucherPrint', '.modal-footer button[aria-label="Print"]', function() {
        var $btn = $(this);
        var $footer = $btn.closest('.no-print');
        $footer.hide();

        if (signatureDataURL) {
            var signatureImage = new Image();
            signatureImage.src = signatureDataURL;
            $scope.find('.new-img').replaceWith(signatureImage);
        }

        var $printTarget = $btn.closest('div.modal');
        if (!$printTarget.length) {
            $printTarget = $btn.closest('div.modal-dialog');
        }

        if (typeof $.fn.printThis === 'function') {
            $printTarget.printThis({
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
            setTimeout(function() {
                $footer.show();
            }, 1000);
        } else {
            window.print();
            $footer.show();
        }
    });
};

$(document).ready(function() {
    window.initExpenseVoucherPrintModal();
});
</script>


