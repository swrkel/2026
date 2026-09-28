@if(!empty($is_add_leads_page))
@extends('layouts.app')
@section('title', __('leads::lang.add_leads'))

@section('content')
<section class="content-header">
    <h1>@lang('leads::lang.add_leads')</h1>
</section>

<section class="content leads-add-page">
    @if(session('status'))
        @php $status = session('status'); @endphp
        <div class="alert alert-{{ !empty($status['success']) ? 'success' : 'danger' }} alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            {{ $status['msg'] ?? '' }}
        </div>
    @endif

    {!! Form::open(['url' => route('leads.add-leads.store'), 'method' => 'post', 'id' => 'leads_form', 'enctype' => 'multipart/form-data']) !!}

    <div class="box box-primary leads-professional-card">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-plus-circle"></i> @lang('leads::lang.add_leads')</h3>
            <div class="box-tools pull-right">
                <a href="{{ action('\\Modules\Leads\Http\Controllers\LeadsController@index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> @lang('messages.back')
                </a>
            </div>
        </div>
        <div class="box-body">
            <div class="lead-section-title"><i class="fa fa-calendar"></i> Lead Date & Primary Details</div>
            <div class="row leads-compact-row">
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('date', __('leads::lang.date') . ':*') !!}
                        {!! Form::text('date', date('m/d/Y'), ['class' => 'form-control', 'required', 'readonly' => true, 'tabindex' => '-1', 'placeholder' => __('leads::lang.date'), 'id' => 'leads_date']) !!}
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('time', __('leads::lang.time') . ':*') !!}
                        {!! Form::text('time', date('H:i:s'), ['class' => 'form-control', 'required', 'readonly' => true, 'tabindex' => '-1', 'placeholder' => __('leads::lang.time'), 'id' => 'leads_time']) !!}
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('transaction_date', 'Transaction Date:*') !!}
                        {!! Form::text('transaction_date', date('m/d/Y'), ['class' => 'form-control date leads-transaction-date', 'required', 'placeholder' => 'Transaction Date', 'id' => 'transaction_date']) !!}
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('mobile_no_1', __('leads::lang.mobile_no_1') . ':*') !!}
                        {!! Form::text('mobile_no_1', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('leads::lang.mobile_no_1'), 'id' => 'mobile_no_1']) !!}
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('sector', __('leads::lang.sector') . ':*') !!}
                        {!! Form::select('sector', ['private' => __('leads::lang.private'), 'government' => __('leads::lang.government')], null, ['class' => 'form-control select2', 'required', 'placeholder' => __('leads::lang.please_select'), 'id' => 'sector']) !!}
                    </div>
                </div>
            </div>

            <div class="lead-section-title"><i class="fa fa-building"></i> Organization Details</div>
            <div class="row">
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('category_id', __('leads::lang.category') . ':*') !!}
                        {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('leads::lang.please_select'), 'id' => 'category_id']) !!}
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('main_organization', __('leads::lang.main_organization') . ':*') !!}
                        {!! Form::text('main_organization', null, ['class' => 'form-control', 'required', 'placeholder' => __('leads::lang.main_organization'), 'id' => 'main_organization']) !!}
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('business', __('leads::lang.business') . ':*') !!}
                        {!! Form::text('business', null, ['class' => 'form-control', 'required', 'placeholder' => __('leads::lang.business'), 'id' => 'business']) !!}
                    </div>
                </div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('address', __('leads::lang.address') . ':*') !!}
                        {!! Form::text('address', null, ['class' => 'form-control', 'required', 'placeholder' => __('leads::lang.address'), 'id' => 'address']) !!}
                    </div>
                </div>
            </div>

            <div class="lead-section-title"><i class="fa fa-map-marker"></i> Location Details</div>
            <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('country', __('leads::lang.country') . ':*') !!}
                        {!! Form::select('country', $countries, null, ['class' => 'form-control select2', 'required', 'placeholder' => __('leads::lang.please_select'), 'id' => 'country']) !!}
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('district', __('leads::lang.district') . ':*') !!}
                        {!! Form::select('district', $districts->pluck('name', 'name'), null, ['class' => 'form-control select2', 'required', 'placeholder' => __('leads::lang.please_select'), 'id' => 'district']) !!}
                    </div>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                    <div class="form-group">
                        {!! Form::label('town', __('leads::lang.town') . ':*') !!}
                        {!! Form::select('town', !empty($towns) ? $towns->pluck('name', 'name') : [], null, ['class' => 'form-control select2', 'required', 'placeholder' => __('leads::lang.please_select'), 'id' => 'town']) !!}
                    </div>
                </div>
            </div>

            <div class="lead-section-title"><i class="fa fa-phone"></i> Contact Details</div>
            <div class="row">
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12"><div class="form-group">{!! Form::label('mobile_no_2', __('leads::lang.mobile_no_2') . ':*') !!}{!! Form::text('mobile_no_2', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('leads::lang.mobile_no_2'), 'id' => 'mobile_no_2']) !!}</div></div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12"><div class="form-group">{!! Form::label('mobile_no_3', __('leads::lang.mobile_no_3') . ':*') !!}{!! Form::text('mobile_no_3', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('leads::lang.mobile_no_3'), 'id' => 'mobile_no_3']) !!}</div></div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12"><div class="form-group">{!! Form::label('land_number', __('leads::lang.land_number') . ':*') !!}{!! Form::text('land_number', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('leads::lang.land_number'), 'id' => 'land_number']) !!}</div></div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12"><div class="form-group">{!! Form::label('email', __('leads::lang.email') . ':*') !!}{!! Form::text('email', null, ['class' => 'form-control', 'required', 'placeholder' => __('leads::lang.email'), 'id' => 'email']) !!}</div></div>
            </div>

            <div class="lead-section-title"><i class="fa fa-comment"></i> Follow-up Details</div>
            <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 leads-client-response-col"><div class="form-group">{!! Form::label('client_response', __('leads::lang.client_resp') . ':*') !!}{!! Form::textarea('client_response', null, ['class' => 'form-control leads-client-response', 'required', 'placeholder' => __('leads::lang.client_resp'), 'id' => 'client_response', 'rows' => 3]) !!}</div></div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12"><div class="form-group">{!! Form::label('follow_up_date', __('leads::lang.follow_up_date') . ':*') !!}{!! Form::text('follow_up_date', null, ['class' => 'form-control date leads-follow-up-date', 'required', 'readonly' => true, 'placeholder' => __('leads::lang.follow_up_date'), 'id' => 'leads_follow_up_date']) !!}</div></div>
                <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12"><div class="form-group">{!! Form::label('label_id', __('leads::lang.labels')) !!}{!! Form::select('label_id', $labels, null, ['class' => 'form-control select2', 'placeholder' => __('leads::lang.please_select'), 'id' => 'label_id']) !!}</div></div>
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><div class="form-group">{!! Form::label('note', __('brand.note')) !!}{!! Form::textarea('note', null, ['class' => 'form-control', 'placeholder' => __('brand.note'), 'rows' => 4]) !!}</div></div>
            </div>
        </div>
        <div class="box-footer text-right">
            <a href="{{ action('\\Modules\Leads\Http\Controllers\LeadsController@index') }}" class="btn btn-default">@lang('messages.cancel')</a>
            <button type="submit" class="btn btn-primary" id="save_leads_btn"><i class="fa fa-save"></i> @lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}

    <div class="modal fade" id="getCodeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header"><button type="button" class="close" id="md2" data-dismiss="modal" aria-hidden="true">&times;</button><h4 class="modal-title">Existing Lead Found</h4></div>
                <div class="modal-body" id="getCode"></div>
            </div>
        </div>
    </div>
