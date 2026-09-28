<div class="row">
    @for($i = 1; $i <= 4; $i++)
        @php $field = 'custom_field' . $i; @endphp
        <div class="col-md-3 col-sm-6 col-xs-12">
            <div class="form-group">
                <label>@lang('suppliers::lang.custom_field') {{ $i }}</label>
                <input type="text" name="{{ $field }}" class="form-control" value="{{ old($field, $isEdit ? $supplier->{$field} : '') }}">
            </div>
        </div>
    @endfor
</div>
