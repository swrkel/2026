<div class="box box-primary">
    <div class="box-body">
        <div class="row">
            <div class="col-md-3">
                {!! Form::label('purchase_dashboard_start_date', __('purchase::dashboard.start_date')) !!}
                {!! Form::text('purchase_dashboard_start_date', $filters['start_date'] ?? date('Y-m-01'), ['class' => 'form-control purchase-dashboard-filter', 'placeholder' => 'YYYY-MM-DD']) !!}
            </div>
            <div class="col-md-3">
                {!! Form::label('purchase_dashboard_end_date', __('purchase::dashboard.end_date')) !!}
                {!! Form::text('purchase_dashboard_end_date', $filters['end_date'] ?? date('Y-m-d'), ['class' => 'form-control purchase-dashboard-filter', 'placeholder' => 'YYYY-MM-DD']) !!}
            </div>
        </div>
    </div>
</div>
