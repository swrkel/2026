@extends('expensesnew::layouts.app', ['heading' => $heading ?? 'Expenses New'])

@section('module_content')
<div class="box box-solid">
    <div class="box-body text-center" style="padding: 36px 20px;">
        <h3 style="margin-top: 0;">{{ $title ?? 'Expenses New' }}</h3>
        <p class="text-muted" style="margin-bottom: 0;">
            This workspace is available. Its detailed operational screen is being completed inside the standalone Expenses New module.
        </p>
    </div>
</div>
@endsection
