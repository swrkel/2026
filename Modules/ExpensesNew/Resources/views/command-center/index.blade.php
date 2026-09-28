@extends('layouts.app')
@section('title', $title ?? 'Expenses-New Command Center')
@section('content')
<div class="expnew-page expnew-command-center">
    <div class="expnew-header"><h1>{{ $title ?? 'Expenses-New Command Center' }}</h1></div>
    <div class="expnew-toolbar">
        <input type="text" class="form-control" placeholder="Search expenses, payees, documents...">
        <a href="{{ route('expensesnew.approval-workbench.index') }}" class="btn btn-primary">Approval Workbench</a>
        <a href="{{ route('expensesnew.operations-board.index') }}" class="btn btn-info">Operations Board</a>
    </div>
    <div class="expnew-card-grid">
        @foreach(($cards ?? []) as $card)
            <div class="expnew-kpi-card">
                <span>{{ $card['label'] }}</span>
                <strong>{{ is_numeric($card['value']) ? number_format($card['value'], 2) : $card['value'] }}</strong>
            </div>
        @endforeach
    </div>
    <div class="expnew-panel">
        <h3>Executive Workspace</h3>
        <p>Budget, approval, payment and documentation alerts are summarized here for the selected business and location.</p>
    </div>
</div>
@endsection
