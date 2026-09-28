@extends('autoservice::layouts.master')
@section('title','Service Due Report')
@section('autoservice_content')
<div class="box"><div class="box-body"><table class="table table-bordered"><tr><th>Due Date</th><th>Send On</th><th>Mobile</th><th>Status</th></tr>@foreach($rows as $r)<tr><td>{{ $r->due_date }}</td><td>{{ $r->send_on }}</td><td>{{ $r->mobile }}</td><td>{{ $r->status }}</td></tr>@endforeach</table>{{ $rows->links() }}</div></div>
@endsection
