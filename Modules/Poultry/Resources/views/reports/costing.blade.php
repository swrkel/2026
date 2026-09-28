@extends('poultry::layouts.app')
@section('title', __('poultry::lang.costing'))
@section('content')
{{-- Broiler and layer results are not directly comparable: one is a work in
     progress job measured per kg, the other an amortising asset measured per
     egg. They are therefore shown as separate blocks rather than one table. --}}
<div class="alert alert-info">@lang('poultry::lang.costing_note')</div>

@forelse ($rows as $row)
    <div class="box box-{{ $row['result']['treatment'] === 'amortising_asset' ? 'warning' : 'primary' }}">
        <div class="box-header with-border">
            <h3 class="box-title">
                <a href="{{ route('poultry.batch.show', $row['batch']->id) }}">{{ $row['batch']->batch_code }}</a>
                <small>{{ \Modules\Poultry\Entities\Batch::BIRD_TYPES[$row['batch']->bird_type] ?? '' }}</small>
            </h3>
            <span class="label label-default pull-right">
                {{ $row['result']['treatment'] === 'amortising_asset'
                    ? __('poultry::lang.amortising_asset') : __('poultry::lang.work_in_progress') }}
            </span>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-condensed">
                        @foreach ($row['result'] as $key => $value)
                            @if ($key !== 'treatment' && $value !== null)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                                    <td class="text-right">
                                        <strong>{{ is_numeric($value) ? number_format($value, 4) : $value }}</strong>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-condensed">
                        <thead><tr>
                            <th>@lang('poultry::lang.cost_type')</th>
                            <th class="text-right">@lang('poultry::lang.amount')</th>
                            <th class="text-right">%</th>
                        </tr></thead>
                        <tbody>
                        @foreach ($row['breakdown']['lines'] as $line)
                            <tr>
                                <td>{{ $line['label'] }}</td>
                                <td class="text-right">{{ number_format($line['amount'], 2) }}</td>
                                <td class="text-right">{{ $line['pct'] }}%</td>
                            </tr>
                        @endforeach
                        <tr class="active">
                            <td><strong>@lang('poultry::lang.total')</strong></td>
                            <td class="text-right"><strong>{{ number_format($row['breakdown']['total'], 2) }}</strong></td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@empty
    <p class="text-muted">@lang('poultry::lang.no_records')</p>
@endforelse
@endsection
