@extends('chequer::layouts.app')
@section('title','Cheque Books')
@section('chequer_content')
@include('chequer::components.page_header', [
    'title' => 'Cheque Books',
    'subtitle' => 'Cheque book ranges, next cheque number and cheque leaf stock control.',
    'actions' => [
        ['url' => url('/chequer-module/cheque-leaves'), 'label' => 'Cheque Leaves', 'icon' => 'fa-list', 'class' => 'gray'],
    ],
])
@include('chequer::partials.list_toolbar', ['searchPlaceholder' => 'Search book, account, status ...', 'createUrl' => url('/chequer-module/cheque-books/create'), 'createLabel' => 'Add Cheque Book'])
<div class="cheq-card">
    <div class="cheq-table-wrap">
        <table class="cheq-table">
            <thead>
                <tr>
                    <th class="w-small">Book<br>No</th>
                    <th style="width:22%">Bank<br>Account</th>
                    <th class="w-medium">Account<br>No</th>
                    <th class="w-small">Start<br>No</th>
                    <th class="w-small">End<br>No</th>
                    <th class="w-small">Next<br>No</th>
                    <th class="w-small">Available<br>Leaves</th>
                    <th class="w-small">Issued /<br>Printed</th>
                    <th class="w-small">Status</th>
                    <th class="w-action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $summary = $leaf_summary[$row->id] ?? [];
                        $available = $summary['available'] ?? 0;
                        $issued = ($summary['issued'] ?? 0) + ($summary['printed'] ?? 0);
                    @endphp
                    <tr>
                        <td><b>{{ $row->book_no }}</b></td>
                        <td>{{ $row->bank_account_name ?? '-' }}</td>
                        <td>{{ $row->account_number ?? '-' }}</td>
                        <td class="amount">{{ $row->start_no }}</td>
                        <td class="amount">{{ $row->end_no }}</td>
                        <td class="amount"><b>{{ $row->next_no }}</b></td>
                        <td class="amount">{{ $available }}</td>
                        <td class="amount">{{ $issued }}</td>
                        <td><span class="cheq-badge cheq-status-{{ $row->status ?? 'active' }}">{{ ucfirst($row->status) }}</span></td>
                        <td class="cheq-actions">@include('chequer::partials.table_actions',['edit'=>url('/chequer-module/cheque-books/'.$row->id.'/edit'),'delete'=>url('/chequer-module/cheque-books/'.$row->id)])</td>
                    </tr>
                @empty
                    <tr><td colspan="10">@include('chequer::components.empty_state', ['title' => 'No cheque books found', 'message' => 'Create a cheque book to auto-generate cheque leaves.', 'icon' => 'fa-book', 'url' => url('/chequer-module/cheque-books/create'), 'label' => 'Add Cheque Book'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows,'links'))<div class="cheq-pagination">{{ $rows->links() }}</div>@endif
</div>
@endsection
