@extends('distribution::layouts.app')

@section('title', 'Daily Summary Sheets')

@section('content')
    <section class="content-header">
        <h1>Daily Summary Sheets</h1>
    </section>

    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class="box-title">List of Daily Summary Sheets</h3>
                        <div class="box-tools pull-right">
                            <a href="{{ route('distribution.daily_summary.create') }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Create New Sheet
                            </a>
                        </div>
                    </div>
                    <div class="box-body">

                        @if(session('status'))
                            <div class="alert alert-{{ session('status')['success'] ? 'success' : 'danger' }} alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                                {{ session('status')['msg'] }}
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                     <tr>
                                        <th>Sheet Number</th>
                                        <th>Loading Sheet No</th>
                                        <th>Date</th>
                                        <th>Sales Rep</th>
                                        <th>Route</th>
                                        <th>Vehicle</th>
                                        <th>Product Category</th>
                                        <th>Customers</th>
                                        <th>Gross Sale</th>
                                        <th>Discount</th>
                                        <th>Net Sale</th>
                                        <th>Has Free Issues</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                     </tr>
                                </thead>
                                <tbody>
                                    @forelse($summaries as $summary)
                                        @php
                                            // Get lines from the relationship
                                            $lines = $summary->lines;
                                            
                                            $lineCount = $lines ? $lines->count() : 0;
                                            $totalGross = 0;
                                            $totalDiscount = 0;
                                            $totalNet = 0;
                                            $hasFree = false;
                                            $customerNames = [];

                                            if ($lines && $lines->count() > 0) {
                                                foreach ($lines as $line) {
                                                    $totalGross += floatval($line->gross_sale ?? 0);
                                                    $totalDiscount += floatval($line->discount ?? 0);
                                                    $totalNet += floatval($line->net_sale ?? 0);
                                                    
                                                    if (!empty($line->customer_name)) {
                                                        $customerNames[] = $line->customer_name;
                                                    }
                                                    
                                                    // Check for any free issues
                                                    if (!$hasFree && !empty($line->free_issues_json)) {
                                                        $fi = is_string($line->free_issues_json)
                                                            ? json_decode($line->free_issues_json, true)
                                                            : $line->free_issues_json;
                                                        if (!empty($fi)) {
                                                            $hasFree = true;
                                                        }
                                                    }
                                                }
                                            }

                                            $customerList = implode(', ', array_unique($customerNames));
                                            if (strlen($customerList) > 60) {
                                                $customerList = substr($customerList, 0, 57) . '...';
                                            }
                                        @endphp

                                        <tr>
                                            <td>{{ $summary->sheet_number ?? '-' }}</td>
                                            <td>{{ $summary->loading_sheet_no ?? '-' }}</td>
                                            <td>{{ $summary->date ? \Carbon\Carbon::parse($summary->date)->format('d/m/Y') : 'N/A' }}</td>
                                            <td>
                                                @if($summary->salesRep)
                                                    {{ $summary->salesRep->name ?? $summary->salesRep->first_name ?? $summary->salesRep->username ?? 'N/A' }}
                                                @elseif($summary->agent_name)
                                                    {{ $summary->agent_name }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>{{ $summary->route ? $summary->route->name : ($summary->route_name ?? 'N/A') }}</td>
                                            <td>{{ $summary->vehicle ? $summary->vehicle->vehicle_no : ($summary->vehicle_no ?? 'N/A') }}</td>
                                            <td>{{ $summary->productCategory ? $summary->productCategory->name : ($summary->product_category_name ?? 'N/A') }}</td>
                                            <td>
                                                @if($lineCount > 0)
                                                    <span title="{{ $customerList }}">
                                                        {{ $lineCount }} {{ $lineCount == 1 ? 'customer' : 'customers' }}
                                                        @if($customerList)
                                                            <br><small class="text-muted">{{ $customerList }}</small>
                                                        @endif
                                                    </span>
                                                @else
                                                    <span class="text-muted">No bills</span>
                                                @endif
                                            </td>
                                            <td class="text-right">
                                                {{ $totalGross > 0 ? number_format($totalGross, 2) : '-' }}
                                            </td>
                                            <td class="text-right">
                                                {{ $totalDiscount > 0 ? number_format($totalDiscount, 2) : '-' }}
                                            </td>
                                            <td class="text-right">
                                                {{ $totalNet > 0 ? number_format($totalNet, 2) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                @if($hasFree)
                                                    <span class="label label-success">
                                                        <i class="fa fa-check"></i> Yes
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="label label-{{ $summary->status === 'finalized' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($summary->status ?? 'draft') }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ route('distribution.daily_summary.print', $summary->id) }}"
                                                   target="_blank"
                                                   class="btn btn-xs btn-info">
                                                    <i class="fa fa-print"></i> Print
                                                </a>
                                                @if($summary->status !== 'finalized')
                                                    {{-- Use create route with draft_id for editing --}}
                                                    <a href="{{ route('distribution.daily_summary.create', ['draft_id' => $summary->id]) }}"
                                                       class="btn btn-xs btn-primary">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>

                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center">No daily summary sheets found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center">
                            {{ $summaries->links() }}
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('styles')
<style>
    .table > thead > tr > th {
        white-space: nowrap;
        vertical-align: middle;
    }
    .table-responsive {
        overflow-x: auto;
    }
    .text-right {
        text-align: right;
    }
    .table > tbody > tr > td {
        vertical-align: middle;
    }
    .label {
        font-size: 11px;
        padding: 3px 6px;
    }
</style>
@endpush