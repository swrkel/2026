@extends('expensesnew::layouts.app')
@section('content')
<div class="expnew-form-card">
    <h4>@lang('expensesnew::lang.costing_form')</h4>
    <form method="post">@csrf
        <input type="text" name="name" class="form-control" placeholder="Name">
        <button class="btn btn-primary mt-3">@lang('expensesnew::lang.save')</button>
    </form>
</div>
@endsection
