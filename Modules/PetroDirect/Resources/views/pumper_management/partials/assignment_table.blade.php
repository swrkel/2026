<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $title }}</h3>
        @if(!empty($show_add))
            <div class="box-tools"><a href="{{ route('petrodirect.pumper-management.assign-pumps') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> @lang('petrodirect::lang.new_assignment')</a></div>
        @endif
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="{{ $table_id }}" style="width: 100%;">
                <thead><tr>
                    <th>@lang('petrodirect::lang.date')</th>
                    <th>@lang('petrodirect::lang.pump_operator')</th>
                    <th>@lang('petrodirect::lang.pump')</th>
                    <th>@lang('petrodirect::lang.shift_no')</th>
                    <th>@lang('petrodirect::lang.location')</th>
                    <th>@lang('petrodirect::lang.status')</th>
                </tr></thead>
            </table>
        </div>
    </div>
</div>
