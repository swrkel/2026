<form method="GET" class="form-inline" style="margin-bottom:15px">
    <div class="form-group">
        <label>@lang('poultry::lang.from')</label>
        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control input-sm">
    </div>
    <div class="form-group">
        <label>@lang('poultry::lang.to')</label>
        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control input-sm">
    </div>
    @if (isset($batches))
    <div class="form-group">
        <select name="batch_id" class="form-control input-sm">
            <option value="">@lang('poultry::lang.all_batches')</option>
            @foreach ($batches as $id => $code)
                <option value="{{ $id }}" {{ ($filters['batch_id'] ?? null) == $id ? 'selected' : '' }}>{{ $code }}</option>
            @endforeach
        </select>
    </div>
    @endif
    <button class="btn btn-default btn-sm"><i class="fa fa-filter"></i> @lang('poultry::lang.filter')</button>
</form>
