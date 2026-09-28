@extends('bankingui::layouts.master', ['title' => 'Banking Testing Checklist'])
@section('banking_content')
<div class="box box-primary"><div class="box-body table-responsive">
<table class="table table-bordered table-striped">
<thead><tr><th>Module</th>@foreach($checkpoints as $checkpoint)<th>{{ $checkpoint }}</th>@endforeach</tr></thead>
<tbody>
@foreach($groups as $group)
    @foreach($group['items'] as $item)
        <tr><td><strong>{{ $item['label'] }}</strong><br><small>{{ $group['label'] }}</small></td>@foreach($checkpoints as $checkpoint)<td class="text-center">□</td>@endforeach</tr>
    @endforeach
@endforeach
</tbody>
</table>
</div></div>
@endsection
