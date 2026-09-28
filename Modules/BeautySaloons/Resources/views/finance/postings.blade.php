@extends('beautysaloons::layouts.app')
@section('title', __('beautysaloons::finance.postings'))
@section('content')
<section class="content-header"><h1>@lang('beautysaloons::finance.postings')</h1></section>
<section class="content"><div class="box"><div class="box-body table-responsive">
<table class="table table-bordered table-striped"><thead><tr><th>Date</th><th>Reference</th><th>Type</th><th>Account</th><th>Debit</th><th>Credit</th><th>Status</th></tr></thead><tbody>
@foreach($postings as $p)<tr><td>{{ $p->posting_date }}</td><td>{{ $p->reference_no }}</td><td>{{ $p->posting_type }}</td><td>{{ $p->account_key }}</td><td class="text-right">{{ number_format($p->debit,4) }}</td><td class="text-right">{{ number_format($p->credit,4) }}</td><td>{{ $p->status }}</td></tr>@endforeach
</tbody></table>{{ $postings->links() }}</div></div></section>
@endsection
