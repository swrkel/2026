@extends('poultry::layouts.app')
@section('title', __('poultry::lang.set_eggs'))

@section('content')
<form method="POST" action="{{ route('poultry.hatchery.store') }}">
    @csrf
    <div class="row"><div class="col-md-8 col-md-offset-2">
        <div class="box box-primary">
            <div class="box-body">
                <div class="row">
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.source_batch')</label>
                        <select name="source_batch_id" class="form-control">
                            <option value="">@lang('poultry::lang.bought_in')</option>
                            @foreach ($breederBatches as $id => $code)
                                <option value="{{ $id }}">{{ $code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 form-group">
                        <label>@lang('poultry::lang.supplier')</label>
                        <select name="supplier_contact_id" class="form-control">
                            <option value="">--</option>
                            @foreach ($suppliers as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 form-group">
                        <label>@lang('poultry::lang.set_date') *</label>
                        <input type="date" name="set_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>@lang('poultry::lang.eggs_set') *</label>
                        <input type="number" name="eggs_set" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-4 form-group">
                        <label>@lang('poultry::lang.setter_no')</label>
                        <input type="text" name="setter_no" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.produce_chicks_into')</label>
                    <select name="variation_id" class="form-control">
                        <option value="">@lang('poultry::lang.do_not_stock')</option>
                        @foreach ($items as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="help-block">@lang('poultry::lang.produce_chicks_help')</span>
                </div>
                <div class="form-group">
                    <label>@lang('poultry::lang.notes')</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="box-footer">
                <button class="btn btn-primary">@lang('poultry::lang.save')</button>
                <a href="{{ route('poultry.hatchery.index') }}" class="btn btn-link">@lang('poultry::lang.cancel')</a>
            </div>
        </div>
    </div></div>
</form>
@endsection
