{{-- PD-038 / Pumper Dashboard Payment Button Force Fix --}}
@php
    $pumperPaymentButtonFixJs = file_exists(public_path('Modules/pumper-dashboard/js/po_payment_button_force_fix.js'))
        ? asset('Modules/pumper-dashboard/js/po_payment_button_force_fix.js')
        : asset('modules/pumper-dashboard/js/po_payment_button_force_fix.js');
@endphp
<script src="{{ $pumperPaymentButtonFixJs }}?v={{ time() }}"></script>
