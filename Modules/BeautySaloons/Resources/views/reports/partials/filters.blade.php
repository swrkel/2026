<form method="GET" class="bs-report-filter-row">
    <div class="row">
        <div class="col-md-3">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
        </div>
        <div class="col-md-3">
            <label>Status</label>
            <input type="text" name="status" class="form-control" value="{{ $filters['status'] ?? '' }}" placeholder="Optional">
        </div>
        <div class="col-md-3 bs-report-filter-actions">
            <button class="btn btn-primary btn-block" type="submit">Search</button>
        </div>
    </div>
</form>
