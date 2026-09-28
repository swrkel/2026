@extends('layouts.app')
@section('title', __('settlementsw::lang.add_payment'))

@section('content')
<section class="content-header">
    <h1>@lang('settlementsw::lang.add_payment')</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-body">
            @include('settlementsw::swsettlement.payments.add_payment')
        </div>
    </div>
</section>
@endsection
