{{--
    The summary table for one shift.

    Operators down, payment types across. Both sets of totals are shown because
    both questions get asked: how much did this operator take, and how much came
    in by cheque.
--}}

@php
    $labels = [
        'cash' => __('sw::lang.cash'),
        'card' => __('sw::lang.card'),
        'cheque' => __('sw::lang.cheque'),
        'credit' => __('sw::lang.credit_sales'),
    ];
@endphp

@component('components.widget', [
    'class' => 'box-primary',
    'title' => __('sw::lang.collection_summary') . ' — ' . $shift->sw_shift_no,
])

    <div style="padding:0 14px 10px;color:#69758b;font-size:13px;border-bottom:1px solid #eef1f6;margin-bottom:6px">
        <strong>{{ \Carbon\Carbon::parse($shift->shift_date)->format('d/m/Y') }}</strong>
        @if ($shift->shift_name)
            &nbsp;·&nbsp; {{ $shift->shift_name }}
        @endif
        &nbsp;·&nbsp; {{ $shift->statusLabel() }}
    </div>

    @if (empty($summary['rows']))
        <div class="text-center text-muted" style="padding:30px">
            @lang('sw::lang.nothing_recorded')
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered sw-cls-table">
                <thead>
                    <tr>
                        <th>@lang('sw::lang.pump_operator')</th>
                        @foreach ($types as $type)
                            <th class="num">{{ $labels[$type] ?? ucfirst($type) }}</th>
                        @endforeach
                        <th class="num">@lang('sale.total')</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($summary['rows'] as $row)
                        <tr>
                            <td class="sw-cls-operator">{{ $row['operator'] }}</td>
                            @foreach ($types as $type)
                                <td class="num @if ($row['amounts'][$type] == 0) sw-cls-zero @endif">
                                    {{ number_format($row['amounts'][$type], 2) }}
                                </td>
                            @endforeach
                            <td class="num"><strong>{{ number_format($row['total'], 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <td>@lang('sale.total')</td>
                        @foreach ($types as $type)
                            <td class="num">{{ number_format($summary['column_totals'][$type], 2) }}</td>
                        @endforeach
                        <td class="num">{{ number_format($summary['grand_total'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

@endcomponent
