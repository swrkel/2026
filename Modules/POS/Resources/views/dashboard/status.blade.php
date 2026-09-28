@extends('pos::layouts.app', ['title' => __('pos::messages.module_status')])

@section('pos_content')
<div class="box box-solid pos-card">
    <div class="box-header with-border"><h3 class="box-title">{{ __('pos::messages.module_status') }}</h3></div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped pos-standard-table">
            <thead><tr><th>{{ __('pos::messages.component') }}</th><th>{{ __('pos::messages.status') }}</th></tr></thead>
            <tbody>
                @foreach($status as $row)
                    <tr><td>{{ $row['name'] }}</td><td><span class="label label-success">{{ $row['status'] }}</span></td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
