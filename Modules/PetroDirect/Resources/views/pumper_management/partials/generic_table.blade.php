<div class="box box-primary">
    <div class="box-header with-border">
        <h3 class="box-title">{{ $title }}</h3>
    </div>
    <div class="box-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="{{ $table_id }}" style="width: 100%;">
                <thead><tr>
                    <th>@lang('petrodirect::lang.date')</th>
                    <th>@lang('petrodirect::lang.reference')</th>
                    <th>@lang('petrodirect::lang.amount')</th>
                    <th>@lang('petrodirect::lang.created_at')</th>
                </tr></thead>
            </table>
        </div>
    </div>
</div>
