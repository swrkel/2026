@extends('poultry::layouts.app')
@section('title', __('poultry::lang.record_harvest'))

@section('content')
<div class="row"><div class="col-md-8 col-md-offset-2">
    <div class="box box-primary">
        <div class="box-body">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>@lang('poultry::lang.batch') *</label>
                    <select id="batch_id" class="form-control">
                        <option value="">--</option>
                        @foreach ($batches as $id => $code)
                            <option value="{{ $id }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label>@lang('poultry::lang.harvest_type') *</label>
                    <select id="harvest_type" class="form-control">
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>@lang('poultry::lang.date') *</label>
                    <input type="date" id="harvest_date" class="form-control"
                           value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-4 form-group">
                    <label>@lang('poultry::lang.birds') *</label>
                    <input type="number" id="birds_qty" class="form-control" min="1">
                </div>
                <div class="col-md-4 form-group">
                    <label>@lang('poultry::lang.total_weight_kg')</label>
                    <input type="number" step="0.001" id="total_weight_kg" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>@lang('poultry::lang.buyer')</label>
                    <select id="buyer_contact_id" class="form-control">
                        <option value="">--</option>
                        @foreach ($buyers as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <span class="help-block">@lang('poultry::lang.buyer_help')</span>
                </div>
                <div class="col-md-6 form-group">
                    <label>@lang('poultry::lang.rate_per_kg')</label>
                    <input type="number" step="0.0001" id="rate_per_kg" class="form-control">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>@lang('poultry::lang.produce_into')</label>
                    <select id="variation_id" class="form-control">
                        <option value="">@lang('poultry::lang.do_not_stock')</option>
                        @foreach ($items as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="help-block">@lang('poultry::lang.produce_into_help')</span>
                </div>
                <div class="col-md-6 form-group">
                    <label>@lang('poultry::lang.location')</label>
                    <select id="location_id" class="form-control">
                        @foreach ($locations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>@lang('poultry::lang.notes')</label>
                <input type="text" id="notes" class="form-control">
            </div>
            <div id="h_feedback"></div>
        </div>
        <div class="box-footer">
            <button class="btn btn-primary" id="btn-harvest">@lang('poultry::lang.save')</button>
            <a href="{{ route('poultry.harvest.index') }}" class="btn btn-link">@lang('poultry::lang.cancel')</a>
        </div>
    </div>
</div></div>
@endsection

@section('javascript')
<script>
$(function () {
    $('#btn-harvest').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);

        $.post('{{ route('poultry.harvest.store') }}', {
            batch_id: $('#batch_id').val(),
            harvest_date: $('#harvest_date').val(),
            harvest_type: $('#harvest_type').val(),
            birds_qty: $('#birds_qty').val(),
            total_weight_kg: $('#total_weight_kg').val() || 0,
            buyer_contact_id: $('#buyer_contact_id').val() || null,
            variation_id: $('#variation_id').val() || null,
            location_id: $('#location_id').val() || null,
            rate_per_kg: $('#rate_per_kg').val() || 0,
            notes: $('#notes').val()
        }).done(function (res) {
            $('#h_feedback').html('<div class="alert alert-' + (res.success ? 'success' : 'danger') + '">'
                + res.msg + '</div>');
            if (res.success) {
                setTimeout(function () { location.href = '{{ route('poultry.harvest.index') }}'; }, 1500);
            }
        }).fail(function (xhr) {
            var msg = 'Save failed.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).join(' ');
            }
            $('#h_feedback').html('<div class="alert alert-danger">' + msg + '</div>');
        }).always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endsection
