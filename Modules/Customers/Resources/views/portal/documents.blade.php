@extends('customers::portal.layout')
@section('title', 'Dealer Documents')
@section('body')
@include('customers::portal.partials_nav')
<div class="dd-wrap">
    @include('customers::portal.partials_summary')
    <div class="dd-card">
        <div class="dd-card-header"><h3 class="dd-card-title">Download Center</h3></div>
        <div class="dd-card-body">
            <div class="dd-table-wrap">
                <table class="dd-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Document</th>
                            <th>Description</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td>{{ $row->date }}</td>
                                <td><span class="dd-badge dd-badge-open">{{ $row->category }}</span></td>
                                <td>{{ $row->title }}</td>
                                <td>{{ $row->description }}</td>
                                <td class="text-center">
                                    @if(!empty($row->url))
                                        <a class="dd-btn dd-btn-default" href="{{ $row->url }}" target="_blank">Download</a>
                                    @else
                                        <span class="text-muted">View from related page</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center">No downloadable documents available.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:14px;">
                <a class="dd-btn dd-btn-primary" href="{{ route('customers.portal.statement.export') }}">Download Statement CSV</a>
                <a class="dd-btn dd-btn-default" href="{{ route('customers.portal.statement.print') }}" target="_blank">Print Statement</a>
            </div>
        </div>
    </div>
</div>
@endsection
