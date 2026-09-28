@extends('chequer::layouts.app')
@section('title', 'Print History')
@section('chequer_content')
@include('chequer::components.page_header', [
    'title' => 'Print History',
    'subtitle' => 'Audit trail of cheque print, preview, voucher and reprint actions.',
    'icon' => 'fa fa-history',
    'actions' => [['label' => 'Write Cheque', 'url' => url('/chequer-module/write-cheque/create'), 'class' => 'btn btn-primary']]
])
<div class="box cheq-card">
    <div class="box-body table-responsive">
        <table class="table table-bordered table-hover cheq-table">
            <thead><tr><th>Date/Time</th><th>Cheque No</th><th>Payee</th><th class="text-right">Amount</th><th>Action</th><th>Printer</th><th>User</th><th>Reprint #</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->created_at }}</td><td>{{ $row->cheque_no }}</td><td>{{ $row->payee_name }}</td><td class="text-right">{{ number_format((float)$row->amount, 2) }}</td><td><span class="label label-info">{{ ucwords(str_replace('_',' ', $row->print_action)) }}</span></td><td>{{ $row->printer_name ?? '-' }}</td><td>{{ trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: '-' }}</td><td>{{ $row->reprint_count ?? 0 }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted">No print history yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if(method_exists($rows, 'links')) {{ $rows->links() }} @endif
    </div>
</div>
@endsection
