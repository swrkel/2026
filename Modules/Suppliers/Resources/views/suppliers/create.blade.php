@extends('suppliers::layouts.app')
@section('title', __('suppliers::lang.add_supplier'))

@section('suppliers_content')
<section class="content-header">
    <h1>@lang('suppliers::lang.add_supplier')</h1>
</section>

<section class="content">
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="box box-primary">
        <div class="box-body">
            <form method="POST" action="{{ route('suppliers.records.store') }}" id="supplier-create-form">
                @csrf
                @include('suppliers::suppliers.form')
            </form>
        </div>
    </div>
</section>
@endsection

@section('javascript')
    <script src="{{ asset('modules/suppliers/js/suppliers/forms/create.js') }}?v=20260905-transaction-date-1"></script>
@endsection
