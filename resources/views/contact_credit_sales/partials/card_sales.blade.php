<section class="content main-content-inner">
    <div class="row">
        <div class="col-md-12">
            @include('contact_credit_sales.partials.filters_card_sales')
        </div>
    </div>

    <div class="table-responsive">
        @component('components.widget', ['class' => 'box-primary'])
            <table class="table table-bordered table-striped" id="card_sales_table" style="width: 100%">
                <thead>
                    <tr>
                        <th>@lang('contact_credit_sales.date')</th>
                        <th>@lang('contact_credit_sales.location')</th>
                        <th>@lang('contact_credit_sales.card_payment_type')</th>
                        <th>@lang('contact_credit_sales.invoice_no')</th>
                        <th>@lang('contact_credit_sales.card_type')</th>
                        <th>@lang('contact_credit_sales.slip_no')</th>
                        <th>@lang('contact_credit_sales.amount')</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="bg-gray font-17 footer-total text-center">
                        <td colspan="6"><strong>@lang('contact_credit_sales.total'):</strong></td>
                        <td><span id="card_total">0.00</span></td>
                    </tr>
                </tfoot>
            </table>
        @endcomponent
    </div>
</section>