@extends('chequer::layouts.app')
@section('title','Chequer Bank Accounts')
@section('chequer_content')
@include('chequer::components.page_header', [
    'title' => 'Bank Accounts',
    'subtitle' => 'Bank accounts are fetched from the existing Finance <b>accounts</b> table. Only the <b>Bank</b> account group is shown.',
])
@include('chequer::partials.list_toolbar', ['searchPlaceholder' => 'Search bank account, account number ...'])
<div class="cheq-card">
    <div class="cheq-table-wrap">
        <table class="cheq-table">
            <thead>
                <tr>
                    <th style="width:34%">Account<br>Name</th>
                    <th style="width:16%">Account<br>Number</th>
                    <th style="width:18%">Account<br>Group</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td><b>{{ $row->name }}</b></td>
                        <td>{{ $row->account_number ?? '-' }}</td>
                        <td><span class="cheq-badge cheq-status-active">{{ $row->account_group ?? 'Bank Account' }}</span></td>
                        <td>{{ $row->note ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">@include('chequer::components.empty_state', ['title' => 'No Finance Bank accounts found', 'message' => 'Please create a Bank account in Finance / Accounts first.', 'icon' => 'fa-university'])</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows,'links'))<div class="cheq-pagination">{{ $rows->links() }}</div>@endif
</div>
@endsection
