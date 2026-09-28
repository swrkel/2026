@extends('leadsnew::layouts.app')
@section('title', 'Workflow Kanban')
@section('leadsnew_subtitle', 'Visualize the configured lead stages in their operational sequence.')
@section('leadsnew_content')
<div class="ln-toolbar"><div class="ln-search"><span class="text-muted"><i class="fa fa-info-circle"></i> Stages follow the order configured for the active business.</span></div><a href="{{ url('/leads-new/workflows') }}" class="btn btn-primary"><i class="fa fa-cogs"></i> Workflow Rules</a></div>
<div class="ln-kanban-board">
@forelse($stages as $stage)
    <div class="ln-kanban-column" data-status-id="{{ $stage['id'] ?? '' }}">
        <div class="ln-kanban-header"><span class="ln-kanban-dot" style="background:{{ $stage['color'] ?? '#2563eb' }}"></span><strong>{{ $stage['name'] ?? ('Stage ' . ($stage['id'] ?? '')) }}</strong><span class="ln-badge">{{ $stage['sort_order'] ?? '-' }}</span></div>
        <div class="ln-kanban-empty"><i class="fa fa-clone"></i><span>Stage ready for lead workflow</span></div>
    </div>
@empty
    <div class="ln-panel"><div class="ln-panel-body"><div class="ln-empty"><i class="fa fa-columns"></i>No active workflow stages are configured.</div></div></div>
@endforelse
</div>
@endsection
