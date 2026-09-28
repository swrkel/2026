<div class="supplier-form-actions text-right">
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save"></i> {{ $isEdit ? __('suppliers::lang.update') : __('suppliers::lang.save') }}
    </button>
    <a href="{{ route('suppliers.records.index') }}" class="btn btn-default">
        @lang('suppliers::lang.cancel')
    </a>
</div>
