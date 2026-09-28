@extends('layouts.app')
@section('title', __('Customer Statements'))

@section('content')
<section class="content-header cs-v2-header">
    <h1>Customer Statements <small>Standalone Customers module workspace</small></h1>
</section>

<section class="content customer-statements-v2" data-next-date-url="{{ route('customers.reports.statement.next_date') }}">
    <div class="box box-solid">
        <div class="box-body">
            @include('customers::customer_statements_v2.partials.tabs')

            <div class="tab-content cs-v2-tab-content">
                <div class="tab-pane active" id="cs-v2-create">
                    @include('customers::customer_statements_v2.partials.create_statement')
                </div>
                <div class="tab-pane" id="cs-v2-list">
                    @include('customers::customer_statements_v2.partials.list_statements')
                </div>
                <div class="tab-pane" id="cs-v2-logo-settings">
                    @include('customers::customer_statements_v2.partials.logo_settings')
                </div>
                <div class="tab-pane" id="cs-v2-number-settings">
                    @include('customers::customer_statements_v2.partials.number_settings')
                </div>
                <div class="tab-pane" id="cs-v2-payments">
                    @include('customers::customer_statements_v2.partials.statement_payments')
                </div>
                <div class="tab-pane" id="cs-v2-font-settings">
                    @include('customers::customer_statements_v2.partials.font_settings')
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('css')
@parent
@php($customersStatementCss = module_path('Customers', 'Resources/assets/css/customer-statements-v2.css'))
<style>{!! is_file($customersStatementCss) ? file_get_contents($customersStatementCss) : '' !!}</style>
@endsection

@section('javascript')
@parent
<script>
window.CustomerStatementsV2 = {
    saveUrl: @json(url('/customers/customer-statement')),
    listUrl: @json(url('/customers/customer-statement/get-statement-list')),
    paymentsUrl: @json(url('/customers/customer-statement/get-statement-list-pmts')),
    logosUrl: @json(url('/customers/customer-statement-logos')),
    settingsUrl: @json(url('/customers/customer-statement-settings')),
    csrf: @json(csrf_token())
};
</script>
@php($customersStatementJs = module_path('Customers', 'Resources/assets/js/customer-statements-v2.js'))
<script>{!! is_file($customersStatementJs) ? file_get_contents($customersStatementJs) : '' !!}</script>
@endsection
