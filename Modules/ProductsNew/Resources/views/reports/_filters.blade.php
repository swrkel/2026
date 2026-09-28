<div class="productsnew-filter-card">
    <form method="GET" class="productsnew-filter-grid">
        <div><label>Search</label><input type="text" name="keyword" value="{{ request('keyword') }}" class="form-control" placeholder="SKU / Name / Barcode"></div>
        <div><label>From Date</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
        <div><label>To Date</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
        <div><label>Category ID</label><input type="text" name="category_id" value="{{ request('category_id') }}" class="form-control"></div>
        <div><label>Brand ID</label><input type="text" name="brand_id" value="{{ request('brand_id') }}" class="form-control"></div>
        <div class="productsnew-filter-actions"><button class="btn btn-primary productsnew-btn-white" type="submit">Search</button><a href="{{ url()->current() }}" class="btn btn-default">Reset</a></div>
    </form>
</div>
