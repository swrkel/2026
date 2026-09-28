@extends('layouts.app')
@section('title', __('contact.add_contact'))

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">@lang('contact.add_contact')</h4>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatContactController@index', ['type' => $type]) }}">@lang('vat::lang.vat_contacts')</a></li>
                    <li><span>@lang('messages.add')</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<section class="content main-content-inner">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('contact.add_contact')])
        {!! Form::open(['url' => action('\\Modules\\Vat\\Http\\Controllers\\VatContactController@store'), 'method' => 'post', 'id' => 'vat_contact_add_form_full']) !!}
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('name', __('contact.name') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i></span>
                        {!! Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('contact.name'), 'required']) !!}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('should_notify', __('contact.should_notify') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                        {!! Form::select('should_notify', ['1' => __('messages.yes'), '0' => __('messages.no')], null, ['placeholder' => __('messages.please_select'), 'required', 'class' => 'form-control select2']) !!}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('credit_notification', __('contact.credit_notification') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                        {!! Form::select('credit_notification', ['settlement' => __('contact.settlement'), 'customer_bill' => __('contact.bill_to_customer')], null, ['placeholder' => __('contact.none'), 'required', 'class' => 'form-control select2']) !!}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('contact_id', __('lang_v1.contact_id') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-id-badge"></i></span>
                        {!! Form::text('contact_id', !empty($contact_id) ? $contact_id : null, ['class' => 'form-control', 'placeholder' => __('lang_v1.contact_id'), 'readonly']) !!}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('mobile', __('contact.mobile') . ':*') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-mobile"></i></span>
                        {!! Form::text('mobile', null, ['class' => 'form-control input_number', 'required', 'placeholder' => __('contact.mobile')]) !!}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('alternate_number', __('contact.alternate_contact_number') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                        {!! Form::text('alternate_number', null, ['class' => 'form-control input_number', 'placeholder' => __('contact.alternate_contact_number')]) !!}
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('vat_no', __('contact.vat_number') . ':') !!}
                    <div class="input-group">
                        <span class="input-group-addon"><i class="fa fa-file-text-o"></i></span>
                        {!! Form::text('vat_no', null, ['class' => 'form-control', 'placeholder' => __('contact.vat_number')]) !!}
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    {!! Form::label('address', __('vat::lang.address') . ':') !!}
                    {!! Form::textarea('address', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('vat::lang.enter_address')]) !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12 text-right">
                <a href="{{ action('\\Modules\\Vat\\Http\\Controllers\\VatContactController@index', ['type' => $type]) }}" class="btn btn-default">@lang('messages.close')</a>
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            </div>
        </div>
        {!! Form::close() !!}
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        if ($.fn.select2) {
            $('select.select2').select2();
        }
    });
</script>
@endsection
