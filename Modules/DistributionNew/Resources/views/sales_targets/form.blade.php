<div class="row">
  <div class="col-md-4"><div class="form-group"><label>{{ __('messages.name') }}</label><input type="text" name="name" value="{{ old('name', $record->name ?? '') }}" class="form-control"></div></div>
  <div class="col-md-4"><div class="form-group"><label>{{ __('distributionnew::lang.status') }}</label><select name="status" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option><option value="blocked">Blocked</option></select></div></div>
  <div class="col-md-4"><div class="form-group"><label>{{ __('distributionnew::lang.note') }}</label><input type="text" name="note" value="{{ old('note', $record->note ?? '') }}" class="form-control"></div></div>
</div>
<div class="row"><div class="col-md-12"><button class="btn btn-primary text-white">{{ __('messages.save') }}</button><a href="{{ url()->previous() }}" class="btn btn-default">{{ __('messages.close') }}</a></div></div>
