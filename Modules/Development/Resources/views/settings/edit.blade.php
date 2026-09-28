@extends('layouts.app')

@section('title', __('development::lang.edit_module'))

@section('content')
<div class="container-fluid">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('development::lang.edit_module')])
        <form action="{{ route('development.settings.update', $module->id) }}" method="POST" id="module_edit_form">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="name">@lang('development::lang.name')</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $module->name) }}" required>
                        @error('name')
                            <div class="text-danger">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 text-right">
                    <button type="submit" class="btn btn-primary">@lang('development::lang.general.update')</button>
                    <a href="{{ route('development.settings.index') }}" class="btn btn-default">@lang('development::lang.general.cancel')</a>
                </div>
            </div>
        </form>
    @endcomponent
</div>
@endsection

@section('javascript')
<script>
    $(document).ready(function() {
        $('#module_edit_form').validate({
            rules: {
                name: {
                    required: true,
                    maxlength: 255
                }
            },
            messages: {
                name: {
                    required: '{{ __("validation.required", ["attribute" => __("development::lang.name")]) }}',
                    maxlength: '{{ __("validation.max.string", ["attribute" => __("development::lang.name"), "max" => 255]) }}'
                }
            }
        });
    });
</script>
@endsection
