@extends('suppliers::layouts.app')

@section('title', __('suppliers::lang.notes'))

@section('suppliers_content')
<section class="content-header">
    <h1>{{ __('suppliers::lang.notes') }} <small>{{ $supplier->name ?? $supplier->supplier_business_name }}</small></h1>
</section>

<section class="content">
    @include('suppliers::communication.partials.nav', ['supplier' => $supplier])
    
        <div class="box box-solid">
            <div class="box-header with-border"><h3 class="box-title">{{ __('suppliers::lang.add_new') }}</h3></div>
            <div class="box-body">
                {!! Form::open(['url' => '#', 'method' => 'post', 'class' => 'supplier-communication-form']) !!}
                <div class="row">
                    <div class="col-md-4 col-sm-12 form-group">
                        {!! Form::label('title', __('suppliers::lang.title')) !!}
                        {!! Form::text('title', null, ['class' => 'form-control', 'placeholder' => __('suppliers::lang.title')]) !!}
                    </div>
                    <div class="col-md-8 col-sm-12 form-group">
                        {!! Form::label('description', __('suppliers::lang.description')) !!}
                        {!! Form::text('description', null, ['class' => 'form-control', 'placeholder' => __('suppliers::lang.description')]) !!}
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">{{ __('suppliers::lang.save') }}</button>
                {!! Form::close() !!}
            </div>
        </div>

    <div class="box box-solid">
        <div class="box-header with-border"><h3 class="box-title">{{ __('suppliers::lang.notes') }}</h3></div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped supplier-communication-table">
                <thead>
                    <tr>
                        <th>{{ __('suppliers::lang.date') }}</th>
                        <th>{{ __('suppliers::lang.title') }}</th>
                        <th>{{ __('suppliers::lang.description') }}</th>
                        <th>{{ __('suppliers::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notes as $item)
                        <tr>
                            <td>{ $item->created_at ?? '' }</td>
                            <td>{ $item->title ?? '' }</td>
                            <td>{ $item->description ?? '' }</td>
                            <td><div class="btn-group"><button class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">{{ __('suppliers::lang.actions') }} <span class="caret"></span></button></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No notes found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script src="{{ asset('modules/suppliers/js/communication/supplier-communication.js') }}"></script>
@endsection
