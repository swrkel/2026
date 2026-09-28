@extends('pos::layouts.app')
@section('pos_styles')<link rel="stylesheet" href="{{ asset('modules/pos/css/pos_page_003.css') }}">@endsection
@section('pos_content')
<div class="box box-primary pos-panel">
  <div class="box-header with-border"><h3 class="box-title"><i class="fa fa-list"></i> {{ __('pos::page_003.quotations') }}</h3></div>
  <div class="box-body">
    <div class="pos-standard-toolbar">
      <input class="form-control input-sm pos-table-filter" placeholder="{{ __('pos::page_003.search') }}">
      <button class="btn btn-default btn-sm"><i class="fa fa-columns"></i> {{ __('pos::page_003.column_visibility') }}</button>
      <button class="btn btn-default btn-sm"><i class="fa fa-file-excel-o"></i> Excel</button>
      <button class="btn btn-default btn-sm"><i class="fa fa-file-pdf-o"></i> PDF</button>
      <button class="btn btn-default btn-sm"><i class="fa fa-print"></i> {{ __('pos::page_003.print') }}</button>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-striped pos-standard-table">
        <thead><tr><th>#</th><th>{{ __('pos::page_003.customer') }}</th><th class="text-right">{{ __('pos::page_003.total') }}</th><th>{{ __('pos::page_003.status') }}</th><th>{{ __('pos::page_003.note') }}</th><th>{{ __('pos::page_003.action') }}</th></tr></thead>
        <tbody>
        @forelse($sales ?? $quotations ?? [] as $row)
          <tr><td>{{ $row->id }}</td><td>{{ $row->customer_name ?? '-' }}</td><td class="text-right">{{ number_format($row->total_amount ?? 0, 4) }}</td><td>{{ $row->status ?? '-' }}</td><td><button class="btn btn-xs btn-info">{{ __('pos::page_003.note') }}</button></td><td><button class="btn btn-xs btn-primary">{{ __('pos::page_003.resume') }}</button></td></tr>
        @empty
          <tr><td colspan="6" class="text-center">{{ __('pos::page_003.no_records_found') }}</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
