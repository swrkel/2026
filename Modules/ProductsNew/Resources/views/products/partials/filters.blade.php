<form class="pn-card pn-filter-card" method="get" action="{{ route('products-new.products.index') }}" data-pn-auto-filter-form>
    <div class="row">
        <div class="col-md-3"><label>Search</label><input class="form-control" name="search" value="{{ request('search') }}" placeholder="Name, SKU, barcode" data-pn-auto-filter-search></div>
        <div class="col-md-2"><label>Category</label><select class="form-control" name="category_id" data-pn-auto-filter><option value="">All</option>@foreach(($lookups['categories'] ?? []) as $category)<option value="{{ $category->id }}" @selected(request('category_id')==$category->id)>{{ $category->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label>Brand</label><select class="form-control" name="brand_id" data-pn-auto-filter><option value="">All</option>@foreach(($lookups['brands'] ?? []) as $brand)<option value="{{ $brand->id }}" @selected(request('brand_id')==$brand->id)>{{ $brand->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label>Status</label><select class="form-control" name="status" data-pn-auto-filter><option value="">All</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select></div>
        <div class="col-md-2"><label>Stock Type</label><select class="form-control" name="stock_type" data-pn-auto-filter><option value="">All</option><option value="stock" @selected(request('stock_type')==='stock')>Stock</option><option value="non_stock" @selected(request('stock_type')==='non_stock')>Non Stock</option></select></div>
        <div class="col-md-1 pn-filter-button"><button class="pn-btn pn-btn-primary" type="submit">Filter</button></div>
    </div>
</form>

@once
@push('javascript')
<script>
(function (window, document) {
    'use strict';
    function initAutoFilter(form) {
        if (!form || form.dataset.pnAutoFilterReady === '1') return;
        form.dataset.pnAutoFilterReady = '1';
        var timer = null, controller = null, requestSerial = 0;
        var tableRegion = document.getElementById('pn-products-table-region');
        var pagination = document.getElementById('pn-products-pagination');
        var totalLabel = document.getElementById('pn-products-total-label');
        var listCard = document.getElementById('pn-products-list-card');
        function currentUrl(pageUrl) {
            if (pageUrl) return pageUrl;
            var params = new URLSearchParams(new FormData(form));
            params.delete('page');
            return form.action + (params.toString() ? '?' + params.toString() : '');
        }
        function setBusy(busy) {
            form.setAttribute('aria-busy', busy ? 'true' : 'false');
            if (listCard) { listCard.style.opacity = busy ? '0.72' : ''; listCard.style.pointerEvents = busy ? 'none' : ''; }
        }
        function loadResults(pageUrl) {
            var url = currentUrl(pageUrl), serial = ++requestSerial;
            if (timer) { window.clearTimeout(timer); timer = null; }
            if (controller && typeof controller.abort === 'function') controller.abort();
            controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
            setBusy(true);
            return window.fetch(url, {method:'GET', credentials:'same-origin', headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}, signal:controller ? controller.signal : undefined})
                .then(function (response) { if (!response.ok) throw new Error('Filter request failed'); return response.json(); })
                .then(function (payload) {
                    if (serial !== requestSerial) return;
                    if (!payload || typeof payload.table_html !== 'string') throw new Error('Invalid filter response');
                    if (tableRegion) tableRegion.innerHTML = payload.table_html;
                    if (pagination) pagination.innerHTML = payload.pagination_html || '';
                    if (totalLabel) totalLabel.textContent = payload.total_label || '';
                    window.history.replaceState({}, '', payload.url || url);
                }).catch(function (error) {
                    if (error && error.name === 'AbortError') return;
                    window.location.assign(url);
                }).finally(function () { if (serial === requestSerial) setBusy(false); });
        }
        function scheduleSearch() {
            if (timer) window.clearTimeout(timer);
            timer = window.setTimeout(function () { loadResults(); }, 120);
        }
        form.addEventListener('submit', function (event) { event.preventDefault(); loadResults(); });
        form.querySelectorAll('[data-pn-auto-filter]').forEach(function (field) { field.addEventListener('change', function () { loadResults(); }); });
        form.querySelectorAll('[data-pn-auto-filter-search]').forEach(function (field) { field.addEventListener('input', scheduleSearch); field.addEventListener('search', scheduleSearch); });
        document.addEventListener('click', function (event) {
            var link = event.target.closest('#pn-products-pagination a');
            if (!link) return; event.preventDefault(); loadResults(link.href);
        });
    }
    document.querySelectorAll('[data-pn-auto-filter-form]').forEach(initAutoFilter);
})(window, document);
</script>
@endpush
@endonce
