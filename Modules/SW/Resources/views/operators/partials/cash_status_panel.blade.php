{{--
    The Balance In Hand panel for one shift.

    Each line expands to the entries behind it, and carries a checkbox deciding
    whether that section is included when the statement is printed.

    Nothing here is stored. Every figure is computed from the entries carrying
    this shift number, because a balance written down once disagrees with its
    own workings the moment anything is corrected.
--}}

@php
    $sections = [
        'collection' => ['+', __('sw::lang.cash_collection'), 'fa-money'],
        'customer_payments' => ['+', __('sw::lang.customer_payment_cash'), 'fa-user'],
        'expenses' => ['−', __('sw::lang.cash_expenses'), 'fa-file-text-o'],
        'purchases' => ['−', __('sw::lang.cash_purchases'), 'fa-shopping-cart'],
        'deposits' => ['−', __('sw::lang.cash_deposit'), 'fa-university'],
    ];
@endphp

<div class="row">
    <div class="col-md-8">

        @component('components.widget', [
            'class' => 'box-primary',
            'title' => __('sw::lang.balance_in_hand') . ' — ' . $shift->sw_shift_no,
        ])

            <div style="padding:0 0 10px 0;border-bottom:1px solid #eef1f6;margin-bottom:10px">
                <div style="padding:0 16px;color:#69758b;font-size:13px">
                    <strong>{{ \Carbon\Carbon::parse($shift->shift_date)->format('d/m/Y') }}</strong>
                    @if ($shift->shift_name)
                        &nbsp;·&nbsp; {{ $shift->shift_name }}
                    @endif
                    @if ($operators->count())
                        &nbsp;·&nbsp; {{ $operators->implode(', ') }}
                    @else
                        &nbsp;·&nbsp; <span class="text-danger">@lang('sw::lang.none_assigned')</span>
                    @endif
                </div>
            </div>

            @foreach ($sections as $key => $meta)
                @php
                    [$sign, $label, $icon] = $meta;
                    $section = $figures[$key];
                @endphp

                <div class="sw-cs-line" data-section="{{ $key }}">
                    <span class="sw-cs-caret"><i class="fa fa-chevron-right"></i></span>
                    <span class="sw-cs-sign">{{ $sign }}</span>
                    <span class="sw-cs-label">
                        <i class="fa {{ $icon }} text-muted"></i>
                        {{ $label }}
                        <span class="sw-cs-count">
                            &nbsp;({{ $section['count'] }})
                        </span>
                    </span>
                    <label class="sw-cs-print" style="margin:0;font-weight:400;font-size:12px;color:#8a94a6">
                        <input type="checkbox" class="sw-cs-print-check"
                            name="print_sections[]" value="{{ $key }}" checked>
                        @lang('sw::lang.print')
                    </label>
                    <span class="sw-cs-amount display_currency" data-currency_symbol="true">
                        {{ number_format($section['total'], 2) }}
                    </span>
                </div>

                <div class="sw-cs-detail" id="sw_cs_detail_{{ $key }}" style="display:none">
                    @if ($section['count'] === 0)
                        <div class="sw-cs-empty">@lang('sw::lang.nothing_recorded')</div>
                    @else
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th style="padding-left:46px">@lang('sw::lang.date')</th>
                                    <th>@lang('sw::lang.reference')</th>
                                    <th>@lang('sw::lang.details')</th>
                                    <th class="text-right">@lang('sw::lang.amount')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($section['rows'] as $row)
                                    <tr>
                                        <td style="padding-left:46px">
                                            {{ $row->date ? \Carbon\Carbon::parse($row->date)->format('d/m/Y') : '—' }}
                                        </td>
                                        <td>{{ $row->reference ?: '—' }}</td>
                                        <td>
                                            {{ $row->party ?: '—' }}
                                            @if (! empty($row->note))
                                                <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($row->note, 60) }}</small>
                                            @endif
                                        </td>
                                        <td class="text-right">{{ number_format((float) $row->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endforeach

            <div class="sw-cs-total">
                <span class="sw-cs-label">@lang('sw::lang.balance_in_hand')</span>
                <span class="sw-cs-amount @if ($figures['balance'] < 0) sw-cs-negative @endif">
                    {{ number_format($figures['balance'], 2) }}
                </span>
            </div>

            @if ($figures['balance'] < 0)
                <div class="alert alert-warning" style="margin:12px 16px 0;font-size:13px">
                    <i class="fa fa-exclamation-triangle"></i>
                    @lang('sw::lang.negative_balance_warning')
                </div>
            @endif

        @endcomponent

    </div>

    <div class="col-md-4">
        @component('components.widget', ['class' => 'box-default', 'title' => __('messages.actions')])

            {{-- Closing from here runs the same check as the Daily Shift tab: a
                 shift with nobody assigned has nothing to reconcile. --}}
            @if ($operators->count() === 0)
                <div class="alert alert-warning" style="font-size:13px">
                    @lang('sw::lang.assign_before_closing')
                </div>
            @endif

            {!! Form::open(['url' => route('sw.cash-status.close'), 'method' => 'post']) !!}
                {!! Form::hidden('sw_shift_id', $shift->id) !!}

                <button type="submit" class="btn btn-primary btn-block"
                    @if ($operators->count() === 0) disabled @endif
                    onclick="return confirm('{{ __('sw::lang.confirm_close_with_balance', ['balance' => number_format($figures['balance'], 2)]) }}')">
                    <i class="fa fa-lock"></i> @lang('sw::lang.close_shift')
                </button>
            {!! Form::close() !!}

            <div class="text-muted" style="font-size:12px;margin-top:10px">
                @lang('sw::lang.close_removes_from_this_form')<br>
                A professional Shift Closing Statement will open immediately after closing.
            </div>

        @endcomponent
    </div>
</div>
