@extends('audit::layout')
@section('audit-title','Run Audit')
@section('audit-content')
<div class="audit-card">
 <div class="audit-card-title">Select modules to audit</div>
 <p class="audit-note">No operational record will be changed. Audit rules read the current tenant database and write only to the standalone audit tables.</p>
 <form method="post" action="{{ route('audit.run.store') }}">@csrf
  <div class="audit-check-grid">@foreach($modules as $m)<label><input type="checkbox" name="modules[]" value="{{ $m }}" checked> <span>{{ $m }}</span></label>@endforeach</div>
  <div class="audit-actions"><button class="audit-btn primary" type="submit" data-confirm="Run the selected audit checks now?">Run Selected Audit</button></div>
 </form>
</div>
@endsection
