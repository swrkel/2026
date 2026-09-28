@extends('layouts.app')
@section('title', __('membership::lang.member_ledger'))

@section('content')
    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h4 class="page-title pull-left">@lang('membership::lang.member_ledger')</h4>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="#">@lang('membership::lang.member_ledger')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box'])
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('ledger_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('ledger_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly', 'id' => 'ledger_date_range_new']); !!}
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('ledger_transaction_type', __('lang_v1.transaction_type') . ':') !!}
                                {!! Form::select('ledger_transaction_type', ['debit' => 'Debit', 'credit' => 'Credit'], null, ['placeholder' => __('lang_v1.please_select'), 'style' => 'width: 100%', 'class' => 'form-control select2', 'readonly', 'id' => 'ledger_transaction_type']); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('ledger_transaction_amount', __('lang_v1.transaction_amount') . ':') !!}
                                {!! Form::select('ledger_transaction_amount', $transaction_amounts ,null, ['placeholder' => __('lang_v1.please_select'), 'style' => 'width: 100%', 'class' => 'form-control select2', 'readonly', 'id' => 'ledger_transaction_amount']); !!}
                            </div>
                        </div>
                    </div>
                    @if (!empty($contact))
                        <div class="row">
                            <div class="col-md-9 text-right">
                                <button data-href="{{action('ContactController@getLedger')}}?contact_id={{$contact->id}}&action=print"
                                        class="btn btn-default btn-xs" id="print_btn"><i class="fa fa-print"></i></button>
                                <button data-href="{{action('ContactController@getLedger')}}?contact_id={{$contact->id}}&action=pdf"
                                        class="btn btn-default btn-xs" id="print_ledger_pdf"><i
                                            class="fa fa-file-pdf-o "></i></button>

                                <button type="button" class="btn btn-default btn-xs" id="send_ledger"><i
                                            class="fa fa-envelope"></i></button>
                            </div>
                        </div>
                    @endif
                @endcomponent
            </div>
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box'])
                    <div id="member_ledger_div"></div>
                @endcomponent
            </div>
        </div>
    </section>
@endsection
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function () {
            //Date range as a button
            $('#ledger_date_range_new').daterangepicker(
                dateRangeSettings,
                function (start, end) {
                    $('#ledger_date_range_new').val(
                        start.format(moment_date_format) +
                        ' ~ ' +
                        end.format(moment_date_format)
                    );
                    getMemberLedger();
                }
            );
            $('#ledger_date_range_new').on('cancel.daterangepicker', function (ev, picker) {
                $('#ledger_date_range_new').val('');
                getMemberLedger();
            });

            $('#ledger_transaction_type, #ledger_transaction_amount').change(function(){
                getMemberLedger();
            });

            getMemberLedger();

            function getMemberLedger() {
                var start_date = '';
                var end_date = '';
                var transaction_type = $('select#ledger_transaction_type').val();
                var transaction_amount = $('select#ledger_transaction_amount').val();
                if ($('#ledger_date_range_new').val()) {
                    start_date = $('#ledger_date_range_new').data('daterangepicker').startDate.format('YYYY-MM-DD');
                    end_date = $('#ledger_date_range_new').data('daterangepicker').endDate.format('YYYY-MM-DD');
                }
                $.ajax({
                    url: '/membership/members/{{$member->id ?? 0 }}/ledger-detail?start_date=' + start_date +
                        '&transaction_type=' + transaction_type + '&end_date=' + end_date + '&transaction_amount=' +
                        transaction_amount,
                    dataType: 'json',
                    success: function(result) {
                        var amounts = result.amounts;
                        $('select#ledger_transaction_amount').empty().append(
                            "<option value=''>Please select</option>");
                        $.each(amounts, function(key, value) {
                            $('select#ledger_transaction_amount').append("<option value='" + value + "'>" +
                                __number_f(value) + "</option>");
                        });

                        $('#member_ledger_div').html(result.html);

                        $('#ledger_table').DataTable({
                            searching: true,
                            ordering: true,
                            paging: true,
                            order: [
                                [0, 'desc']
                            ],
                        });
                    },
                });
            }
        });
    </script>
@endsection
