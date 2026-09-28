{{--
    Tank Transfers tab for Petro General / Tank Management.

    Markup only - every table on this page has its JavaScript in the parent
    index.blade.php @section('javascript'), and this follows that convention
    rather than introducing an inline <script> the other tabs do not use.

    All three collections it needs (business_locations, tank_numbers, products)
    are already passed to this page by FuelTankController@index, so no controller
    change is required.

    Field ids are prefixed tt_ so they cannot collide with the Fuel Tanks tab,
    which has its own Location and Fuel Tank Number filters on the same page.
--}}
<div class="tab-pane" id="tank_transfers_list">

    <section class="content">

        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                {{--
                    The columns are wrapped in an element with overflow:hidden so the
                    panel contains its floated children. Without it the panel
                    collapses to zero height and the inputs render underneath it,
                    behind the next box - which is what happened on the standalone
                    List Tank Transfer page.
                --}}
                <div class="row" style="overflow: hidden !important; width: 100% !important; margin-left: 0 !important; margin-right: 0 !important;">

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('tt_date_range', __('report.date_range') . ':') !!}
                            {!! Form::text('tt_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'tt_date_range', 'readonly']); !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('tt_location_id', __('petrogeneral::lang.location') . ':') !!}
                            {!! Form::select('tt_location_id', $business_locations, null, ['class' => 'form-control select2', 'id' => 'tt_location_id', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('tt_from_tank', __('petrogeneral::lang.from_tank') . ':') !!}
                            {!! Form::select('tt_from_tank', $tank_numbers, null, ['class' => 'form-control select2', 'id' => 'tt_from_tank', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('tt_to_tank', __('petrogeneral::lang.to_tank') . ':') !!}
                            {!! Form::select('tt_to_tank', $tank_numbers, null, ['class' => 'form-control select2', 'id' => 'tt_to_tank', 'placeholder' => __('petrogeneral::lang.all'), 'style' => 'width:100%']); !!}
                        </div>
                    </div>

                    <div class="clearfix"></div>
                </div>
                @endcomponent
            </div>
        </div>

        @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-primary btn-modal add_tank_transfer_btn"
                data-href="{{ url('/petro-general/tank-management') }}?petrogeneral_embedded_tank_transfer_create=1"
                data-container=".tank_transfer_modal">
                <i class="fa fa-plus"></i> @lang('messages.add')</button>
        </div>
        @endslot

        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="tt_transfers_table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>@lang('petrogeneral::lang.date')</th>
                        <th>@lang('petrogeneral::lang.location')</th>
                        <th>@lang('petrogeneral::lang.transfer_no')</th>
                        <th>@lang('petrogeneral::lang.from_tank')</th>
                        <th>@lang('petrogeneral::lang.from_qty')</th>
                        <th>@lang('petrogeneral::lang.to_tank')</th>
                        <th>@lang('petrogeneral::lang.to_qty')</th>
                        <th>@lang('petrogeneral::lang.product')</th>
                        <th>@lang('petrogeneral::lang.transfer_qty')</th>
                        <th>@lang('petrogeneral::lang.user_added')</th>
                    </tr>
                </thead>
            </table>
        </div>
        @endcomponent

    </section>

    <div class="modal fade tank_transfer_modal" role="dialog" aria-labelledby="gridSystemModalLabel"></div>

</div>
