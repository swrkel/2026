@extends('poultry::layouts.app')
@section('title', __('poultry::lang.feed_issue'))

@section('content')
@if (! $stockOn)
<div class="alert alert-warning">
    <i class="fa fa-info-circle"></i> @lang('poultry::lang.stock_posting_off')
</div>
@endif

<div class="row">
    <div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.feed_issue')</h3></div>
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
                        <label>@lang('poultry::lang.date') *</label>
                        <input type="date" id="consumption_date" class="form-control"
                               value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.item') *</label>
                        <select id="variation_id" class="form-control">
                            <option value="">--</option>
                            @foreach ($items as $id => $label)
                                <option value="{{ $id }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @if (empty($items))
                            <span class="help-block text-red">@lang('poultry::lang.no_items_configured')</span>
                        @endif
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.location') *</label>
                        <select id="location_id" class="form-control">
                            @foreach ($locations as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>@lang('poultry::lang.qty') *</label>
                        <input type="number" step="0.001" min="0.001" id="qty" class="form-control">
                        <span class="help-block" id="stock-hint"></span>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>@lang('poultry::lang.unit_cost')</label>
                        <input type="number" step="0.0001" min="0" id="unit_cost" class="form-control">
                        <span class="help-block">@lang('poultry::lang.unit_cost_help')</span>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>@lang('poultry::lang.cost_type')</label>
                        <select id="cost_type" class="form-control">
                            <option value="feed">@lang('poultry::lang.feed')</option>
                            <option value="medication">@lang('poultry::lang.medication')</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.notes')</label>
                    <input type="text" id="notes" class="form-control">
                </div>
                <div id="feed-feedback"></div>
            </div>
            <div class="box-footer">
                <button class="btn btn-primary" id="btn-issue">
                    <i class="fa fa-check"></i> @lang('poultry::lang.issue')</button>
                <a href="{{ route('poultry.feed.index') }}" class="btn btn-link">@lang('poultry::lang.cancel')</a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script>
$(function () {
    // Show available stock as soon as item and location are both chosen, so an
    // insufficient-stock error is visible before submitting rather than after.
    function refreshStock() {
        var v = $('#variation_id').val(), l = $('#location_id').val();
        if (!v || !l) { $('#stock-hint').text(''); return; }
        $.get('{{ url('poultry/feed/stock') }}/' + v + '/' + l, function (res) {
            $('#stock-hint').text('@lang('poultry::lang.available'): ' + res.qty_available);
        });
    }
    $('#variation_id, #location_id').on('change', refreshStock);

    $('#btn-issue').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        $('#feed-feedback').html('');

        $.post('{{ route('poultry.feed.store') }}', {
            batch_id:         $('#batch_id').val(),
            consumption_date: $('#consumption_date').val(),
            variation_id:     $('#variation_id').val(),
            location_id:      $('#location_id').val(),
            qty:              $('#qty').val(),
            unit_cost:        $('#unit_cost').val() || null,
            cost_type:        $('#cost_type').val(),
            notes:            $('#notes').val()
        }).done(function (res) {
            $('#feed-feedback').html('<div class="alert alert-' + (res.success ? 'success' : 'danger') + '">'
                + res.msg + '</div>');
            if (res.success) { $('#qty').val(''); refreshStock(); }
        }).fail(function (xhr) {
            var msg = 'Save failed.';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).join(' ');
            }
            $('#feed-feedback').html('<div class="alert alert-danger">' + msg + '</div>');
        }).always(function () { $btn.prop('disabled', false); });
    });
});
</script>
@endsection
