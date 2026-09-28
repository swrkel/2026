@extends('layouts.app')

@section('title', $title)

@section('content')
<section class="content-header">
    <h1>{{ $title }} <small>Customers Module</small></h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert {{ data_get(session('status'), 'success') ? 'alert-success' : 'alert-danger' }}">
            {{ data_get(session('status'), 'msg') }}
        </div>
    @endif

    @if(isset($tableAvailable) && !$tableAvailable)
        <div class="alert alert-warning">
            <i class="fa fa-exclamation-triangle"></i>
            The required tenant table <strong>{{ $tableName }}</strong> is not installed yet.
            Run <strong>00_MASTER_CUSTOMERS_IS1805.sql</strong> in this tenant database, then refresh this page.
        </div>
    @endif

    <div class="box box-primary customers-master-data-box">
        <div class="box-header with-border">
            <h3 class="box-title">{{ $title }}</h3>
            <div class="box-tools pull-right">
                @if(!isset($tableAvailable) || $tableAvailable)
                    <a href="{{ route($routePrefix . '.create') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-plus"></i> @lang('messages.add')
                    </a>
                @endif
                <a href="{{ route('customers.master.index') }}" class="btn btn-default btn-sm">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="box-body">
            <div class="table-responsive customers-master-scroll">
                <table class="table table-bordered table-striped customers-master-table">
                    <thead>
                        <tr>
                            <th style="width:70px;">#</th>
                            <th>Name</th>
                            @if($key !== 'groups')
                                <th style="width:140px;">Code / Reference</th>
                            @endif
                            @if($key === 'opening_balances')
                                <th class="text-right" style="width:140px;">Amount</th>
                                <th style="width:130px;">Date</th>
                            @endif
                            @if($key === 'settings')
                                <th>Value</th>
                            @endif
                            <th>Description</th>
                            <th style="width:110px;">Status</th>
                            <th style="width:130px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $record)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $record->name ?? '' }}</td>
                                @if($key !== 'groups')
                                    <td>{{ $record->code ?? $record->reference_no ?? '' }}</td>
                                @endif
                                @if($key === 'opening_balances')
                                    <td class="text-right">{{ number_format((float) ($record->amount ?? 0), 2) }}</td>
                                    <td>{{ $record->transaction_date ?? '' }}</td>
                                @endif
                                @if($key === 'settings')
                                    <td>{{ $record->value ?? '' }}</td>
                                @endif
                                <td>{{ $record->description ?? '' }}</td>
                                <td>
                                    @if(($record->is_active ?? 1) == 1)
                                        <span class="label label-success">Active</span>
                                    @else
                                        <span class="label label-default">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-xs btn-primary dropdown-toggle" data-toggle="dropdown">
                                            @lang('customers::lang.actions') <span class="caret"></span>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li><a href="{{ route($routePrefix . '.edit', $record->id) }}"><i class="fa fa-edit"></i> @lang('messages.edit')</a></li>
                                            <li>
                                                <form method="POST" action="{{ route($routePrefix . '.destroy', $record->id) }}" onsubmit="return confirm('Delete this record?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-link" style="padding:3px 20px;color:#333;text-align:left;width:100%;">
                                                        <i class="fa fa-trash"></i> @lang('messages.delete')
                                                    </button>
                                                </form>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<style>
.customers-master-scroll{overflow-x:auto!important;-webkit-overflow-scrolling:touch;}
.customers-master-table{min-width:900px;}
.customers-master-data-box .dropdown-menu{z-index:99999!important;}
</style>
@endsection
