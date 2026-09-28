{{--
    Petro General / Dip Management / Readings.

    Was a placeholder that showed a "separated for maintenance" note and no data,
    even though DipIndexController was already passing $readings in.

    It now lists those dip_readings rows, so a reading added through the
    Resettings form - a resetting writes the new dip against the tank - is
    visible here straight afterwards.
--}}
<div class="tab-pane {{ $active_tab == 'readings' ? 'active' : '' }}" id="pg_dip_readings">

    @component('components.widget', ['class' => 'box-primary', 'title' => __('petrogeneral::lang.dip_readings')])

    <div class="table-responsive">
        <table class="table table-bordered table-striped" style="width: 100%;">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.add_dip_no')</th>
                    <th>@lang('petrogeneral::lang.date')</th>
                    <th>@lang('petrogeneral::lang.tank')</th>
                    <th>@lang('petrogeneral::lang.dip_reading')</th>
                    <th>@lang('petrogeneral::lang.current_qty')</th>
                    <th>@lang('petrogeneral::lang.reset_new_dip')</th>
                    <th>@lang('petrogeneral::lang.note')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($readings as $reading)
                    <tr>
                        <td>{{ $reading->ref_number ?? '' }}</td>
                        <td>{{ !empty($reading->transaction_date) ? $reading->transaction_date : ($reading->date_and_time ?? '') }}</td>
                        <td>{{ $pgDipTankNames[$reading->tank_id] ?? $reading->tank_id }}</td>
                        <td>{{ $reading->dip_reading ?? '' }}</td>
                        <td>{{ $reading->current_qty ?? '' }}</td>
                        <td>{{ $reading->reset_new_dip ?? '' }}</td>
                        <td>{{ $reading->note ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            @lang('petrogeneral::lang.no_records_found')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @endcomponent
</div>
