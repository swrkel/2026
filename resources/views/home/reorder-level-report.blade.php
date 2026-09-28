<style>
    /* Custom Modal Styles */
    .custom-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .custom-modal-overlay.active {
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 1;
    }

    .custom-modal {
        background: white;
        border-radius: 8px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        max-width: 95%;
        max-height: 95%;
        width: 1200px;
        overflow: hidden;
        transform: scale(0.7);
        transition: transform 0.3s ease;
    }

    .custom-modal-overlay.active .custom-modal {
        transform: scale(1);
    }

    .custom-modal-header {
        background: linear-gradient(135deg, #5C2AAE, #7C4DFF);
        color: white;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: none;
    }

    .custom-modal-title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .custom-modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.3s ease;
    }

    .custom-modal-close:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .custom-modal-body {
        padding: 0;
        max-height: 80vh;
        overflow-y: auto;
    }

    .custom-tab-header {
        display: flex;
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        margin: 0;
        padding: 0;
    }

    .custom-tab-button {
        flex: 1;
        padding: 15px 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 500;
        color: #6c757d;
        border-bottom: 3px solid transparent;
        transition: all 0.3s ease;
    }

    .custom-tab-button.active {
        color: #5C2AAE;
        border-bottom-color: #5C2AAE;
        background-color: white;
    }

    .custom-tab-button:hover {
        background-color: #e9ecef;
    }

    .custom-tab-content {
        padding: 20px;
    }

    .custom-tab-pane {
        display: none;
    }

    .custom-tab-pane.active {
        display: block;
    }

    .filter-section {
        background-color: #f8f9fa;
        padding: 20px;
        margin: -20px -20px 20px -20px;
        border-bottom: 1px solid #dee2e6;
    }

    .export-toolbar {
        background-color: #ffffff;
        padding: 15px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .export-btn {
        margin-right: 10px;
        margin-bottom: 5px;
    }

    .loading-spinner {
        text-align: center;
        padding: 40px;
        display: none;
    }

    .loading-spinner.show {
        display: block;
    }

    .spinner {
        width: 40px;
        height: 40px;
        margin: 0 auto 20px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #5C2AAE;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .reorder-table-container {
        overflow-x: auto;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }

    .reorder-table {
        width: 100%;
        margin-bottom: 0;
    }

    .reorder-table th {
        background-color: #343a40;
        color: white;
        font-weight: 600;
        padding: 12px 8px;
        border: none;
        white-space: nowrap;
    }

    .reorder-table td {
        padding: 10px 8px;
        border-bottom: 1px solid #dee2e6;
        vertical-align: middle;
    }

    .table-danger {
        background-color: #f8d7da !important;
    }

    .table-warning {
        background-color: #fff3cd !important;
    }

    .table-success {
        background-color: #d4edda !important;
    }

    .badge {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }

    .no-data {
        text-align: center;
        padding: 40px;
        color: #6c757d;
        font-style: italic;
    }

    .form-control:focus {
        border-color: #5C2AAE;
        box-shadow: 0 0 0 0.2rem rgba(92, 42, 174, 0.25);
    }

    .btn-primary {
        background-color: #5C2AAE;
        border-color: #5C2AAE;
    }

    .btn-primary:hover {
        background-color: #4a1d91;
        border-color: #4a1d91;
    }
</style>
<!-- Custom Modal -->
<div class="custom-modal-overlay" id="reorderModalOverlay">
    <div class="custom-modal">
        <!-- Modal Header -->
        <div class="custom-modal-header">
            <h4 class="custom-modal-title">
                <i class="fas fa-exclamation-triangle"></i> Products Reorder Level Report
            </h4>
            <button class="custom-modal-close" onclick="closeReorderModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="custom-modal-body">
            <!-- Tab Headers -->
            <div class="custom-tab-header">
                <button class="custom-tab-button active" onclick="switchTab('current')" id="currentTab">
                    Products Reorder Level Report
                </button>
                <button class="custom-tab-button" onclick="switchTab('old')" id="oldTab">
                    Old Product Reorder List
                </button>
            </div>

            <!-- Current Reorder Tab -->
            <div class="custom-tab-pane active" id="currentPane">
                <div class="custom-tab-content">
                    <!-- Filters -->
                    <div class="filter-section">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="modal_location_id" class="font-weight-bold">Location</label>
                                <select id="modal_location_id" class="form-control "
                                    onchange="loadProducts();">
                                    <option value="1">Main Location</option>
                                    <!-- Add your locations dynamically -->
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="modal_category_id" class="font-weight-bold">Product
                                    Category</label>
                                <select id="modal_category_id" class="form-control select2 "
                                    onchange="loadProducts();">
                                    <option value="">All Categories</option>
                                </select>

                            </div>

                            <div class="col-md-4">
                                <label for="modal_product_id" class="font-weight-bold">Product</label>
                                <select id="modal_product_id" class="form-control select2" onchange="loadReorderData();">
                                    <option value="">All Products</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Export Toolbar -->
                    <div class="export-toolbar">
                        <h6 class="mb-3"><i class="fas fa-download"></i> Export Options</h6>
                        <button class="btn btn-success btn-sm export-btn" onclick="exportReorderData('csv')">
                            <i class="fas fa-file-csv"></i> Export to CSV
                        </button>
                        <button class="btn btn-success btn-sm export-btn" onclick="exportReorderData('excel')">
                            <i class="fas fa-file-excel"></i> Export to Excel
                        </button>
                        <button class="btn btn-info btn-sm export-btn" onclick="toggleColumns()">
                            <i class="fas fa-columns"></i> Column Visibility
                        </button>
                        <button class="btn btn-danger btn-sm export-btn" onclick="exportReorderData('pdf')">
                            <i class="fas fa-file-pdf"></i> Export to PDF
                        </button>
                        <button class="btn btn-secondary btn-sm export-btn" onclick="printReorderReport()">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>

                    <!-- Loading Spinner -->
                    <div class="loading-spinner" id="loadingSpinner">
                        <div class="spinner"></div>
                        <p>Loading reorder report...</p>
                    </div>

                    <!-- Data Table -->
                    <div class="reorder-table-container" id="tableContainer">
                        <table class="reorder-table table table-striped">
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Sub Category</th>
                                    <th>Last Purchase Price</th>
                                    <th>Last Supplier</th>
                                    <th>Current Qty</th>
                                    <th>Alert Qty</th>
                                </tr>
                            </thead>
                            <tbody id="reorderTableBody">
                                <!-- Data will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Old Reorder List Tab -->
            <div class="custom-tab-pane" id="oldPane">
                <div class="custom-tab-content">
                    <div class="text-center" style="padding: 40px;">
                        <h5>Old Product Reorder List</h5>
                        <p class="text-muted">Products that were previously below alert level but are now
                            restocked</p>
                        <button class="btn btn-primary" onclick="loadOldReorderList()">
                            <i class="fas fa-refresh"></i> Load Old Reorder List
                        </button>
                    </div>
                    <div id="oldReorderContent"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="custom-modal-overlay" id="columnModalOverlay">
    <div class="custom-modal" style="width: 400px;">
        <div class="custom-modal-header">
            <h5 class="custom-modal-title">Column Visibility</h5>
            <button class="custom-modal-close" onclick="closeColumnModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="custom-modal-body">
            <div style="padding: 20px;">
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="0" checked id="col0">
                    <label class="form-check-label" for="col0">Product Code</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="1" checked
                        id="col1">
                    <label class="form-check-label" for="col1">Product Name</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="2" checked
                        id="col2">
                    <label class="form-check-label" for="col2">Category</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="3" checked
                        id="col3">
                    <label class="form-check-label" for="col3">Sub Category</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="4" checked
                        id="col4">
                    <label class="form-check-label" for="col4">Last Purchase Price</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="5" checked
                        id="col5">
                    <label class="form-check-label" for="col5">Last Supplier</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="6" checked
                        id="col6">
                    <label class="form-check-label" for="col6">Current Qty</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggle" type="checkbox" value="7" checked
                        id="col7">
                    <label class="form-check-label" for="col7">Alert Qty</label>
                </div>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="applyColumnVisibility()">Apply
                        Changes</button>
                    <button class="btn btn-secondary btn-sm ml-2" onclick="closeColumnModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@section('javascript')
    <script>
        function initSelect2() {
            $('#modal_location_id, #modal_category_id, #modal_product_id').select2({
                width: '100%',
                dropdownParent: $('#reorderModalOverlay'),
                placeholder: 'Select an option',
                allowClear: true,
                minimumResultsForSearch: 0 // always show search box
            });
        }


        $(document).ready(function() {
            $(document).on('click', '#product-reorder-btn', function() {
                $('#reorderModalOverlay').addClass('active');
                document.body.style.overflow = 'hidden';

                const selectedLocation = $('#location_id').val();
                window.reorderInitializing = true;

                $.when(loadLocations())
                    .then(() => {
                        if (selectedLocation) {
                            $('#modal_location_id').val(selectedLocation);
                        }

                        return loadCategories();
                    })
                    .then(() => loadProducts())
                    .always(() => {
                        window.reorderInitializing = false;
                        loadReorderData();
                    });
            });

        });

        // Global variables (idempotent, avoid redeclaration on multiple script loads)
        window.currentReorderData = window.currentReorderData || [];
        window.oldReorderData = typeof window.oldReorderData === 'undefined' ? null : window.oldReorderData;
        window.oldReorderLoadedFor = window.oldReorderLoadedFor || null;
        window.reorderInitializing = window.reorderInitializing || false;

        // Modal Functions
        function openReorderModal() {
            const overlay = document.getElementById('reorderModalOverlay');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
            window.reorderInitializing = true;
            $.when(loadLocations())
                .then(function() {
                    return loadCategories();
                })
                .then(function() {
                    return loadProducts();
                })
                .always(function() {
                    window.reorderInitializing = false;
                    loadReorderData();
                });
        }

        function closeReorderModal() {
            const overlay = document.getElementById('reorderModalOverlay');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function switchTab(tab) {
            document.querySelectorAll('.custom-tab-button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.custom-tab-pane').forEach(p => p.classList.remove('active'));

            if (tab === 'current') {
                $('#currentTab').addClass('active');
                $('#currentPane').addClass('active');
            } else {
                $('#oldTab').addClass('active');
                $('#oldPane').addClass('active');

                loadOldReorderList();
            }
        }

        function loadCategories() {
            return $.ajax({
                url: '/get-categories',
                type: 'GET',
                success: function(categories) {

                    $('#modal_category_id').empty();
                    let options = '<option value="">All</option>';
                    let firstId = null; // ✅ define it

                    categories.forEach(function(category, index) {
                        if (index === 0) firstId = category.id; // store first ID
                        options += `<option value="${category.id}">${category.name}</option>`;
                    });

                    $('#modal_category_id').html(options);

                    initSelect2();

                    if (firstId) {
                        $('#modal_category_id').val(firstId);
                    }
                }
            });
        }

        function loadProducts() {
            const categoryId = $('#modal_category_id').val();
            const locationId = $('#modal_location_id').val();

            return $.ajax({
                url: '/get-products',
                type: 'GET',
                data: {
                    category_id: categoryId,
                    location_id: locationId
                },
                success: function(products) {

                    let options = '';
                    let firstId = null;

                    products.forEach((p, index) => {
                        if (index === 0) firstId = p.id;
                        options += `<option value="${p.id}">${p.code} - ${p.name}</option>`;
                    });

                    $('#modal_product_id').html(options);

                    initSelect2();

                    if (firstId) {
                        $('#modal_product_id').val(firstId);
                    } else {
                        $('#modal_product_id').val('');
                    }

                    if (!window.reorderInitializing) {
                        loadReorderData();
                    }
                }
            });
        }

        function loadReorderData() {
            if (window.reorderInitializing) {
                return;
            }

            showLoading();

            const data = {
                category_id: $('#modal_category_id').val(),
                product_id: $('#modal_product_id').val(),
                location_id: $('#modal_location_id').val()
            };

            window.currentReorderData = [];
            window.oldReorderData = null;
            window.oldReorderLoadedFor = null;

            $.ajax({
                url: '/reorder-report-data',
                type: 'GET',
                data: data,
                success: function(response) {
                    hideLoading();
                    window.currentReorderData = response.data || [];

                    renderReorderTable(window.currentReorderData);
                },
                error: function() {
                    hideLoading();
                    showError('Error loading reorder data');
                }
            });
        }

        function loadOldReorderList() {
            const data = {
                category_id: $('#modal_category_id').val(),
                product_id: $('#modal_product_id').val(),
                location_id: $('#modal_location_id').val()
            };
            const cacheKey = JSON.stringify(data);

            if (window.oldReorderLoadedFor === cacheKey && Array.isArray(window.oldReorderData)) {
                renderOldReorderTable(window.oldReorderData);
                return;
            }

            $.ajax({
                url: '/old-reorder-list',
                type: 'GET',
                data: data,
                success: function(response) {
                    window.oldReorderData = response.data || [];
                    window.oldReorderLoadedFor = cacheKey;
                    renderOldReorderTable(window.oldReorderData);
                },
                error: function() {
                    $('#oldReorderContent').html(
                        '<div class="alert alert-danger">Error loading old reorder list</div>');
                }
            });
        }

        // Rendering Functions
        function renderReorderTable(data) {
            let tbody = '';

            if (data && data.length > 0) {
                data.forEach(function(product) {
                    const rowClass = parseFloat(product.current_qty) === 0 ? 'table-danger' : 'table-warning';
                    const qtyBadge = parseFloat(product.current_qty) === 0 ? 'badge-danger' : 'badge-warning';

                    tbody += `
                                          <tr class="${rowClass}">
                                                <td>${product.code || ''}</td>
                                                <td>${product.name || ''}</td>
                                                <td>${product.category || ''}</td>
                                                <td>${product.sub_category || 'N/A'}</td>
                                                <td>${product.last_purchase_price || '0.00'}</td>
                                                <td>${product.last_supplier || 'N/A'}</td>
                                                <td><span class="badge ${qtyBadge}">${product.current_qty}</span></td>
                                                <td>${product.alert_qty}</td>
                                          </tr>
                                    `;
                });
            } else {
                tbody = '<tr><td colspan="8" class="no-data">No products found requiring reorder</td></tr>';
            }

            $('#reorderTableBody').html(tbody);
        }

        function renderOldReorderTable(data) {
            let content = `
                            <div class="reorder-table-container">
                                    <table class="reorder-table table table-striped">
                                    <thead>
                                        <tr>
                                                <th>Product Code</th>
                                                <th>Product Name</th>
                                                <th>Category</th>
                                                <th>Sub Category</th>
                                                <th>Last Purchase Price</th>
                                                <th>Last Supplier</th>
                                                <th>Current Qty</th>
                                                <th>Alert Qty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        `;

            if (data && data.length > 0) {
                data.forEach(function(product) {
                    content += `
                                <tr class="table-success">
                                    <td>${product.code || ''}</td>
                                    <td>${product.name || ''}</td>
                                    <td>${product.category || ''}</td>
                                    <td>${product.sub_category || 'N/A'}</td>
                                    <td>${product.last_purchase_price || '0.00'}</td>
                                    <td>${product.last_supplier || 'N/A'}</td>
                                    <td><span class="badge badge-success">${product.current_qty}</span></td>
                                    <td>${product.alert_qty}</td>
                                </tr>
                        `;
                });
            } else {
                content += '<tr><td colspan="8" class="no-data">No old reorder records found</td></tr>';
            }

            content += '</tbody></table></div>';
            $('#oldReorderContent').html(content);
        }

        // Utility Functions
        function showLoading() {
            $('#loadingSpinner').addClass('show');
            $('#tableContainer').hide();
        }

        function hideLoading() {
            $('#loadingSpinner').removeClass('show');
            $('#tableContainer').show();
        }

        function showError(message) {
            $('#reorderTableBody').html(`<tr><td colspan="8" class="no-data text-danger">${message}</td></tr>`);
        }

        // Export Functions
        function exportReorderData(format) {
            const data = {
                category_id: $('#modal_category_id').val(),
                product_id: $('#modal_product_id').val(),
                location_id: $('#modal_location_id').val(),
                format: format
            };

            const params = $.param(data);
            window.open('/export-reorder-report?' + params);
        }

        function printReorderReport() {
            const printWindow = window.open('', '_blank');
            const tableContent = document.getElementById('tableContainer').innerHTML;

            printWindow.document.write(`
                        <html>
                                <head>
                                <title>Products Reorder Level Report</title>
                                <style>
                                    body { font-family: Arial, sans-serif; margin: 20px; }
                                    table { width: 100%; border-collapse: collapse; }
                                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                                    th { background-color: #f2f2f2; }
                                    .table-danger { background-color: #f8d7da; }
                                    .table-warning { background-color: #fff3cd; }
                                    .badge { padding: 4px 8px; border-radius: 4px; }
                                    .badge-danger { background-color: #dc3545; color: white; }
                                    .badge-warning { background-color: #ffc107; color: black; }
                                </style>
                                </head>
                                <body>
                                <h2>Products Reorder Level Report</h2>
                                <p>Generated on: ${new Date().toLocaleDateString()}</p>
                                ${tableContent}
                                </body>
                        </html>
                    `);

            printWindow.document.close();
            printWindow.print();
        }

        // Column Visibility Functions
        function toggleColumns() {
            document.getElementById('columnModalOverlay').classList.add('active');
        }

        function closeColumnModal() {
            document.getElementById('columnModalOverlay').classList.remove('active');
        }

        function applyColumnVisibility() {
            $('.column-toggle').each(function() {
                const column = $(this).val();
                const isVisible = $(this).is(':checked');
                const columnIndex = parseInt(column) + 1;

                if (isVisible) {
                    $(`.reorder-table th:nth-child(${columnIndex}), .reorder-table td:nth-child(${columnIndex})`)
                        .show();
                } else {
                    $(`.reorder-table th:nth-child(${columnIndex}), .reorder-table td:nth-child(${columnIndex})`)
                        .hide();
                }
            });

            closeColumnModal();
        }

        // Close modal when clicking outside
        document.getElementById('reorderModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReorderModal();
            }
        });

        document.getElementById('columnModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeColumnModal();
            }
        });

        function loadLocations() {
            return $.ajax({
                url: '/get-locations',
                type: 'GET',
                success: function(locations) {
                    let options = '';
                    let firstKey = null;

                    Object.keys(locations).forEach((key, index) => {
                        if (index === 0) firstKey = key;
                        options += `<option value="${key}">${locations[key]}</option>`;
                    });

                    $('#modal_location_id').html(options);

                    initSelect2();

                    if (firstKey) {
                        $('#modal_location_id')
                            .val(firstKey)
                            .trigger('change');
                    }
                }
            });
        }
    </script>
@endsection
