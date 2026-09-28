<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $title }}</h3>
        <div class="box-tools">
            @if(!empty($show_excess_add))
                <a href="{{ route('petrodirect.pumper-management.pay-excess') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> @lang('petrodirect::lang.add_excess_payment')</a>
            @endif
            @if(!empty($show_shortage_add))
                <a href="{{ route('petrodirect.pumper-management.recover-shortage') }}" class="btn btn-primary btn-sm"><i class="fa fa-plus"></i> @lang('petrodirect::lang.add_shortage_recovery')</a>
            @endif
        </div>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="{{ $table_id }}" style="width: 100%;">
                <thead><tr>
                    <th>@lang('petrodirect::lang.date_time')</th>
                    <th>@lang('petrodirect::lang.pump_operator')</th>
                    <th>@lang('petrodirect::lang.type')</th>
                    <th>@lang('petrodirect::lang.collection_form_no')</th>
                    <th><span>Settlement</span><br><span>No</span></th>
                    <th>@lang('petrodirect::lang.amount')</th>
                    <th>@lang('petrodirect::lang.note')</th>
                </tr></thead>
            </table>
        </div>
    </div>
</div>
