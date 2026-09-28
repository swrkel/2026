@extends('layouts.app')
@section('title', __('distributionnew::lang.final_readiness'))
@section('content')
<section class="content-header">
  <h1>{{ __('distributionnew::lang.final_readiness') }}</h1>
</section>
<section class="content disnew-page">
  <div class="box box-primary disnew-card">
    <div class="box-header with-border">
      <h3 class="box-title">{{ __('distributionnew::lang.server_testing_checklist') }}</h3>
    </div>
    <div class="box-body table-responsive">
      <table class="table table-bordered table-striped" id="disnew_final_readiness_table">
        <thead>
          <tr>
            <th>Group</th><th>Code</th><th>Check</th><th>Severity</th><th>Status</th><th>Message</th><th>Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($checks as $check)
          <tr data-id="{{ $check->id }}">
            <td>{{ $check->check_group }}</td>
            <td>{{ $check->check_code }}</td>
            <td>{{ $check->check_name }}</td>
            <td>{{ ucfirst($check->severity) }}</td>
            <td><span class="label label-{{ $check->status == 'passed' ? 'success' : ($check->status == 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($check->status) }}</span></td>
            <td>{{ $check->message }}</td>
            <td><button class="btn btn-xs btn-primary disnew-mark-passed" data-id="{{ $check->id }}">Mark Passed</button></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</section>
@endsection
@section('javascript')
<script src="{{ asset('Modules/DistributionNew/Resources/assets/js/final_readiness.js') }}"></script>
@endsection
