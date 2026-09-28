<div class="card mb-3 tailoring-filter-card">
    <div class="card-body">
        <form method="GET" class="row align-items-end">
            <div class="col-md-3"><label>Branch / Location</label><select name="location_id" class="form-control"><option value="">Consolidated / All Branches</option></select></div>
            <div class="col-md-2"><label>From</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}"></div>
            <div class="col-md-2"><label>To</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}"></div>
            <div class="col-md-3"><label>Search</label><input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search..."></div>
            <div class="col-md-2"><button class="btn btn-primary btn-block" type="submit">Apply</button></div>
        </form>
    </div>
</div>
