@php
    $supplierCurrencySymbol = (string) (session('currency.symbol') ?: session('business.currency_symbol') ?: '');
@endphp
<div class="supplier-erp-toolbar" id="supplier_erp_toolbar">
    <form method="GET" action="{{ route('suppliers.records.index') }}" id="supplier_filter_form" class="supplier-filter-form">
        <div class="row supplier-toolbar-row">
            <div class="supplier-toolbar-item supplier-toolbar-search">
                <input type="search"
                       name="search"
                       id="supplier_global_search"
                       value="{{ $filters['search'] ?? '' }}"
                       class="form-control input-sm"
                       autocomplete="off"
                       aria-label="@lang('suppliers::lang.search_suppliers')"
                       placeholder="@lang('suppliers::lang.search_suppliers')">
            </div>

            <div class="supplier-toolbar-item supplier-toolbar-date">
                <input type="text"
                       name="date_range"
                       id="supplier_date_range"
                       value="{{ $filters['date_range'] ?? '' }}"
                       class="form-control input-sm"
                       autocomplete="off"
                       inputmode="numeric"
                       aria-label="Supplier created date range"
                       placeholder="YYYY-MM-DD ~ YYYY-MM-DD">
            </div>

            <div class="supplier-toolbar-item supplier-toolbar-per-page">
                <select name="per_page" id="supplier_per_page" class="form-control input-sm">
                    @foreach([10, 25, 50, 100, 250] as $size)
                        <option value="{{ $size }}" {{ (int)($filters['per_page'] ?? 25) === $size ? 'selected' : '' }}>
                            {{ $size }} @lang('suppliers::lang.records')
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="supplier-toolbar-item supplier-toolbar-actions">
                <div class="supplier-standard-export-group" role="group" aria-label="Supplier list export controls">
                    <button type="button"
                            class="btn supplier-standard-action-btn supplier-export-csv supplier-export-btn"
                            data-export-url="{{ route('suppliers.records.export', 'csv') }}">
                        <i class="fa fa-file-text-o" aria-hidden="true"></i>
                        <span>Export to CSV</span>
                    </button>

                    <button type="button"
                            class="btn supplier-standard-action-btn supplier-export-excel supplier-export-btn"
                            data-export-url="{{ route('suppliers.records.export', 'excel') }}">
                        <i class="fa fa-file-excel-o" aria-hidden="true"></i>
                        <span>Export to Excel</span>
                    </button>

                    <div class="btn-group supplier-column-visibility-group">
                        <button type="button"
                                class="btn supplier-standard-action-btn supplier-column-visibility-btn dropdown-toggle supplier-column-toggle"
                                data-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <i class="fa fa-columns" aria-hidden="true"></i>
                            <span>@lang('suppliers::lang.column_visibility')</span>
                            <span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right supplier-column-menu">
                            <li><label><input type="checkbox" class="supplier-colvis" data-column="1" checked> @lang('suppliers::lang.supplier_no')</label></li>
                            <li><label><input type="checkbox" class="supplier-colvis" data-column="2" checked> @lang('suppliers::lang.name')</label></li>
                            <li><label><input type="checkbox" class="supplier-colvis" data-column="3" checked> @lang('suppliers::lang.mobile')</label></li>
                            <li><label><input type="checkbox" class="supplier-colvis" data-column="4" checked> @lang('suppliers::lang.email')</label></li>
                            <li><label><input type="checkbox" class="supplier-colvis" data-column="5" checked> @lang('contact.total_due')</label></li>
                            <li><label><input type="checkbox" class="supplier-colvis" data-column="6" checked> @lang('suppliers::lang.created_at')</label></li>
                        </ul>
                    </div>

                    <button type="button"
                            class="btn supplier-standard-action-btn supplier-export-pdf supplier-export-btn"
                            data-export-url="{{ route('suppliers.records.export', 'pdf') }}">
                        <i class="fa fa-file-pdf-o" aria-hidden="true"></i>
                        <span>Export to PDF</span>
                    </button>

                    <button type="button" class="btn supplier-standard-action-btn supplier-print-standard supplier-print-btn">
                        <i class="fa fa-print" aria-hidden="true"></i>
                        <span>@lang('suppliers::lang.print')</span>
                    </button>
                </div>

                <a href="{{ route('suppliers.records.create') }}" class="btn btn-primary supplier-add-btn">
                    <i class="fa fa-plus"></i> @lang('suppliers::lang.add_supplier')
                </a>

                <div class="supplier-toolbar-total supplier-toolbar-summary" role="status" aria-label="Supplier financial summary across all pages">
                    <div class="supplier-toolbar-summary-head">
                        <span class="supplier-toolbar-total-icon" aria-hidden="true">
                            <i class="fa fa-truck"></i>
                        </span>
                        <span class="supplier-toolbar-total-copy">
                            <span class="supplier-toolbar-total-label">Supplier Total</span>
                            <strong class="supplier-toolbar-total-value">{{ number_format($suppliers->total()) }}</strong>
                        </span>
                    </div>

                    <div class="supplier-toolbar-money-summary">
                        <div class="supplier-toolbar-money-row supplier-toolbar-money-due">
                            <span class="supplier-toolbar-money-label">Total Due</span>
                            <strong class="supplier-toolbar-money-value"
                                    data-orig-value="{{ (float) ($supplierBalanceSummary['total_due'] ?? 0) }}">
                                {{ $supplierCurrencySymbol }}{{ $supplierCurrencySymbol !== '' ? ' ' : '' }}{{ number_format((float) ($supplierBalanceSummary['total_due'] ?? 0), 2) }}
                            </strong>
                        </div>
                        <div class="supplier-toolbar-money-row supplier-toolbar-money-overpaid">
                            <span class="supplier-toolbar-money-label">Total Overpaid</span>
                            <strong class="supplier-toolbar-money-value"
                                    data-orig-value="{{ (float) ($supplierBalanceSummary['total_overpaid'] ?? 0) }}">
                                {{ $supplierCurrencySymbol }}{{ $supplierCurrencySymbol !== '' ? ' ' : '' }}{{ number_format((float) ($supplierBalanceSummary['total_overpaid'] ?? 0), 2) }}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
