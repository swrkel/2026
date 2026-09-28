@extends('layouts.app')
@section('title', __('beautysaloons::reception.new_queue'))

@section('content')
<section class="content-header"><h1>@lang('beautysaloons::reception.new_queue')</h1></section>
<section class="content bs013-reception">
    {!! Form::open(['route' => 'beautysaloons.reception.store', 'method' => 'post']) !!}
    <div class="box box-primary">
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('customer_name', __('contact.customer')) !!}
                        {!! Form::text('customer_name', null, ['class'=>'form-control', 'placeholder'=>__('contact.customer')]) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('mobile', __('beautysaloons::reception.mobile')) !!}
                        {!! Form::text('mobile', null, ['class'=>'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('visit_type', __('beautysaloons::reception.visit_type')) !!}
                        {!! Form::select('visit_type', ['walk_in'=>'Walk-in','appointment'=>'Appointment','package'=>'Package','membership'=>'Membership','complaint'=>'Complaint'], 'walk_in', ['class'=>'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        {!! Form::label('priority', __('beautysaloons::reception.priority')) !!}
                        {!! Form::select('priority', ['normal'=>'Normal','vip'=>'VIP','senior'=>'Senior','urgent'=>'Urgent'], 'normal', ['class'=>'form-control']) !!}
                    </div>
                </div>
            </div>
            <div class="form-group">
                {!! Form::label('notes', __('beautysaloons::reception.notes')) !!}
                {!! Form::textarea('notes', null, ['class'=>'form-control', 'rows'=>3]) !!}
            </div>
        </div>
        <div class="box-footer text-right">
            <button class="btn btn-primary btn-lg bs-save-btn"><i class="fa fa-save"></i> @lang('messages.save')</button>
        </div>
    </div>
    {!! Form::close() !!}
</section>
@endsection
