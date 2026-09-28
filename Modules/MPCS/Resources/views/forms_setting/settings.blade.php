
<!-- Content Header (Page header) -->
<section class="content-header">
    <h1> @lang('mpcs::lang.mpcs_forms_setting')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2',
                    'placeholder' => __('petro::lang.all'), 'style' => 'width:100%']); !!}
                </div>
            </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            {!! Form::open(['action' => '\Modules\MPCS\Http\Controllers\FormsSettingController@store', 'method' =>
            'post', 'id' =>
            'form_setting']) !!}
            @component('components.widget', ['class' => 'box-primary'])
            <div class="row text-center">
                <div class="col-md-3">
                    <b> @lang('mpcs::lang.form_number') </b>
                </div>
                <div class="col-md-2">
                    @lang('mpcs::lang.form_starting_number')
                </div>
                <div class="col-md-2">
                    @lang('mpcs::lang.opening_date')
                </div>
                <div class="col-md-3">
                    @lang('mpcs::lang.previous_opening_balance_zero_frequency')
                </div>
                <div class="col-md-2"></div>

            </div>
            <br>
            <br>
            <div class="row text-center">
                <div class="col-md-3" data="{{$settings ?? ''}}">
                    @lang('mpcs::lang.F_9_C')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F9C_sn', !empty($settings->F9C_sn) ? $settings->F9C_sn :
                    null, ['class' => 'form-control will-edit-input', 'id' => 'F9C_sn', 'readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F9C_tdate',!empty($settings->F9C_tdate) ? date('m/d/Y', strtotime($settings->F9C_tdate)) : null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F9C_tdate', 'readonly'])
                    !!}
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-modal" id="form_9c_modal"
                        data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getForm9CSetting')}}"
                        data-container=".form_9c_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F159ABC_form')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F159ABC_form_sn', !empty($settings->F159ABC_form_sn) ? $settings->F159ABC_form_sn :
                    null, ['class' => 'form-control will-edit-input', 'id' => 'F159ABC_form_sn',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F159ABC_form_tdate', !empty($settings->F159ABC_form_tdate) ?
                    date('m/d/Y', strtotime($settings->F159ABC_form_tdate)) : null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' =>
                    'F159ABC_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-modal" id="form_f159abc_modal"
                        data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getForm159ABCSetting')}}"
                        data-container=".form_f159abc_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F16A_form')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F16A_form_sn', !empty($settings->F16A_form_sn) ? $settings->F16A_form_sn : null,
                    ['class' => 'form-control will-edit-input', 'id' => 'F16A_form_sn',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F16A_form_tdate', !empty($settings->F16A_form_tdate) ? date('m/d/Y',
                    strtotime($settings->F16A_form_tdate)) :
                    null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F16A_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-modal" id="form_16a_modal"
                        data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getForm16ASetting')}}"
                        data-container=".form_16a_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F21C_form')
                </div>
                <div class="col-md-2">

                    {!! Form::text('F21C_form_sn', !empty($settings->F21C_form_sn) ? $settings->F21C_form_sn : null,

                    ['class' => 'form-control will-edit-input', 'id' => 'F21C_form_sn',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F21C_form_tdate', !empty($settings->F21C_form_tdate) ? date('m/d/Y',
                    strtotime($settings->F21C_form_tdate)) :
                    null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F21C_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-modal" id="form_16a_modal"
                        data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getForm21CSetting')}}"
                        data-container=".form_16a_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    <b>@lang('mpcs::lang.F14_form')</b>
                </div>
                <div class="col-md-2">
                    <b>@lang('mpcs::lang.form_number')</b>
                </div>
                <div class="col-md-2">
                    <b>@lang('mpcs::lang.opening_date')</b>
                </div>
                <div class="col-md-2">
                    <b>Bill No (Form 14)</b>
                </div>
                <div class="col-md-2"></div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F14_form')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F14_form_sn', !empty($settings->F14_form_sn) ? $settings->F14_form_sn : null,
                    ['class' => 'form-control will-edit-input', 'id' => 'F14_form_sn','readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F14_form_tdate', !empty($settings->F14_form_tdate) ? date('m/d/Y', strtotime(
                    $settings->F14_form_tdate)) :
                    null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F14_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('bill_no_form_14', !empty($settings->bill_no_form_14) ? $settings->bill_no_form_14 : null,
                    ['class' => 'form-control will-edit-input', 'id' => 'bill_no_form_14', 'readonly']) !!}
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-modal" id="form_16a_modal"
                        data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getForm14CSetting')}}"
                        data-container=".form_16a_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F17_form')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F17_form_sn', !empty($settings->F17_form_sn) ? $settings->F17_form_sn : null,
                    ['class' => 'form-control will-edit-input', 'id' => 'F17_form_sn','readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F17_form_tdate', !empty($settings->F17_form_tdate) ? date('m/d/Y', strtotime(
                    $settings->F17_form_tdate)) :
                    null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F17_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-modal" id="form_16a_modal"
                        data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getForm17CSetting')}}"
                        data-container=".form_16a_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F20_form')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F20_form_sn', !empty($settings->F20_form_sn) ? $settings->F20_form_sn : null,
                    ['class' => 'form-control will-edit-input', 'id' => 'F20_form_sn','readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F20_form_tdate', !empty($settings->F20_form_tdate) ? date('m/d/Y', strtotime(
                    $settings->F20_form_tdate)):
                    null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F20_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-primary btn-modal" id="form_16a_modal"
                        data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getForm20CSetting')}}"
                        data-container=".form_16a_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F21_form')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F21_form_sn', !empty($settings->F21_form_sn) ? $settings->F21_form_sn : null,
                    ['class' => 'form-control will-edit-input', 'id' => 'F21_form_sn','readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F21_form_tdate', !empty($settings->F21_form_tdate) ? date('m/d/Y',
                    strtotime($settings->F21_form_tdate)):
                    null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F21_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-modal" id="form_f21_modal"
                            data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getFormF21Setting')}}"
                            data-container=".form_f22_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row text-center">
                <div class="col-md-3">
                    @lang('mpcs::lang.F22_form')
                </div>
                <div class="col-md-2">
                    {!! Form::text('F22_form_sn', !empty($settings->F22_form_sn) ? $settings->F22_form_sn : null,
                    ['class' => 'form-control will-edit-input', 'id' => 'F22_form_sn','readonly']) !!}
                </div>
                <div class="col-md-2">
                    {!! Form::text('F22_form_tdate', !empty($settings->F22_form_tdate) ? date('m/d/Y',
                    strtotime($settings->F22_form_tdate)):
                    null, ['class' => 'form-control will-edit-input opening-form-datepicker', 'id' => 'F22_form_tdate',
                    'readonly']) !!}
                </div>
                <div class="col-md-2">
                        <button type="button" class="btn btn-primary btn-modal" id="form_f22_modal"
                            data-href="{{action('\Modules\MPCS\Http\Controllers\FormsSettingController@getFormF22Setting')}}"
                            data-container=".form_f22_modal"> @lang('mpcs::lang.click_to_new') </button>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-4" style="margin-top: 5px;">
                    <b> @lang('mpcs::lang.current_stock_aa_onstocktaking') </b>
                </div>
                <div class="col-md-2">
                    {!! Form::select('current_stock_aa_onstocktaking', ['1' => 'Yes', '0' => 'No'], !empty($settings) ? $settings->current_stock_aa_onstocktaking : null, ['class' => 'form-control',
                    'placeholder' => __('mpcs::lang.please_select')]) !!}
                </div>
            </div>
            <br>
            {{-- Manual entry - Card Accounts (Previous Day) --}}
            @if(!empty($card_accounts) && count($card_accounts) > 0)
                <div class="row" style="margin-top: 20px;">
                    <div class="col-md-12">
                        <hr style="border-top: 2px solid #0099ff;">
                        <h4 style="color:#0099ff; font-weight: bold; margin-bottom:15px;">
                            Previous Day (Card Accounts)
                        </h4>
                    </div>
                </div>
                <div class="row">
                    @foreach($card_accounts as $account)
                        <div class="col-md-6" style="margin-bottom:12px;">
                            <div class="form-group">
                                <label class="control-label">{{ $account->name }}</label>
                                <input type="text"
                                       name="pre_day_card_accounts[{{ $account->id }}]"
                                       class="form-control input-sm input_number will-edit-input"
                                       placeholder="0.00"
                                       value="{{ $card_manual[$account->id] ?? '' }}"
                                       readonly>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="row">
                <div class="col-md-12">
                    <button class="btn btn-primary btn-sm pull-right" style="margin-left: 10px;"
                        type="submit">@lang('mpcs::lang.save')</button>
                    <button class="btn btn-danger  btn-sm pull-right" id="edit-form" type="button">@lang('mpcs::lang.edit')</button>
                </div>

            </div>
            @endcomponent
            {!! Form::close() !!}
        </div>
    </div>
  
</section>

@section('javascript')
<script type="text/javascript">
    
    $(document).on('click', '#edit-form', (e) => {
        e.preventDefault();
        $('.will-edit-input').removeAttr('readonly');
        $('.opening-form-datepicker').datepicker();
    })
</script>
@endsection
<!-- /.content -->