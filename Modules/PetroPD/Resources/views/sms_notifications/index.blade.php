@extends('layouts.app')
@section('title', 'Petro PD SMS Notifications')

@section('content')
<section class="content-header">
    <h1>Petro PD SMS Notifications</h1>
</section>

<section class="content">
    {!! Form::open(['route' => 'petropd.sms-notifications.store', 'method' => 'post']) !!}
    <div class="row no-print">
        <div class="col-md-12">
            @include('petropd::sms_notifications.partials.sms', ['templates' => $notifications])
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 text-center">
            <button type="submit" class="btn btn-danger btn-big">@lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@stop

@section('javascript')
<script type="text/javascript">
    if ($.fn.iCheck) {
        $('input.input-icheck').iCheck({checkboxClass: 'icheckbox_square-blue'});
    }
</script>
@endsection
