@extends('layouts.app')

@section('title', $title)

@section('content')
<section class="content-header">
    <h1>{{ $title }} <small>Customers Module</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert alert-success">{{ session('status.msg') }}</div>
    @endif

    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title }}</h3>
            <div class="box-tools pull-right">
                <a href="{{ route('customers.settings.index') }}" class="btn btn-default btn-sm">Back</a>
            </div>
        </div>
        <form method="POST" action="{{ route(str_replace('.index', '.update', $route)) }}">
            @csrf
            @method('PUT')
            <div class="box-body">
                <div class="row">
                    @foreach($settings as $key => $value)
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ ucwords(str_replace('_', ' ', $key)) }}</label>
                                @if(is_numeric($value) && in_array((string)$value, ['0','1'], true))
                                    <select name="{{ $key }}" class="form-control">
                                        <option value="1" {{ (int)$value === 1 ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ (int)$value === 0 ? 'selected' : '' }}>No</option>
                                    </select>
                                @else
                                    <input type="text" name="{{ $key }}" value="{{ $value }}" class="form-control">
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="box-footer text-right">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</section>
@endsection
