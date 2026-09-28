@extends('chequer::layouts.app')
@section('title','Cheque Leaves')
@section('chequer_content')
@include('chequer::components.page_header', [
    'title' => 'Cheque Leaves',
    'subtitle' => 'Auto-generated cheque leaves from each cheque book range.',
    'actions' => [
        ['url' => url('/chequer-module/cheque-books'), 'label' => 'Cheque Books', 'icon' => 'fa-book', 'class' => 'gray'],
    ],
])
@include('chequer::partials.list_toolbar', ['searchPlaceholder' => 'Search cheque no, book, account, status ...'])
<div class="cheq-card">
    <div class="cheq-table-wrap">
        <table class="cheq-table">
            <thead>
                <tr>
                    <th class="w-medium">Cheque<br>No</th>
                    <th class="w-medium">Book<br>No</th>
                    <th style="width:28%">Bank<br>Account</th>
                    <th class="w-medium">Account<br>No</th>
                    <th class="w-medium">Status</th>
                    <th class="w-medium">Issued<br>At</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><b>{{ $row->cheque_no }}</b></td>
                        <td>{{ $row->book_no ?? '-' }}</td>
                        <td>{{ $row->bank_account_name ?? '-' }}</td>
                        <td>{{ $row->account_number ?? '-' }}</td>
                        <td><span class="cheq-badge cheq-status-{{ $row->status ?? 'available' }}">{{ ucfirst($row->status) }}</span></td>
                        <td>{{ $row->issued_at ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">@include('chequer::components.empty_state', ['title' => 'No cheque leaves found', 'message' => 'Cheque leaves are generated from cheque book ranges.', 'icon' => 'fa-list'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows,'links'))<div class="cheq-pagination">{{ $rows->links() }}</div>@endif
</div>
@endsection
