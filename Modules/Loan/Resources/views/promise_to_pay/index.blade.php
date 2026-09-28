@extends('layouts.app')

@section('title', 'Promise To Pay Management')

@section('content')

@php
    $currency_precision = session('business.currency_precision', 2);
@endphp

@include('layouts.partials.enterprise-dashboard-style')
@include('layouts.partials.enterprise-chart-style')
@include('layouts.partials.loan-operational-dashboard-style')

<section class="content-header">

    <h1>
        Promise To Pay Management
        <small>Enterprise PTP Governance & Recovery Commitment Tracking</small>
    </h1>

</section>

<section class="content">

    <div class="loan-op-header-panel">
        <div class="row">
            <div class="col-md-8">
                <div class="loan-op-title">
                    <i class="fa fa-handshake-o"></i>
                    Promise To Pay Governance Engine
                </div>

                <div class="loan-op-subtitle">
                    Monitor customer payment commitments, broken promises,
                    recovery officer accountability and escalation governance.
                </div>
            </div>

            <div class="col-md-4 text-right">
                @can('loan.promise_to_pay.create')
                    <a href="{{ route('loan.promise.to.pay.create') }}"
                       class="loan-op-action-btn">
                        <i class="fa fa-plus"></i>
                        Create PTP
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <div class="row">

        <div class="col-md-3">
            <div class="loan-op-card loan-op-blue">
                <div class="icon text-primary"><i class="fa fa-folder-open"></i></div>
                <div class="title">Total PTP</div>
                <div class="value">{{ number_format($total_ptp) }}</div>
                <div class="subtext">Registered payment promises</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="loan-op-card loan-op-yellow">
                <div class="icon text-warning"><i class="fa fa-clock-o"></i></div>
                <div class="title">Open PTP</div>
                <div class="value">{{ number_format($open_ptp) }}</div>
                <div class="subtext">Pending customer commitments</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="loan-op-card loan-op-red">
                <div class="icon text-danger"><i class="fa fa-warning"></i></div>
                <div class="title">Broken PTP</div>
                <div class="value">{{ number_format($broken_ptp) }}</div>
                <div class="subtext">Failed payment promises</div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="loan-op-card loan-op-green">
                <div class="icon text-success"><i class="fa fa-check-circle"></i></div>
                <div class="title">Kept PTP</div>
                <div class="value">{{ number_format($kept_ptp) }}</div>
                <div class="subtext">Successfully honored promises</div>
            </div>
        </div>

    </div>

    <div class="loan-op-section">

        <div class="loan-op-section-title">
            <i class="fa fa-table"></i>
            Promise To Pay Register
        </div>

        @include('layouts.partials.erp-records-toolbar')

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <div class="table-responsive">

            <table id="erp_ajax_table"
                   class="table table-bordered table-hover enterprise-table">
                <thead>
                    <tr>
                        <th>PTP No</th>
                        <th style="min-width:220px;">Customer</th>
                        <th>Loan</th>
                        <th>Branch</th>
                        <th>Officer</th>
                        <th class="text-right">Promised Amount</th>
                        <th>Promise Date</th>
                        <th>Status</th>
                        <th>Escalated</th>
                        <th width="120">Actions</th>
                    </tr>
                </thead>

                <tbody></tbody>
            </table>

        </div>

    </div>

</section>

@endsection

@section('javascript')

<script>
$(document).ready(function () {

    let currencyPrecision = "{{ $currency_precision }}";

     let ptpTable = $('#erp_ajax_table').DataTable({
        processing: true,
        serverSide: true,
        responsive: false,
        searching: true,
        ordering: true,
        pageLength: 10,
        lengthChange: false,

        deferRender: true,
        searchDelay: 150,
        stateSave: false,

        dom: 'Brtip',
        

        ajax: {
            url: "{{ route('loan.promise.to.pay.ajax_data') }}",
            type: "GET",
            data: function (d) {
                d.date_range = $('#erp_date_range_filter').val();
            }
        },

        buttons: [
            {
                extend: 'csv',
                className: 'hidden buttons-csv',
                exportOptions: {
                    columns: ':visible:not(:last-child)'
                }
            },
            {
                extend: 'excel',
                className: 'hidden buttons-excel',
                exportOptions: {
                    columns: ':visible:not(:last-child)'
                }
            },
            {
                extend: 'colvis',
                className: 'hidden buttons-colvis'
            },
            {
                extend: 'pdf',
                className: 'hidden buttons-pdf',
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {
                    columns: ':visible:not(:last-child)'
                }
            },
            {
                extend: 'print',
                className: 'hidden buttons-print',
                exportOptions: {
                    columns: ':visible:not(:last-child)'
                }
            }
        ],

        columns: [
            {
                data: 'ptp_no',
                name: 'ptp_no'
            },
            {
                data: 'customer',
                name: 'customer'
            },
            {
                data: 'loan',
                name: 'loan'
            },
            {
                data: 'branch',
                name: 'branch'
            },
            {
                data: 'officer',
                name: 'officer'
            },
            {
                data: 'promised_amount',
                name: 'promised_amount',
                className: 'text-right'
            },
            {
                data: 'promise_date',
                name: 'promise_date'
            },
            {
                data: 'status',
                name: 'status',
                orderable: false
            },
            {
                data: 'escalated',
                name: 'escalated',
                orderable: false
            },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
        ],

        language: {
            processing: 'Loading records...',
            emptyTable: 'No Promise To Pay records found.',
            zeroRecords: 'No matching Promise To Pay records found.'
        }
    });

});
</script>

@endsection