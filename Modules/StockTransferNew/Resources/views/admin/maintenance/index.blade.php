@extends('layouts.app')
@section('title', __('stocktransfernew::maintenance.title'))
@section('content')
<link rel="stylesheet" href="{{ asset('modules/stocktransfernew/css/stocktransfernew-maintenance.css') }}">
<div class="stn-page-wrap">
    <div class="stn-page-header">
        <div>
            <h1>{{ __('stocktransfernew::maintenance.title') }}</h1>
            <p>{{ __('stocktransfernew::maintenance.subtitle') }}</p>
        </div>
        <a href="{{ route('stocktransfernew.admin.maintenance.calendar') }}" class="btn btn-primary">{{ __('stocktransfernew::maintenance.calendar') }}</a>
    </div>

    <div class="stn-kpi-grid">
        <div class="stn-kpi"><span>Open</span><strong>{{ $summary['open'] ?? 0 }}</strong></div>
        <div class="stn-kpi danger"><span>Overdue</span><strong>{{ $summary['overdue'] ?? 0 }}</strong></div>
        <div class="stn-kpi warning"><span>High Priority</span><strong>{{ $summary['high_priority'] ?? 0 }}</strong></div>
        <div class="stn-kpi success"><span>Closed This Month</span><strong>{{ $summary['closed_this_month'] ?? 0 }}</strong></div>
    </div>

    <div class="stn-card">
        <form method="POST" action="{{ route('stocktransfernew.admin.maintenance.store') }}" class="stn-inline-form">
            @csrf
            <input type="text" name="title" class="form-control" placeholder="Task title" required>
            <select name="task_type" class="form-control"><option value="general">General</option><option value="data_check">Data Check</option><option value="archive">Archive</option><option value="performance">Performance</option><option value="security">Security</option></select>
            <select name="priority" class="form-control"><option value="low">Low</option><option value="medium">Medium</option><option value="high">High</option><option value="critical">Critical</option></select>
            <input type="date" name="due_date" class="form-control">
            <button class="btn btn-success">Add</button>
        </form>
    </div>

    <div class="stn-card">
        <table class="table table-bordered table-striped stn-table">
            <thead><tr><th>Due Date</th><th>Title</th><th>Type</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>{{ optional($item->due_date)->format('Y-m-d') }}</td>
                    <td>{{ $item->title }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->task_type)) }}</td>
                    <td><span class="stn-badge {{ $item->priority }}">{{ ucfirst($item->priority) }}</span></td>
                    <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                    <td>
                        <form method="POST" action="{{ route('stocktransfernew.admin.maintenance.status', $item->id) }}" class="stn-status-form">
                            @csrf
                            <select name="status" class="form-control input-sm"><option value="open">Open</option><option value="in_progress">In Progress</option><option value="on_hold">On Hold</option><option value="closed">Closed</option></select>
                            <input name="remarks" class="form-control input-sm" placeholder="Remarks">
                            <button class="btn btn-xs btn-primary">Save</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No maintenance tasks found.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $items->links() }}
    </div>
</div>
@endsection
