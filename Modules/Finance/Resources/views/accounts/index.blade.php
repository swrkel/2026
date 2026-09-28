@extends('layouts.app')

@section('title', __('finance::finance.finance_accounts'))

@section('content')
<section class="content-header">
    <h1>{{ __('finance::finance.finance_accounts') }}</h1>
</section>

<section class="content">
    <div class="box">
        <div class="box-body">
            <p>Finance module foundation loaded. Account migration will continue in FIN-002 and FIN-003.</p>
        </div>
    </div>
</section>
@endsection
