<div class="box box-primary">
    <div class="box-body">
        <form method="GET" class="form-inline">
            <div class="form-group" style="margin-right:10px;">
                <label>From</label>
                <input type="date" name="start_date" value="{{ $filters['start_date'] ?? request('start_date') }}" class="form-control input-sm">
            </div>
            <div class="form-group" style="margin-right:10px;">
                <label>To</label>
                <input type="date" name="end_date" value="{{ $filters['end_date'] ?? request('end_date') }}" class="form-control input-sm">
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
            <a href="{{ url()->current() }}" class="btn btn-default btn-sm"><i class="fa fa-refresh"></i> Reset</a>
            @if(!empty($reportKey))
                <a href="{{ route('myhealth.reports.export', array_merge(['report' => $reportKey], request()->query())) }}" class="btn btn-success btn-sm pull-right"><i class="fa fa-file-excel-o"></i> CSV Export</a>
            @endif
        </form>
    </div>
</div>
