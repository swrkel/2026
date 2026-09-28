@extends('poultry::layouts.app')
@section('title', __('poultry::lang.edit_batch').' - '.$batch->batch_code)

@section('content')
<form method="POST" action="{{ route('poultry.batch.update', $batch->id) }}">
    @csrf @method('PUT')
    <div class="row"><div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-body">
                {{-- Placement date, quantity and bird type are intentionally not
                     editable: every derived KPI is computed from them, so a
                     change here would silently rewrite history. Correct via a
                     transfer or a daily record adjustment instead. --}}
                <div class="alert alert-info">@lang('poultry::lang.edit_batch_note')</div>

                <div class="form-group">
                    <label>@lang('poultry::lang.house')</label>
                    <select name="house_id" class="form-control" required>
                        @foreach ($houses as $id => $name)
                            <option value="{{ $id }}" {{ $batch->house_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.breed')</label>
                    <select name="breed_id" class="form-control">
                        <option value="">--</option>
                        @foreach ($breeds as $id => $name)
                            <option value="{{ $id }}" {{ $batch->breed_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.supplier')</label>
                    <select name="supplier_contact_id" class="form-control">
                        <option value="">--</option>
                        @foreach ($suppliers as $id => $name)
                            <option value="{{ $id }}" {{ $batch->supplier_contact_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.doc_unit_cost')</label>
                        <input type="number" step="0.0001" name="doc_unit_cost" class="form-control"
                               value="{{ $batch->doc_unit_cost }}">
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.expected_depletion')</label>
                        <input type="date" name="expected_depletion_date" class="form-control"
                               value="{{ optional($batch->expected_depletion_date)->format('Y-m-d') }}">
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.notes')</label>
                    <textarea name="notes" class="form-control" rows="3">{{ $batch->notes }}</textarea>
                </div>
            </div>
            <div class="box-footer">
                <button class="btn btn-primary">@lang('poultry::lang.save')</button>
                <a href="{{ route('poultry.batch.show', $batch->id) }}" class="btn btn-link">@lang('poultry::lang.cancel')</a>
            </div>
        </div>
    </div></div>
</form>
@endsection
