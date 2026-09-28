@extends('poultry::layouts.app')
@section('title', __('poultry::lang.place_batch'))

@section('content')
<form method="POST" action="{{ route('poultry.batch.store') }}">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <div class="box box-primary">
                <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.batch_details')</h3></div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>@lang('poultry::lang.bird_type') *</label>
                            <select name="bird_type" class="form-control" required>
                                @foreach ($birdTypes as $key => $label)
                                    <option value="{{ $key }}" {{ old('bird_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>@lang('poultry::lang.batch_code')</label>
                            <input type="text" name="batch_code" class="form-control" value="{{ old('batch_code') }}"
                                   placeholder="@lang('poultry::lang.auto_generated')">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>@lang('poultry::lang.farm') *</label>
                            <select name="farm_id" id="farm_id" class="form-control" required>
                                <option value="">--</option>
                                @foreach ($farms as $id => $name)
                                    <option value="{{ $id }}" {{ old('farm_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>@lang('poultry::lang.house') *</label>
                            <select name="house_id" id="house_id" class="form-control" required>
                                <option value="">--</option>
                                @foreach ($houses as $id => $name)
                                    <option value="{{ $id }}" {{ old('house_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>@lang('poultry::lang.breed')</label>
                            <select name="breed_id" class="form-control">
                                <option value="">--</option>
                                @foreach ($breeds as $id => $name)
                                    <option value="{{ $id }}" {{ old('breed_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>@lang('poultry::lang.placement_date') *</label>
                            <input type="date" name="placement_date" class="form-control" required
                                   value="{{ old('placement_date', date('Y-m-d')) }}">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>@lang('poultry::lang.initial_qty') *</label>
                            <input type="number" name="initial_qty" class="form-control" min="1" required
                                   value="{{ old('initial_qty') }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>@lang('poultry::lang.male_qty')</label>
                            <input type="number" name="male_qty" class="form-control" min="0" value="{{ old('male_qty', 0) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>@lang('poultry::lang.female_qty')</label>
                            <input type="number" name="female_qty" class="form-control" min="0" value="{{ old('female_qty', 0) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>@lang('poultry::lang.notes')</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="box box-info">
                <div class="box-header with-border"><h3 class="box-title">@lang('poultry::lang.source_and_cost')</h3></div>
                <div class="box-body">
                    <div class="form-group">
                        <label>@lang('poultry::lang.supplier')</label>
                        <select name="supplier_contact_id" class="form-control">
                            <option value="">--</option>
                            @foreach ($suppliers as $id => $name)
                                <option value="{{ $id }}" {{ old('supplier_contact_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                        <span class="help-block">@lang('poultry::lang.supplier_help')</span>
                    </div>
                    <div class="form-group">
                        <label>@lang('poultry::lang.doc_unit_cost')</label>
                        <input type="number" step="0.0001" name="doc_unit_cost" class="form-control"
                               min="0" value="{{ old('doc_unit_cost', 0) }}">
                        <span class="help-block">@lang('poultry::lang.doc_cost_help')</span>
                    </div>
                    <div class="form-group">
                        <label>@lang('poultry::lang.expected_depletion')</label>
                        <input type="date" name="expected_depletion_date" class="form-control"
                               value="{{ old('expected_depletion_date') }}">
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fa fa-check"></i> @lang('poultry::lang.place_batch')</button>
                    <a href="{{ route('poultry.batch.index') }}" class="btn btn-link btn-block">@lang('poultry::lang.cancel')</a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('javascript')
<script>
$(function () {
    // Houses are scoped to the selected farm.
    $('#farm_id').on('change', function () {
        var farmId = $(this).val();
        var $house = $('#house_id').html('<option value="">--</option>');
        if (!farmId) { return; }
        $.get('{{ url('poultry/houses-by-farm') }}/' + farmId, function (data) {
            $.each(data, function (id, name) {
                $house.append($('<option>').val(id).text(name));
            });
        });
    });
});
</script>
@endsection
