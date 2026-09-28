@extends('layouts.app')
@section('title', __('beautysaloons::reception.reception_queue'))

@section('content')
<section class="content-header">
    <h1>@lang('beautysaloons::reception.reception_queue')</h1>
</section>
<section class="content bs013-reception">
    <div class="box box-primary">
        <div class="box-header with-border">
            <a href="{{ route('beautysaloons.reception.create') }}" class="btn btn-primary btn-lg pull-right">
                <i class="fa fa-plus"></i> @lang('messages.add')
            </a>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped" id="bs_reception_queue_table" style="width:100%;">
                <thead>
                    <tr>
                        <th>@lang('beautysaloons::reception.queue_no')</th>
                        <th>@lang('contact.customer')</th>
                        <th>@lang('beautysaloons::reception.mobile')</th>
                        <th>@lang('beautysaloons::reception.visit_type')</th>
                        <th>@lang('beautysaloons::reception.priority')</th>
                        <th>@lang('beautysaloons::reception.status')</th>
                        <th>@lang('beautysaloons::reception.arrival_at')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ Module::asset('beautysaloons:js/bs013_reception.js') }}"></script>
@endsection
