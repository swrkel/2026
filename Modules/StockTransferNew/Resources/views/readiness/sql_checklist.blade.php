@extends('layouts.app')

@section('title', __('stocktransfernew::lang.sql_checklist'))

@section('content')
<section class="content-header stn-final-header"><h1>{{ __('stocktransfernew::lang.sql_checklist') }}</h1></section>
<section class="content stn-final-page">
    <div class="box box-solid stn-final-box">
        <div class="box-body">
            <ol class="stn-sql-list">
                @foreach($files as $file)
                    <li>{{ $file }}</li>
                @endforeach
            </ol>
            <p class="help-block">Run tenant SQL on each tenant database. Do not run tenant table creation SQL only on the master database.</p>
        </div>
    </div>
</section>
@endsection
