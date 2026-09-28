{{-- IS2201: selected-shift context only.

     The main create form already contains the editable Meter Sales, Credit
     Sales and Payments sections. The old assembled body repeated Meter Sales
     and Collections below them. Remove only those duplicate summary sections.
--}}

@php
    $bundle = $bundle ?? [];
    $shiftNumbers = $bundle['shift_numbers'] ?? '';
@endphp

<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info" style="font-size:13px">
            <i class="fa fa-info-circle"></i>
            @lang('sw::lang.settling_shifts', ['numbers' => $shiftNumbers])
            @if (! empty($bundle['operators']))
                &nbsp;·&nbsp; {{ implode(', ', $bundle['operators']) }}
            @endif
        </div>
    </div>
</div>

{{-- Shortage / Excess is useful settlement context and is not duplicated by
     the Meter Sales/Collections summary that IS2201 asks to remove. --}}
@if (! empty($bundle['shortage_excess']['rows']) && $bundle['shortage_excess']['rows']->count())
    @component('components.widget', ['class' => 'box-warning', 'title' => __('sw::lang.daily_shortage_excess')])
        <div class="row" style="padding:0 15px">
            <div class="col-md-6">
                <strong>@lang('sw::lang.total_shortage'):</strong>
                {{ number_format((float) $bundle['shortage_excess']['short'], 2) }}
            </div>
            <div class="col-md-6">
                <strong>@lang('sw::lang.total_excess'):</strong>
                {{ number_format((float) $bundle['shortage_excess']['excess'], 2) }}
            </div>
        </div>
        <div class="text-muted" style="font-size:12px;padding:10px 15px 0">
            @lang('sw::lang.shortage_settles_here')
        </div>
    @endcomponent
@endif
