@extends('expensesnew::layouts.app')
@section('title', __('expensesnew::lang.approval_center'))
@section('content')
<div class="expnew-page">
    <div class="expnew-hero">
        <div><h2>{{ __('expensesnew::lang.approval_center') }}</h2><p>{{ __('expensesnew::lang.approval_center_note') }}</p></div>
    </div>
    <div class="expnew-card">
        @include('expensesnew::components.toolbar')
        <div class="table-responsive">
            <table class="table table-bordered table-striped expnew-table">
                <thead><tr><th>{{ __('expensesnew::lang.expense_no') }}</th><th>{{ __('expensesnew::lang.date') }}</th><th>{{ __('expensesnew::lang.payee') }}</th><th>{{ __('expensesnew::lang.amount') }}</th><th>{{ __('expensesnew::lang.status') }}</th><th>{{ __('expensesnew::lang.action') }}</th></tr></thead>
                <tbody>
                @forelse($expenses as $expense)
                    <tr>
                        <td>{{ $expense->expense_no }}</td><td>{{ $expense->expense_date }}</td><td>{{ optional($expense->payee)->name ?? $expense->payee_name }}</td><td class="text-right">{{ number_format($expense->total_amount ?? $expense->final_total ?? $expense->amount ?? 0, 4) }}</td><td><span class="expnew-status">{{ ucfirst(str_replace('_',' ', $expense->status)) }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('expensesnew.approvals.approve', $expense->id) }}" style="display:inline">@csrf<button class="btn btn-xs btn-success">{{ __('expensesnew::lang.approve') }}</button></form>
                            <form method="POST" action="{{ route('expensesnew.approvals.reject', $expense->id) }}" style="display:inline">@csrf<button class="btn btn-xs btn-danger">{{ __('expensesnew::lang.reject') }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center">{{ __('expensesnew::lang.no_records_found') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $expenses->links() }}
    </div>
</div>
@endsection
