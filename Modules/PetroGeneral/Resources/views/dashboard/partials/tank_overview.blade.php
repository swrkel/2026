<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('petrogeneral::lang.tank_overview')</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped pg-table">
            <thead>
                {{--
                    MA-002: "Tank Name" replaced by Fuel Type and Current Balance.

                    fuel_tanks has NO fuel_tank_name column - the table has 17 and
                    that is not one of them. A tank is identified by its NUMBER.
                    So that column could never fill; it rendered blank on every row
                    and the query behind it threw
                        Unknown column 'fuel_tank_name' in 'SELECT'

                    Fuel Type and Current Balance are real columns and are what you
                    actually want on a dashboard - how full each tank is, not a name
                    it does not have.
                --}}
                <tr>
                    <th>@lang('petrogeneral::lang.tank_no')</th>
                    <th>@lang('petrogeneral::lang.fuel_type')</th>
                    <th class="text-right">@lang('petrogeneral::lang.storage_volume')</th>
                    <th class="text-right">@lang('petrogeneral::lang.current_balance')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tanks as $tank)
                    <tr>
                        <td>{{ $tank->fuel_tank_number ?? '' }}</td>
                        <td>{{ $tank->fuel_type ?? '' }}</td>
                        {{-- MA-002: whole numbers.
                             @num_format applies the business CURRENCY precision -
                             2 or 3 decimals depending on the business - which is
                             right for money and wrong for a tank reading. --}}
                        <td class="text-right">{{ number_format((float) ($tank->storage_volume ?? 0)) }}</td>
                        <td class="text-right">{{ number_format((float) ($tank->current_balance ?? 0)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
