<div class="tab-pane {{ $active_tab == 'testing' ? 'active' : '' }}" id="pg_testing">
    <div class="box box-warning"><div class="box-header with-border"><h3 class="box-title">@lang('petrogeneral::lang.testing_details')</h3></div>
    <div class="box-body table-responsive"><table class="table table-bordered table-striped pg-table" id="pg_testing_details_table"><thead><tr><th>@lang('petrogeneral::lang.date')</th><th>@lang('petrogeneral::lang.pump')</th><th class="text-right">@lang('petrogeneral::lang.quantity')</th></tr></thead><tbody>
    @forelse($testing_details as $row)<tr><td>{{ $row->date ?? $row->created_at ?? '' }}</td><td>{{ $row->pump_id ?? '' }}</td><td class="text-right">{{ @num_format($row->qty ?? $row->quantity ?? 0) }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>@endforelse
    </tbody></table></div></div>
</div>
