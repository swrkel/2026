<div class="tab-pane {{ $active_tab == 'meters' ? 'active' : '' }}" id="pg_meters">
    <div class="box box-info"><div class="box-header with-border"><h3 class="box-title">@lang('petrogeneral::lang.meter_readings')</h3></div>
    <div class="box-body table-responsive"><table class="table table-bordered table-striped pg-table" id="pg_meter_readings_table"><thead><tr><th>@lang('petrogeneral::lang.date')</th><th>@lang('petrogeneral::lang.shift_number')</th><th class="text-right">@lang('petrogeneral::lang.amount')</th></tr></thead><tbody>
    @forelse($meter_readings as $row)<tr><td>{{ $row->date ?? $row->created_at ?? '' }}</td><td>{{ $row->shift_no ?? $row->shift_number ?? '' }}</td><td class="text-right">{{ @num_format($row->amount ?? 0) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>@endforelse
    </tbody></table></div></div>
</div>
