@extends('layouts.app')

@section('title', __('petrodirect::lang.edit_pumper'))

@section('content')
<section class="content-header">
    <h1>@lang('petrodirect::lang.edit_pumper')</h1>
</section>

<section class="content">
{!! Form::open(['url' => route('petrodirect.pumper-management.update', $pump_operator->id), 'method' => 'put', 'id' => 'petrodirect_pumper_form', 'autocomplete' => 'off']) !!}
{{--
    MA-002 (IS1902) - SHOW VALIDATION ERRORS.

    This form validates name, address, mobile and location_id as REQUIRED
    (PumperManagementController::validatedData). When any of them fails,
    Laravel redirects back with the errors in the session - and nothing on
    this page ever rendered them.

    layouts.partials.error exists and does exactly that, but it is included
    by only a handful of unrelated views (purchase, sell_return) and is NOT
    in layouts.app. So the operator pressed Save, came back to the same form,
    and saw no reason why - which is exactly the report: "the details are
    not saved".

    Including it here turns a silent failure into a visible message naming
    the field. It changes no logic and no data path.
--}}
@includeIf('layouts.partials.error')

@include('petrodirect::pumper_management.partials.form', ['submit_text' => __('messages.update')])
{!! Form::close() !!}
</section>
@stop

@section('javascript')
@include('petrodirect::pumper_management.partials.script')
@stop
