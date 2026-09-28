<div class="tab-pane {{ $active_tab == 'pumps' ? 'active' : '' }}" id="pg_pumps">
    <div class="box box-primary"><div class="box-header with-border"><h3 class="box-title">@lang('petrogeneral::lang.pumps')</h3></div>
    <div class="box-body table-responsive"><table class="table table-bordered table-striped pg-table" id="pg_pumps_table"><thead><tr><th>@lang('petrogeneral::lang.pump_no')</th><th>@lang('petrogeneral::lang.pump_name')</th><th>@lang('petrogeneral::lang.product')</th></tr></thead><tbody>
    @forelse($pumps as $pump)<tr><td>{{ $pump->pump_no ?? '' }}</td><td>{{ $pump->pump_name ?? '' }}</td><td>{{ $pump->product_name ?? '' }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>@endforelse
    </tbody></table></div></div>
</div>
