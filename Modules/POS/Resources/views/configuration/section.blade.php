@extends('pos::layouts.app', ['title' => $data['meta']['title']])

@section('pos_content')
<div class="box box-solid pos-card">
    <div class="box-header with-border">
        <h3 class="box-title"><i class="{{ $data['meta']['icon'] }}"></i> {{ $data['meta']['title'] }}</h3>
        <div class="box-tools pull-right">
            <a href="{{ route('pos.configuration.index') }}" class="btn btn-sm btn-default"><i class="fa fa-arrow-left"></i> {{ __('pos::messages.back') }}</a>
        </div>
    </div>
    <div class="box-body">
        <p class="text-muted">{{ $data['meta']['description'] }}</p>
        <div class="row">
            @foreach($data['fields'] as $field)
                <div class="col-md-6">
                    <div class="form-group">
                        <label>{{ $field['label'] }}</label>
                        @if($field['type'] === 'select')
                            <select class="form-control pos-typeahead"><option value="">{{ __('pos::messages.please_select') }}</option></select>
                        @elseif($field['type'] === 'checkbox')
                            <div class="checkbox"><label><input type="checkbox" value="1"> {{ __('pos::messages.enable') }}</label></div>
                        @elseif($field['type'] === 'textarea')
                            <textarea class="form-control" rows="3" placeholder="{{ __('pos::messages.note') }}"></textarea>
                        @else
                            <input type="text" class="form-control">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="box-footer text-right">
        <button type="button" class="btn btn-primary pos-save-button"><i class="fa fa-save"></i> {{ __('pos::messages.save') }}</button>
    </div>
</div>
@endsection
