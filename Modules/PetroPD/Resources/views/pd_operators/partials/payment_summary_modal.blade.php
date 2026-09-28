@if(!empty(Auth::user()->pump_operator_id))
<style>
    #payment_summary_modal .dt-buttons{
        display: none !important;
    }
</style>
@endif
<div class="modal-dialog" role="document" style="width: 70%; max-width: 96%;" id="payment_summary_modal">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'petropd::lang.payment_summary' )</h4>
        </div>

        <div class="modal-body">
            <div class="col-md-12">
                <div class="row">
                    @include('petropd::pd_operators.partials.payment_summary')
                </div>
            </div>
            <div class="clearfix"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
            </div>

        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>

@include('petropd::pd_operators.partials.payment_summary_scripts')
@include('petropd::pd_operators.partials.payment_summary_edit_modal_script')
<script>
$(document).ready(function() {
    window.pump_operators_payment_summary_table = window.initPetroPDPaymentSummaryTable({
        includeExtraColumns: @json(empty($only_pumper)),
        ajaxUrl: "{{ route('petropd.pump-operator-payments.index', ['only_pumper' => true]) }}"
    });
});
</script>
