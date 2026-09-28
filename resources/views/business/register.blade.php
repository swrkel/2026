@extends('layouts.auth2')
@section('title', __('lang_v1.register'))

@section('content')
    <div class="login-form col-md-12 col-xs-12 right-col-content-register">

        <p class="form-header">@lang('business.register_and_get_started_in_minutes')</p>
        {!! Form::open([
            'url' => route('business.postRegister'),
            'method' => 'post',
            'id' => 'business_register_form',
            'files' => true,
        ]) !!}
        @include('business.partials.register_form')
        {!! Form::close() !!}
    </div>
@stop
@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Show validation errors in modal
            @if ($errors->has('username') || $errors->has('email') || $errors->has('mobile'))
                var errorMessage = '';
                @if ($errors->has('username'))
                    errorMessage = '{{ $errors->first('username') }}';
                @elseif ($errors->has('email'))
                    errorMessage = '{{ $errors->first('email') }}';
                @elseif ($errors->has('mobile'))
                    errorMessage = '{{ $errors->first('mobile') }}';
                @endif

                swal({
                    title: "{{ __('lang_v1.validation_error') }}",
                    text: errorMessage,
                    icon: "warning",
                    buttons: {
                        confirm: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    }
                });
            @endif

            $('#change_lang').change(function() {
                window.location = "{{ route('business.getRegister') }}?lang=" + $(this).val();
            });
        })
    </script>
@endsection
