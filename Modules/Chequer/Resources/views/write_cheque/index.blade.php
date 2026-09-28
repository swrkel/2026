@extends('chequer::layouts.app')
@section('title','Write Cheque')
@section('chequer_content')
@include('chequer::components.page_header', [
    'title' => 'Written Cheques',
    'subtitle' => 'Draft, printed and tracked cheques.',
    'actions' => [
        ['url' => url('/chequer-module/write-cheque/create'), 'label' => 'Write Cheque', 'icon' => 'fa-pen-square', 'class' => 'green'],
    ],
])
@include('chequer::partials.list_toolbar', ['searchPlaceholder' => 'Search cheque no, payee, bank, status ...', 'createUrl' => url('/chequer-module/write-cheque/create'), 'createLabel' => 'Write Cheque'])
<div class="cheq-card">
    <div class="cheq-table-wrap">
        <table class="cheq-table">
            <thead>
                <tr>
                    <th class="w-medium">Cheque<br>No</th>
                    <th class="w-medium">Cheque<br>Date</th>
                    <th style="width:22%">Bank<br>Account</th>
                    <th style="width:20%">Payee</th>
                    <th class="w-medium">Payment<br>Type</th>
                    <th class="w-medium">Amount</th>
                    <th class="w-medium">Status</th>
                    <th class="w-action">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><b>{{ $row->cheque_no }}</b></td>
                        <td>{{ $row->cheque_date }}</td>
                        <td>{{ $row->bank_account_name ?? '-' }}</td>
                        <td>{{ $row->payee_name }}</td>
                        <td>{{ ucwords(str_replace('_',' ',$row->payment_type)) }}</td>
                        <td class="amount"><b>{{ number_format($row->amount,2) }}</b></td>
                        <td><span class="cheq-badge cheq-status-{{ $row->status ?? 'draft' }}">{{ ucfirst($row->status) }}</span></td>
                        <td class="cheq-actions">@include('chequer::partials.table_actions',['print'=>url('/chequer-module/write-cheque/'.$row->id.'/print')])</td>
                    </tr>
                @empty
                    <tr><td colspan="8">@include('chequer::components.empty_state', ['title' => 'No cheques found', 'message' => 'Create the first cheque using the Write Cheque button.', 'icon' => 'fa-money-check-alt', 'url' => url('/chequer-module/write-cheque/create'), 'label' => 'Write Cheque'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows,'links'))<div class="cheq-pagination">{{ $rows->links() }}</div>@endif
</div>
@endsection
