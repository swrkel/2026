@extends('leadsnew::layouts.app')

@section('title', __('leadsnew::lang.release_checklist'))

@section('leadsnew_content')
<section class="content-header">
    <h1>{{ __('leadsnew::lang.release_checklist') }}</h1>
</section>

<section class="content">
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">Leads-New RC9 Server Verification</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Check</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(($items ?? []) as $item)
                        <tr>
                            <td>{{ $item['group'] ?? '' }}</td>
                            <td>{{ $item['item'] ?? '' }}</td>
                            <td><span class="label label-warning">{{ $item['status'] ?? 'manual_check' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
