@csrf
<div class="row">
    <div class="col-md-4"><div class="form-group"><label>{{ __('pos::messages.name') }}</label><input type="text" name="name" value="{{ old('name', $register->name ?? '') }}" class="form-control" required></div></div>
    <div class="col-md-3"><div class="form-group"><label>{{ __('pos::messages.code') }}</label><input type="text" name="code" value="{{ old('code', $register->code ?? '') }}" class="form-control"></div></div>
    <div class="col-md-3"><div class="form-group"><label>{{ __('pos::messages.opening_balance') }}</label><input type="number" step="0.0001" name="opening_balance" value="{{ old('opening_balance', $register->opening_balance ?? 0) }}" class="form-control text-right"></div></div>
    <div class="col-md-2"><div class="form-group"><label>{{ __('pos::messages.status') }}</label><select name="is_active" class="form-control select2"><option value="1" {{ old('is_active', $register->is_active ?? 1) == 1 ? 'selected' : '' }}>{{ __('pos::messages.active') }}</option><option value="0" {{ old('is_active', $register->is_active ?? 1) == 0 ? 'selected' : '' }}>{{ __('pos::messages.inactive') }}</option></select></div></div>
</div>
<div class="form-group"><label>{{ __('pos::messages.note') }}</label><textarea name="note" class="form-control" rows="3">{{ old('note', $register->note ?? '') }}</textarea></div>
<div class="text-right"><button type="submit" class="btn btn-primary pos-large-save"><i class="fa fa-save"></i> {{ __('pos::messages.save') }}</button></div>
