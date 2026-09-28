<div class="box box-solid supplier-financial-filters">
    <div class="box-body row">
        <div class="col-md-3 col-sm-6">
            <label>@lang('suppliers::lang.start_date')</label>
            <input type="text" name="start_date" class="form-control supplier-date" placeholder="YYYY-MM-DD" value="{{ $filters['start_date'] ?? '' }}">
        </div>
        <div class="col-md-3 col-sm-6">
            <label>@lang('suppliers::lang.end_date')</label>
            <input type="text" name="end_date" class="form-control supplier-date" placeholder="YYYY-MM-DD" value="{{ $filters['end_date'] ?? '' }}">
        </div>
        <div class="col-md-3 col-sm-6">
            <label>@lang('suppliers::lang.search')</label>
            <input type="text" name="search" class="form-control supplier-search" value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-3 col-sm-6 supplier-filter-actions">
            <button type="button" class="btn btn-primary btn-flat supplier-apply-filter">@lang('suppliers::lang.apply')</button>
        </div>
    </div>
</div>
