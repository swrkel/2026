@extends('layouts.app')

@section('title', $title)

@section('content')
<section class="content-header">
    <h1>{{ $title }} <small>Customers Module</small></h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title }}</h3>
        </div>
        <form method="POST" action="{{ empty($record) ? route($routePrefix . '.store') : route($routePrefix . '.update', $record->id) }}">
            @csrf
            @if(!empty($record))
                @method('PUT')
            @endif
            <div class="box-body">
                @if($errors->any())
                    <div class="alert alert-danger">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required value="{{ old('name', $record->name ?? '') }}">
                        </div>
                    </div>

                    @if($key !== 'groups' && $key !== 'settings' && $key !== 'opening_balances')
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Code</label>
                                <input type="text" name="code" class="form-control" value="{{ old('code', $record->code ?? '') }}">
                            </div>
                        </div>
                    @endif

                    @if($key === 'groups')
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Calculation Percentage</label>
                                <input type="number" step="0.0001" name="amount" class="form-control" value="{{ old('amount', $record->amount ?? 0) }}">
                            </div>
                        </div>
                        <input type="hidden" name="price_calculation_type" value="percentage">
                    @endif

                    @if($key === 'settings')
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Value</label>
                                <input type="text" name="value" class="form-control" value="{{ old('value', $record->value ?? '') }}">
                            </div>
                        </div>
                    @endif

                    @if($key === 'opening_balances')
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Reference No</label>
                                <input type="text" name="reference_no" class="form-control" value="{{ old('reference_no', $record->reference_no ?? '') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="amount" class="form-control" required value="{{ old('amount', $record->amount ?? 0) }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Transaction Date</label>
                                <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', $record->transaction_date ?? date('Y-m-d')) }}">
                            </div>
                        </div>
                    @endif

                    <div class="col-md-3">
                        <div class="form-group" style="margin-top:25px;">
                            <label>
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $record->is_active ?? 1) ? 'checked' : '' }}>
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $record->description ?? '') }}</textarea>
                </div>
            </div>
            <div class="box-footer text-right">
                <a href="{{ route($routePrefix . '.index') }}" class="btn btn-default">@lang('messages.cancel')</a>
                <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            </div>
        </form>
    </div>
</section>
@endsection
