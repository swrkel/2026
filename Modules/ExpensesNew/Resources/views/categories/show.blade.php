@extends('expensesnew::layouts.app', ['heading'=>'View Category'])
@section('module_content')
<div class="expnew-card">
    <div class="expnew-card-header">
        <h3>Category Details - {{ $category->name }}</h3>
        <div class="expnew-actions">
            <a href="{{ route('expensesnew.categories.edit', $category->id) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('expensesnew.categories.index') }}" class="btn btn-default">Back</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered expnew-details-table">
            <tbody>
                <tr><th>Name</th><td>{{ $category->name }}</td><th>Code</th><td>{{ $category->code ?: '—' }}</td></tr>
                <tr><th>Expense Account</th><td>{{ optional($category->expenseAccount)->name ?: '—' }}</td><th>Default Payee Name</th><td>{{ optional($category->defaultPayee)->name ?: '—' }}</td></tr>
                <tr><th>Active</th><td>{{ $category->is_active ? 'Yes' : 'No' }}</td><th>Expenses Using Category</th><td>{{ number_format((int) $expenseCount) }}</td></tr>
                <tr><th>Description</th><td colspan="3">{{ $category->description ?: '—' }}</td></tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
