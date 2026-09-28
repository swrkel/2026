@extends('layouts.app')
@section('title', __('petropd::lang.pump_operators'))

@section('content')
@php
    // IS1460-002: This page is a PetroPD pump-operator profile, not an Account Book.
    // Some older shared includes expected $account and caused undefined variable errors.
    $account = $account ?? null;
@endphp
<section class="content-header">
    <h1>
        {{ $pump_operator->name ?? __('petropd::lang.pump_operators') }}
        <small>@lang('petropd::lang.pump_operators')</small>
    </h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $pump_operator->name ?? '' }}</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('petropd.pd-operators') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <div class="box-body">
            <ul class="nav nav-tabs" role="tablist">
                <li class="{{ $view_type == 'contact_info' ? 'active' : '' }}">
                    <a href="#contact_info_tab" data-toggle="tab">
                        <i class="fa fa-id-card-o"></i> @lang('contact.contact_info', ['contact' => __('contact.contact')])
                    </a>
                </li>

                @if(!empty($pump_operator_ledger_permission))
                    <li class="{{ $view_type == 'ledger' ? 'active' : '' }}">
                        <a href="#ledger_tab" data-toggle="tab">
                            <i class="fa fa-anchor"></i> @lang('lang_v1.ledger')
                        </a>
                    </li>
                @endif

                <li class="{{ $view_type == 'documents_and_notes' ? 'active' : '' }}">
                    <a href="#documents_and_notes_tab" data-toggle="tab">
                        <i class="fa fa-paperclip"></i> @lang('lang_v1.documents_and_notes')
                    </a>
                </li>
            </ul>

            <div class="tab-content" style="padding-top:15px;">
                <div class="tab-pane {{ $view_type == 'contact_info' ? 'active' : '' }}" id="contact_info_tab">
                    @includeIf('petropd::pd_operators.partials.contact_info_tab')
                </div>

                @if(!empty($pump_operator_ledger_permission))
                    <div class="tab-pane {{ $view_type == 'ledger' ? 'active' : '' }}" id="ledger_tab">
                        <div class="row">
                            <div class="col-md-12">
                                @component('components.widget', ['class' => 'box'])
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            {!! Form::label('ledger_date_range_new', __('report.date_range') . ':') !!}
                                            {!! Form::text('ledger_date_range_new', null, [
                                                'placeholder' => __('lang_v1.select_a_date_range'),
                                                'class' => 'form-control',
                                                'readonly',
                                                'id' => 'ledger_date_range_new'
                                            ]) !!}
                                        </div>
                                    </div>
                                    <div class="col-md-9 text-right">
                                        <button type="button" class="btn btn-default btn-xs" id="print_btn">
                                            <i class="fa fa-print"></i>
                                        </button>
                                        <button type="button" class="btn btn-default btn-xs" id="print_ledger_pdf">
                                            <i class="fa fa-file-pdf-o"></i>
                                        </button>
                                    </div>
                                @endcomponent
                            </div>
                            <div class="col-md-12">
                                @component('components.widget', ['class' => 'box'])
                                    <div id="contact_ledger_div"></div>
                                @endcomponent
                            </div>
                        </div>
                    </div>
                @endif

                <div class="tab-pane {{ $view_type == 'documents_and_notes' ? 'active' : '' }}" id="documents_and_notes_tab">
                    @includeIf('petropd::pd_operators.partials.documents_and_notes_tab')
                </div>
            </div>
        </div>
    </div>
</section>
@stop

@section('javascript')
<script type="text/javascript">
$(document).ready(function() {
    @if($view_type == 'ledger' && !empty($pump_operator_ledger_permission))
        function loadPetroPdOperatorLedger(action) {
            var start = '';
            var end = '';

            if ($('#ledger_date_range_new').val()) {
                start = $('#ledger_date_range_new').data('daterangepicker').startDate.format('YYYY-MM-DD');
                end = $('#ledger_date_range_new').data('daterangepicker').endDate.format('YYYY-MM-DD');
            }

            var url = "{{ route('petropd.pd-operators.ledger') }}";
            var data = {
                pump_operator_id: "{{ $pump_operator->id }}",
                start_date: start,
                end_date: end
            };

            if (action) {
                data.action = action;
                window.open(url + '?' + $.param(data), '_blank');
                return;
            }

            $('#contact_ledger_div').html('<div class="text-center" style="padding:20px;"><i class="fa fa-spinner fa-spin"></i> Loading...</div>');

            $.ajax({
                url: url,
                method: 'GET',
                data: data,
                success: function(result) {
                    $('#contact_ledger_div').html(result);
                    __currency_convert_recursively($('#contact_ledger_div'));
                },
                error: function(xhr) {
                    $('#contact_ledger_div').html('<div class="alert alert-danger">Unable to load pump operator ledger. Please refresh and try again.</div>');
                    console.error('PetroPD pump operator ledger load failed:', xhr.responseText);
                }
            });
        }

        if ($('#ledger_date_range_new').length) {
            $('#ledger_date_range_new').daterangepicker(dateRangeSettings, function(start, end) {
                $('#ledger_date_range_new').val(
                    start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                );
                loadPetroPdOperatorLedger();
            });

            $('#ledger_date_range_new').data('daterangepicker').setStartDate(moment().startOf('month'));
            $('#ledger_date_range_new').data('daterangepicker').setEndDate(moment().endOf('month'));
            $('#ledger_date_range_new').val(
                moment().startOf('month').format(moment_date_format) + ' - ' + moment().endOf('month').format(moment_date_format)
            );
        }

        loadPetroPdOperatorLedger();

        $('#print_btn').on('click', function() {
            loadPetroPdOperatorLedger('print');
        });

        $('#print_ledger_pdf').on('click', function() {
            loadPetroPdOperatorLedger('pdf');
        });
    @endif
});
</script>
@endsection
