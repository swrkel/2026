@extends('layouts.app')
@section('title', __('beautysaloons::bs014.pos'))
@section('content')
<section class="content-header"><h1>{{ __('beautysaloons::bs014.pos') }}</h1></section>
<section class="content bs014-pos-page">
  <div class="box box-solid">
    <div class="box-header with-border"><h3 class="box-title">{{ __('beautysaloons::bs014.new_bill') }}</h3></div>
    <div class="box-body">
      <div class="table-responsive">
        <table class="table table-bordered" id="bs014_pos_lines_table">
          <thead><tr><th>Type</th><th>Description</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
      <button type="button" class="btn btn-primary btn-lg" id="bs014_add_line">Add Line</button>
      <button type="button" class="btn btn-success btn-lg pull-right" id="bs014_checkout">Save Bill</button>
    </div>
  </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('modules/beautysaloons/js/bs014_pos.js') }}"></script>
@endsection
