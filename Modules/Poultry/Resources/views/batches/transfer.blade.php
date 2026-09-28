@extends('poultry::layouts.app')
@section('title', __('poultry::lang.transfer_to_lay'))

@section('content')
<form method="POST" action="{{ route('poultry.batch.transfer', $batch->id) }}">
    @csrf
    <div class="row"><div class="col-md-6 col-md-offset-3">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $batch->batch_code }} &rarr; @lang('poultry::lang.laying_batch')</h3>
            </div>
            <div class="box-body">
                {{-- This is the point where the costing treatment changes from
                     work in progress to amortising asset. The accumulated
                     rearing cost transfers across as the new batch's opening
                     capitalised value. --}}
                <div class="alert alert-info">@lang('poultry::lang.transfer_note')</div>

                <div class="form-group">
                    <label>@lang('poultry::lang.to_house') *</label>
                    <select name="to_house_id" class="form-control" required>
                        <option value="">--</option>
                        @foreach ($houses as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.transfer_date') *</label>
                        <input type="date" name="transfer_date" class="form-control"
                               value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.qty') *</label>
                        <input type="number" name="qty" class="form-control" min="1"
                               max="{{ $batch->current_qty }}" value="{{ $batch->current_qty }}" required>
                        <span class="help-block">@lang('poultry::lang.available'): {{ number_format($batch->current_qty) }}</span>
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.new_batch_code')</label>
                    <input type="text" name="batch_code" class="form-control"
                           placeholder="{{ $batch->batch_code }}-L">
                </div>
            </div>
            <div class="box-footer">
                <button class="btn btn-warning"><i class="fa fa-exchange"></i> @lang('poultry::lang.transfer')</button>
                <a href="{{ route('poultry.batch.show', $batch->id) }}" class="btn btn-link">@lang('poultry::lang.cancel')</a>
            </div>
        </div>
    </div></div>
</form>
@endsection
