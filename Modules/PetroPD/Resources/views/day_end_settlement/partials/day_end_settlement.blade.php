<!-- Main content -->

<section class="content main-content-inner">

    <div class="row">

        <div class="col-md-12">

            @component('components.filters', ['title' => __('report.filters')])

            <div class="col-md-3">

                <div class="form-group">

                    {!! Form::label('daily_date_range', __('report.date_range') . ':') !!}

                    {!! Form::text('date_range', @format_date('first day of this month') . ' ~ ' .

                    @format_date('last

                    day of this month') , ['placeholder' => __('lang_v1.select_a_date_range'), 'class' =>

                    'form-control', 'id' => 'date_range', 'readonly']); !!}

                </div>

            </div>

            @endcomponent

        </div>

    </div>



    @component('components.widget', ['class' => 'box-primary', 'title' => __('petropd::lang.day_end_settlement')])

    @slot('tool')
    
    {{--
     | S678: add_day_end_settlement is not offered on any Role screen - the
     | Petro PD section grants petro_pd_day_end_settlement.edit instead. The
     | button was therefore invisible to every role that had been given Day End
     | Settlement rights. Accept either name rather than renaming, so roles that
     | already hold the old one keep it.
    --}}
    @canany(['add_day_end_settlement', 'petro_pd_day_end_settlement.edit'])
    <button type="button" class="btn  btn-primary btn-modal pull-right"

    data-href="{{ route('petropd.day-end-settlements.create') }}"

    data-container=".pd_day_end_modal">

    <i class="fa fa-plus"></i> @lang('petropd::lang.add')</button>
    @endcanany

    @endslot

    <div class="table-responsive">

                <table class="table table-bordered table-striped" id="pd_day_end_settlement_table" width="100%">

                    <thead>

                        <tr>
                            
                            <th>@lang('petropd::lang.action')</th>

                            <th>@lang('petropd::lang.time_and_date')</th>
                            
                            <th>@lang('petropd::lang.day_end_date')</th>

                            <th>@lang('petropd::lang.no_operation')</th>
                            
                            <th>@lang('petropd::lang.pumps_in_settlement')</th>

                            <th>@lang('petropd::lang.user_added')</th>

                            <th>@lang('petropd::lang.user_editted')</th>

                        </tr>

                    </thead>

                    
                   
                   

                </table>

            </div>

    @endcomponent



    <div class="modal fade settlement_modal" role="dialog" aria-labelledby="gridSystemModalLabel">

    </div>

</section>

<!-- /.content -->