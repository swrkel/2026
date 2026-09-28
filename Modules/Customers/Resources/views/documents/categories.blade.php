@extends('layouts.app')
@section('title', __('customers::lang.document_categories'))
@section('content')
<section class="content-header"><h1>@lang('customers::lang.document_categories')</h1></section>
<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border clearfix">
            <h3 class="box-title"><i class="fa fa-tags"></i> @lang('customers::lang.document_categories')</h3>
            <div class="pull-right"><a href="{{ route('customers.index') }}" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> @lang('messages.back')</a></div>
        </div>
        <div class="box-body">
            @if(!$enabled)
                <div class="alert alert-warning">@lang('customers::lang.document_categories_table_missing')</div>
            @else
                {!! Form::open(['url' => route('customers.document_categories.store'), 'method' => 'post']) !!}
                    <div class="row">
                        <div class="col-md-4"><div class="form-group">{!! Form::label('name', __('customers::lang.name') . ':*') !!}{!! Form::text('name', null, ['class'=>'form-control','required'=>true]) !!}</div></div>
                        <div class="col-md-6"><div class="form-group">{!! Form::label('description', __('customers::lang.description')) !!}{!! Form::text('description', null, ['class'=>'form-control']) !!}</div></div>
                        <div class="col-md-2" style="padding-top:25px;"><button type="submit" class="btn btn-primary btn-block"><i class="fa fa-save"></i> @lang('messages.save')</button></div>
                    </div>
                {!! Form::close() !!}
                <hr>
                <table class="table table-bordered table-hover">
                    <thead><tr><th>@lang('customers::lang.name')</th><th>@lang('customers::lang.description')</th><th>@lang('messages.action')</th></tr></thead>
                    <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->description }}</td>
                            <td>{!! Form::open(['url'=>route('customers.document_categories.destroy',$category->id),'method'=>'delete','style'=>'display:inline;']) !!}<button type="submit" class="btn btn-danger btn-xs" onclick="return confirm('{{ __('messages.sure') }}');"><i class="fa fa-trash"></i></button>{!! Form::close() !!}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted">@lang('customers::lang.no_records_found')</td></tr>
                    @endforelse
                    </tbody>
                </table>
                @if(method_exists($categories, 'links')) {{ $categories->links() }} @endif
            @endif
        </div>
    </div>
</section>
@endsection