</section>

<style>
.leads-add-page .leads-professional-card{border-radius:10px; box-shadow:0 4px 18px rgba(0,0,0,.08); border-top:3px solid #3c8dbc;}
.leads-add-page .box-header{padding:14px 18px;}
.leads-add-page .box-title{font-weight:700; color:#26394d;}
.leads-add-page .box-body{padding:18px 20px;}
.leads-add-page .row{margin-left:-10px; margin-right:-10px;}
.leads-add-page .row:before,.leads-add-page .row:after{content:" ";display:table;}
.leads-add-page .row:after{clear:both;}
.leads-add-page [class*="col-"]{padding-left:10px; padding-right:10px;}
.leads-add-page .lead-section-title{font-weight:700; color:#2f4050; border-left:4px solid #3c8dbc; background:#f7f9fb; padding:9px 12px; margin:8px 0 14px; border-radius:4px;}
.leads-add-page .form-group label{font-weight:600; color:#455a64;}
.leads-add-page .form-control{height:38px; border-radius:7px; border:1px solid #d8e0e7;}
.leads-add-page .form-control[readonly]{background:#f4f6f8; cursor:not-allowed; color:#52616f;}
.leads-add-page textarea.form-control{height:auto;}
.leads-add-page .leads-client-response{min-height:92px; resize:vertical;}
.leads-add-page .leads-client-response-col textarea{min-height:92px;}
.leads-add-page .select2-container .select2-selection--single{height:38px!important; border-radius:7px!important; border:1px solid #d8e0e7!important;}
.leads-add-page .select2-container--default .select2-selection--single .select2-selection__rendered{line-height:36px!important;}
.leads-add-page .select2-container--default .select2-selection--single .select2-selection__arrow{height:36px!important;}
.leads-add-page .btn{border-radius:7px; padding:8px 16px; font-weight:600;}
@media (max-width: 1199px){.leads-add-page .form-control{min-width:0;}}
@media (max-width: 767px){.leads-add-page .box-tools{float:none!important; margin-top:10px}.leads-add-page .box-footer .btn{width:100%; margin-bottom:8px}.leads-add-page .box-body{padding:14px 12px}.leads-add-page .lead-section-title{font-size:14px}}
</style>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    if (!$('#leads_time').val() || $('#leads_time').val() === 'Time') {
        var now = new Date();
        var hh = String(now.getHours()).padStart(2, '0');
        var mm = String(now.getMinutes()).padStart(2, '0');
        var ss = String(now.getSeconds()).padStart(2, '0');
        $('#leads_time').val(hh + ':' + mm + ':' + ss);
    }
    $('#leads_date, #leads_time').prop('readonly', true).attr('tabindex', '-1');
    $('#transaction_date').prop('readonly', true);
    $('#transaction_date').prop('readonly', true);
    if ($.fn.select2) { $('.select2').select2(); }
    if ($.fn.datepicker) { $('.date').datepicker({ format: 'mm/dd/yyyy', autoclose: true, todayHighlight: true }); }
    $('#leads_date').attr('readonly', true);
    $('#leads_time').attr('readonly', true);

    function genModalContent(data){
        return `<div class="container-fluid"><ul class="list-group list-group-flush">
            <li class="list-group-item"><b>Date Created:</b> ${data.created_at || ''}</li>
            <li class="list-group-item"><b>Mobile No 1:</b> ${data.mobile_no_1 || ''}</li>
            <li class="list-group-item"><b>Sector:</b> ${data.sector || ''}</li>
            <li class="list-group-item"><b>Category:</b> ${data.category_id || ''}</li>
            <li class="list-group-item"><b>Main Organization:</b> ${data.main_organization || ''}</li>
            <li class="list-group-item"><b>Email:</b> ${data.email || ''}</li>
            <li class="list-group-item"><b>District:</b> ${data.district || ''}</li>
            <li class="list-group-item"><b>Town:</b> ${data.town || ''}</li>
            <li class="list-group-item"><b>Follow Up Date:</b> ${data.follow_up_date || ''}</li>
        </ul></div>`;
    }

    function checkDuplicateMobile(selector) {
        var mobile = $(selector).val();
        if (!mobile) { return; }
        $.ajax({
            url: '{{ route('leads.ajax_mobile') }}',
            type: 'POST',
            data: { postData: mobile },
            success: function(data) {
                if (data) {
                    $('#save_leads_btn').prop('disabled', true);
                    $('#getCode').empty().append(genModalContent(data));
                    $('#getCodeModal').modal('show');
                } else {
                    $('#save_leads_btn').prop('disabled', false);
                }
            }
        });
    }
    $('#mobile_no_1').on('blur', function(){ checkDuplicateMobile(this); });
    $('#mobile_no_2').on('blur', function(){ checkDuplicateMobile(this); });
    $('#md2').on('click', function(){ $('#getCode').empty(); $('#save_leads_btn').prop('disabled', false); });

    function leadsPopulateTownDropdown(data) {
        var $town = $('#town');
        $town.empty().append('<option value="">{{ __('leads::lang.please_select') }}</option>');
        if (data && data.length) {
            $.each(data, function(i, row){
                if (row && row.name) {
                    $town.append($('<option></option>').attr('value', row.name).text(row.name));
                }
            });
        }
        $town.trigger('change.select2');
    }

    $('#district').on('change', function(){
        $.ajax({
            url: '{{ route('leads.ajax_town') }}',
            type: 'POST',
            data: {postData: $(this).val()},
            success: leadsPopulateTownDropdown,
            error: function(){ leadsPopulateTownDropdown([]); }
        });
    });
    $('#country').on('change', function(){
        $.ajax({url: '{{ route('leads.ajax_district') }}', type: 'POST', data: {postData: $(this).val()}, success: function(data){
            $('#district').empty().append('<option value="">{{ __('leads::lang.please_select') }}</option>');
            if(data){ $.each(data, function(i, row){ $('#district').append($('<option></option>').attr('value', row.name).text(row.name)); }); }
            $('#district').trigger('change.select2');
            leadsPopulateTownDropdown([]);
        }});
    });
});
</script>
@endsection
@else
<div class="modal-dialog leads-add-modal-dialog" role="document">
    <div class="modal-content leads-add-modal-content">

        <style>
            .leads-add-modal-dialog { width: 78%; max-width: 1180px; }
            .leads-add-modal-body { padding: 18px 22px 6px; display: grid !important; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px 18px; align-items: start; margin-left: 0; margin-right: 0; }
            .leads-modal-grid { display: flex; flex-wrap: wrap; align-items: flex-start; margin-left: -10px; margin-right: -10px; }
            .leads-modal-grid > [class*="col-"], .leads-add-modal-body > [class*="col-"] { float: none !important; width: auto !important; max-width: none !important; padding-left: 0; padding-right: 0; min-height: auto; }
            .leads-modal-grid .form-group, .leads-add-modal-body > [class*="col-"] .form-group { margin-bottom: 14px; }
            .leads-modal-grid label, .leads-add-modal-body label { font-weight: 600; color: #455a64; }
            .leads-modal-grid .form-control, .leads-add-modal-body .form-control { height: 38px; border-radius: 7px; border: 1px solid #d8e0e7; }
            .leads-modal-grid .form-control[readonly], .leads-add-modal-body .form-control[readonly] { background: #f4f6f8; cursor: not-allowed; color: #52616f; }
            .leads-modal-grid textarea.form-control, .leads-add-modal-body textarea.form-control { height: auto; }
            .leads-add-modal-body .leads-client-response { min-height: 92px; resize: vertical; }
            .leads-add-modal-body .leads-client-response-col { grid-column: span 2; }
            .leads-add-modal-body > [class*="col-"] { min-height: 86px; }
            @media (max-width: 991px) { .leads-add-modal-dialog { width: 95%; } }
            @media (max-width: 991px) { .leads-add-modal-body { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
            @media (max-width: 767px) { .leads-add-modal-dialog { width: 98%; margin: 10px auto; } .leads-add-modal-body { grid-template-columns: 1fr; } .leads-add-modal-body .leads-client-response-col { grid-column: span 1; } .leads-modal-grid > [class*="col-"], .leads-add-modal-body > [class*="col-"] { min-height: auto; } }
            .select2 {
                width: 100% !important;
            }
        </style>
        {!! Form::open(['url' => !empty($is_add_leads_page) ? route('leads.add-leads.store') : action('\Modules\Leads\Http\Controllers\LeadsController@store'), 'method' =>
        'post', 'id' => 'leads_form', 'enctype' => 'multipart/form-data' ])
        !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang( 'leads::lang.add_leads' )</h4>
        </div>

        <div class="modal-body leads-add-modal-body">
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('date', __( 'leads::lang.date' )) !!}
                    {!! Form::text('date', date('m/d/Y'), ['class' => 'form-control', 'required', 'readonly' => true, 'tabindex' => '-1', 'placeholder' => __(
                    'leads::lang.date' ),
                    'id' => 'leads_date']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('time', __( 'leads::lang.time' )) !!}
                    {!! Form::text('time', date('H:i:s'), ['class' => 'form-control', 'required', 'readonly' => true, 'tabindex' => '-1', 'placeholder' => __(
                    'leads::lang.time' ), 'id' => 'leads_time']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('transaction_date', 'Transaction Date') !!}
                    {!! Form::text('transaction_date', date('m/d/Y'), ['class' => 'form-control date leads-transaction-date', 'required', 'placeholder' => 'Transaction Date', 'id' => 'transaction_date']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('mobile_no_1', __( 'leads::lang.mobile_no_1' )) !!}
                    {!! Form::text('mobile_no_1', null, ['class' => 'form-control input_number', 'required','placeholder' => __(
                    'leads::lang.mobile_no_1' ),
                    'id' => 'mobile_no_1']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('sector', __( 'leads::lang.sector' )) !!}
                    {!! Form::select('sector', ['private' => __('leads::lang.private'), 'government' =>
                    __('leads::lang.government')], null, ['class' => 'form-control select2',
                    'required',
                    'placeholder' => __(
                    'leads::lang.please_select' ), 'id' => 'sector']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('category_id', __( 'leads::lang.category' )) !!}
                    {!! Form::select('category_id', $categories, null, ['class' => 'form-control select2',
                    'required',
                    'placeholder' => __(
                    'leads::lang.please_select' ), 'id' => 'category_id']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('main_organization', __( 'leads::lang.main_organization' )) !!}
                    {!! Form::text('main_organization', null, ['class' => 'form-control', 'placeholder' => __(
                    'leads::lang.main_organization' ), 'required', 
                    'id' => 'main_organization']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('business', __( 'leads::lang.business' )) !!}
                    {!! Form::text('business', null, ['class' => 'form-control', 'placeholder' => __(
                    'leads::lang.business' ), 'required',
                    'id' => 'business']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('address', __( 'leads::lang.address' )) !!}
                    {!! Form::text('address', null, ['class' => 'form-control', 'placeholder' => __(
                    'leads::lang.address' ), 'required', 
                    'id' => 'address']);
                    !!}
                </div>
            </div>
            
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('country', __( 'leads::lang.country' )) !!}
                    {!! Form::select('country', $countries, null, ['class' => 'form-control select2',
                    'required',
                    'placeholder' => __(
                    'leads::lang.please_select' ), 'id' => 'country']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('district', __( 'leads::lang.district' )) !!}
                    {!! Form::select('district', $districts->pluck('name', 'name'), null, ['class' => 'form-control select2',
                    'required',
                    'placeholder' => __(
                    'leads::lang.please_select' ), 'id' => 'district']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('town', __( 'leads::lang.town' )) !!}
                    {!! Form::select('town', !empty($towns) ? $towns->pluck('name', 'name') : [], null, ['class' => 'form-control select2',
                    'required',
                    'placeholder' => __(
                    'leads::lang.please_select' ), 'id' => 'town']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('mobile_no_2', __( 'leads::lang.mobile_no_2' )) !!}
                    {!! Form::text('mobile_no_2', null, ['class' => 'form-control input_number', 'placeholder' => __(
                    'leads::lang.mobile_no_2' ), 'required', 
                    'id' => 'mobile_no_2']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('mobile_no_3', __( 'leads::lang.mobile_no_3' )) !!}
                    {!! Form::text('mobile_no_3', null, ['class' => 'form-control input_number', 'placeholder' => __(
                    'leads::lang.mobile_no_3' ), 'required',
                    'id' => 'mobile_no_3']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('land_number', __( 'leads::lang.land_number' )) !!}
                    {!! Form::text('land_number', null, ['class' => 'form-control input_number', 'placeholder' => __(
                    'leads::lang.land_number' ), 'required',
                    'id' => 'land_number']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('email', __( 'leads::lang.email' )) !!}
                    {!! Form::text('email', null, ['class' => 'form-control', 'placeholder' => __(
                    'leads::lang.email' ), 'required',
                    'id' => 'email']);
                    !!}
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 leads-client-response-col">
                <div class="form-group">
                    {!! Form::label('client_response', __( 'leads::lang.client_resp' )) !!}
                    {!! Form::textarea('client_response', null, ['class' => 'form-control leads-client-response', 'placeholder' => __(
                    'leads::lang.client_resp' ), 'required',
                    'id' => 'client_response', 'rows' => 3]);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('follow_up_date', __( 'leads::lang.follow_up_date' )) !!}
                    {!! Form::text('follow_up_date', null, ['class' => 'form-control date leads-follow-up-date', 'required', 'readonly' => true, 'placeholder' => __(
                    'leads::lang.follow_up_date' ),
                    'id' => 'leads_follow_up_date']);
                    !!}
                </div>
            </div>
            <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                <div class="form-group">
                    {!! Form::label('label_id', __( 'leads::lang.labels' )) !!}
                    {!! Form::select('label_id', $labels, null, ['class' => 'form-control select2',
                    'placeholder' => __(
                    'leads::lang.please_select' ), 'id' => 'label_id']);
                    !!}
                </div>
            </div>
            
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12"><div class="form-group">
                {!! Form::label('note', __( 'brand.note' )) !!}
                {!! Form::textarea('note', null, ['class' => 'form-control', 'placeholder' => __( 'brand.note' ), 'rows' => 4]);
                !!}
            </div></div>
        </div>
        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="save_leads_btn" >@lang( 'messages.save' )</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
        </div>

        {!! Form::close() !!}

    </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

 <!-- Modal -->
 <div class="modal fade" id="getCodeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog modal-sm">
      <div class="modal-content">
       <div class="modal-header">
         <button type="button" class="close" id="md2"><span aria-hidden="true">&times;</span></button>
         <h4 class="modal-title" id="myModalLabel"> Your Number Already Exist! </h4>
       </div>
       <div class="modal-body" id="getCode" style="overflow-x: scroll;">
          //ajax success content here.
       </div>
    </div>
   </div>
 </div>

<script>
$('#transaction_date, #leads_follow_up_date').datepicker({
    format: 'mm/dd/yyyy',
    autoclose: true,
    todayHighlight: true
});
$('.select2').select2();

$("#leads_form").validate({
    rules: {
      mobile_no_1: "required",
      transaction_date: "required",
      sector: "required",
      category_id: "required",
      main_organization: "required",
      business: "required",
      address: "required",
      town: "required",
      mobile_no_2: "required",
      mobile_no_3: "required",
      land_number: "required",
      email: "required",
      client_response: "required",
      follow_up_date: "required",
    },
    
    submitHandler: function(form) {
      $.ajax({
            url: $('#leads_form').attr('action'),
            type: "POST",
            data: $('#leads_form').serialize(),
            success: function(response) {
                $('#save_leads_btn').html('Submit');
                toastr.success("Leads created successfully!");
                leads_table.ajax.reload();
                document.getElementById("leads_form").reset();
                $('.leads_model').modal("hide");
    
            }
        });
        return false;
    }
  });


function genModalContent(data){
    
    return `<div class="container"><ul class="list-group list-group-flush">
                <li class="list-group-item"> Date Created: ${data.created_at
                }</li>
                <li class="list-group-item">Mobile No 1: ${data.mobile_no_1
                }</li>
                <li class="list-group-item">Sector: ${data.sector
                }</li>
                <li class="list-group-item">Category: ${data.category_id}</li>
                <li class="list-group-item">Main Organization: ${data.main_organization
                }</li>
                <li class="list-group-item">Client Name: ${data.email
                }</li>
                <li class="list-group-item">District: ${data.district}</li>
                <li class="list-group-item">Town: ${data.town}</li>
                <li class="list-group-item">Mobile No2: ${data.mobile_no_2}</li>
                <li class="list-group-item">EMail ID: ${data.email}</li>
                <li class="list-group-item">Follow up Date: ${data.follow_up_date}</li>
                <li class="list-group-item">User: ${data.business_id
                }</li>
                </ul></div>`;

}

$(document).ready(function() {
    $("#leads_date").attr("readonly", true);
    $("#leads_time").attr("readonly", true);
    
    // Mobile 1 ajax query.
    $('#mobile_no_1').blur(function() {
        var mobile1 = $(this).val();
        $.ajax({
            url: '{{route('leads.ajax_mobile')}}',
            type: 'POST',
            data: {
                'postData': mobile1
            },
            success: function(data) {
                if(data) {
                    $("#save_leads_btn").prop("disabled", true);
                    //   show modal 
                    let content = genModalContent(data);
                    document.getElementById('getCode').innerHTML="";
                    $( "#getCode" ).empty("").append(content);
                    $("#getCodeModal").modal('show');
                }else{
                    console.log('data not found');
                    $("#save_leads_btn").prop("disabled", false);
                }
            }
        });
    });
    

    // Mobile 2 ajax query
    $('#mobile_no_2').blur(function() {
        var mobile2 = $(this).val();
        $.ajax({
            url: '{{route('leads.ajax_mobile')}}',
            type: 'POST',
            data: {
                'postData': mobile2
            },
            success: function(data) {
                if(data) {
                    $("#save_leads_btn").prop("disabled", true);
                        
                    //   show modal 
                    let content = genModalContent(data);
                    // document.getElementById('getCode').innerHTML="";
                    // document.getElementById('getCodeModal').innerHTML="";
                    $("#getCode").empty("").append(content);
                    $("#getCodeModal").modal('show');
                } else {
                    console.log('data not found');
                    $("#save_leads_btn").prop("disabled", false);
                }
            }
        });

        
    });
    
    $('#district').change(function(event){
        let district = $(this).val();
        
       $.ajax({
           url: '{{route('leads.ajax_town')}}',
           type: 'POST',
           data: {'postData': district},
           success: function(data){
                // console.log(data[0]);
                $('#town').find('option').not(':first').remove();
                var len = 0;
                    if(data != null){
                        len = data.length;
                    }
                if(len > 0){
                    // Read data and create <option >
                    for(var i=0; i<len; i++){
                        var id = data[i].id;
                        var name = data[i].name;
                        var option = "<option value='"+name+"'>"+name+"</option>";
                        $("#town").append(option);
                    }
                }
           }
       });
    });
    
    $('#country').change(function(event){
        let country = $(this).val();
        
       $.ajax({
           url: '{{route('leads.ajax_district')}}',
           type: 'POST',
           data: {'postData': country},
           success: function(data){
                // console.log(data[0]);
                $('#district').find('option').not(':first').remove();
                var len = 0;
                    if(data != null){
                        len = data.length;
                    }
                if(len > 0){
                    // Read data and create <option >
                    for(var i=0; i<len; i++){
                        var id = data[i].id;
                        var name = data[i].name;
                        var option = "<option value='"+name+"'>"+name+"</option>";
                        $("#district").append(option);
                    }
                }
           }
       });
    });

});
    
    
$(document).ready(function(){
    if (!$('#leads_time').val() || $('#leads_time').val() === 'Time') {
        var now = new Date();
        $('#leads_time').val(String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0'));
    }
    $('#leads_date, #leads_time').prop('readonly', true).attr('tabindex', '-1');
    $('#transaction_date').prop('readonly', true);
});

// close sesocnd modal
$("#md2").click(function(){
     document.getElementById('getCode').innerHTML="";
    $("#addModal").addClass("add_modal");
    $('#getCodeModal').modal('hide');
});
</script>
<style>
.add_modal {
    position: absolute !important;
}
</style>
@endif
