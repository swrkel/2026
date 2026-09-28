<div class="box box-success">
    <div class="box-header with-border">
        <h3 class="box-title">@lang('petrogeneral::lang.pump_overview')</h3>
    </div>
    <div class="box-body table-responsive">
        <table class="table table-bordered table-striped pg-table">
            <thead>
                <tr>
                    <th>@lang('petrogeneral::lang.pump_no')</th>
                    <th>@lang('petrogeneral::lang.pump_name')</th>
                    <th>@lang('petrogeneral::lang.product')</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pump_summary as $pump)
                    <tr>
                        <td>{{ $pump->pump_no ?? '' }}</td>
                        <td>{{ $pump->pump_name ?? '' }}</td>
                        {{-- MA-002: the NAME, not the database id. --}}
                        <td>{{ $pump->product_name ?? '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted">@lang('petrogeneral::lang.no_records_found')</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
