@extends('layouts.app')

@section('title', __('petrogeneral::lang.petro_settings'))



@section('content')

<!-- Content Header (Page header) -->

@php
                    
    $business_id = request()
        ->session()
        ->get('user.business_id');
    
    $pacakge_details = [];
        
    $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
    }

@endphp

<section class="content-header main-content-inner">
    <div class="row">

        <div class="col-md-12 dip_tab">

            <div class="settlement_tabs">

                <ul class="nav nav-tabs">

                    <li class="active" style="margin-left: 20px;">

                        <a style="font-size:13px;" href="#day_end_settlement" class="" data-toggle="tab">

                            <i class="fa fa-file-o"></i> <strong>@lang('petrogeneral::lang.day_end_settlement')</strong>

                        </a>

                    </li>


                   

                </ul>

            </div>

        </div>

    </div>

    <div class="tab-content">

        <div class="tab-pane active" id="petro_settings">

            @if(!empty($message)) {!! $message !!} @endif

            @include('petrogeneral::petro_settings.partials.day_end_settlement')

        </div>
       

    </div>



    <div class="modal fade dip_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

</section>



@endsection

@section('javascript')

<script type="text/javascript">

    $(document).ready( function(){
       
        $(document).on('click', '.edit_dip', function (e) {
             e.preventDefault()
            var actionuRL = $(this).data('href');
            $('.dip_modal').load(actionuRL, function() {
                $(this).modal('show');
            });
        });
    
    
        
        $('.dip_modal').on('show.bs.modal', function () {
          $(this).data('bs.modal').options.backdrop = 'static';
          $(this).data('bs.modal').options.keyboard = false;
        });




    if ($('#date_range').length == 1) {

        $('#date_range').daterangepicker(dateRangeSettings, function(start, end) {

            $('#date_range').val(

                start.format(moment_date_format) + ' - ' + end.format(moment_date_format)

            );
            day_end_settlement_table.ajax.reload();

        });

        $('#date_range').on('cancel.daterangepicker', function(ev, picker) {

            $('#date_range').val('');

        });

        $('#date_range')

            .data('daterangepicker')

            .setStartDate(moment().startOf('month'));

        $('#date_range')

            .data('daterangepicker')

            .setEndDate(moment().endOf('month'));

    }
        
        var day_end_settlement_table = $('#day_end_settlement_table').DataTable({
            processing: true,
            serverSide: true,
            order: [[2, 'desc']],
            ajax: {
                url: '{{ route('petrogeneral.day_end_settlement.index') }}',
                data: function(d) {
                    var picker = $('input#date_range').data('daterangepicker');
                    if (picker) {
                        d.start_date = picker.startDate.format('YYYY-MM-DD');
                        d.end_date = picker.endDate.format('YYYY-MM-DD');
                    }
                }
            },
            columns: [
                { data: 'action', orderable: false, searchable: false },
                { data: 'created_at', name: 'day_ends.created_at' },
                { data: 'day_end_date', name: 'day_ends.day_end_date' },
                { data: 'pumps', orderable: false, searchable: false },
                { data: 'sold_pumps', orderable: false, searchable: false },
                { data: 'user_added', name: 'created.username' },
                { data: 'user_editted', name: 'editted.username' },
            ]
        
    

    });
    
});

    {{--
        The flashed status is now shown on every Petro General page by
        \Modules\PetroGeneral\Http\Middleware\RenderPetroGeneralStatusMessage,
        so rendering it again here would toast the same message twice.

        Note the block removed from here only ever showed the FAILURE case -
        'success == false' - so a successful save on this screen was silent too.
        The middleware shows both outcomes.
    --}}

</script>

@endsection