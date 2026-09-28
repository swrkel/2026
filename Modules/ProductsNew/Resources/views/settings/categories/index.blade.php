@extends('productsnew::layouts.app')

@section('productsnew_page_title', 'Categories')
@section('productsnew_page_subtitle', 'Maintain product categories, subcategories, linked accounts and VAT behaviour')

@push('css')
<style>
/* Categories page typography requirement: keep Calibri isolated to this page only. */
.productsnew-shell,
.productsnew-shell button,
.productsnew-shell input,
.productsnew-shell select,
.productsnew-shell textarea,
.productsnew-shell .select2-container,
.productsnew-shell .dropdown-menu {
    font-family: Calibri, "Segoe UI", Arial, sans-serif !important;
}

/* Categories table refinements - page scoped so no other Products New page is affected. */
.pn-category-table th {
    font-size: 9px !important; /* 2px smaller than the system 11px table heading baseline */
}
.pn-category-table th:nth-child(1), .pn-category-table td:nth-child(1) { width: 18.65% !important; }
.pn-category-table th:nth-child(2), .pn-category-table td:nth-child(2) { width: 17.2% !important; }
.pn-category-table th:nth-child(3), .pn-category-table td:nth-child(3) { width: 17.19% !important; text-align: left !important; }
.pn-category-table th:nth-child(4), .pn-category-table td:nth-child(4) { width: 8.1% !important; }
.pn-category-table th:nth-child(5), .pn-category-table td:nth-child(5) { width: 5.95% !important; }
/* Apply VAT On: 22.3% -> 15.61% (30% narrower). */
.pn-category-table th:nth-child(6), .pn-category-table td:nth-child(6) { width: 15.61% !important; }
.pn-category-table th:nth-child(7), .pn-category-table td:nth-child(7) { width: 6.3% !important; text-align: center !important; }
.pn-category-table th:nth-child(8), .pn-category-table td:nth-child(8) { width: 11% !important; text-align: center !important; }
.pn-category-table .pn-action-column { width: 11% !important; }
.pn-category-table-wrap { width: 100% !important; max-width: 100% !important; }
.pn-category-table { width: 100% !important; max-width: 100% !important; min-width: 0 !important; table-layout: fixed !important; }
.pn-category-table th, .pn-category-table td { padding-left: 5px !important; padding-right: 5px !important; }
.pn-category-list-card { width: 100%; max-width: 100%; box-sizing: border-box; }
/* Keep category Action menus above the table/card instead of clipping the lower rows.
   The desktop category table is designed to fit the page, so horizontal scrolling is
   not required there. Smaller screens retain the responsive wrapper. */
@media (min-width: 992px) {
    .pn-category-list-card { overflow: visible !important; }
    .pn-category-table-wrap { overflow: visible !important; }
}
.pn-category-table .pn-action-dropdown { position: relative; }
.pn-category-table .pn-action-dropdown.open { z-index: 1200; }
.pn-category-table .pn-action-dropdown .dropdown-menu { z-index: 1210; }
.pn-subcategory-cell .pn-subcategory-badge { display: inline-flex; }
.pn-subcategory-code-inline {
    display: block;
    margin-top: 3px;
    color: #71849a;
    font-size: 11px;
    font-weight: 600;
    line-height: 1.15;
}
.pn-category-functionality-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
    margin: 0 0 12px;
}
.pn-category-functionality-bar .dt-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.pn-category-functionality-bar .dt-button {
    margin: 0 !important;
    color: #fff !important;
    border: 0 !important;
    border-radius: 4px !important;
    box-shadow: none !important;
}
@media (max-width: 767px) {
    .pn-category-functionality-bar .dt-buttons { width: 100%; }
    .pn-category-functionality-bar .dt-button { flex: 1 1 auto; }
}

.pn-category-import-modal .modal-footer {
    clear: both;
    position: relative;
    z-index: 5;
}
.pn-category-import-guide {
    margin: 0 0 16px;
    padding: 14px 16px;
    border: 1px solid #dce7f3;
    border-radius: 10px;
    background: #f8fbff;
}
.pn-category-import-guide h4 {
    margin: 0 0 6px;
    color: #1e3550;
    font-size: 14px;
    font-weight: 800;
}
.pn-category-import-guide p {
    margin: 0;
    color: #60758d;
    font-size: 12px;
    line-height: 1.5;
}
.pn-category-import-headings {
    max-height: 310px;
    overflow: auto;
    margin-top: 12px;
    border: 1px solid #e2e9f1;
    border-radius: 8px;
    background: #fff;
}
.pn-category-import-headings table {
    width: 100%;
    margin: 0;
    font-size: 12px;
}
.pn-category-import-headings th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f4f7fb;
    color: #31445b;
    font-size: 11px;
    font-weight: 800;
}
.pn-category-import-headings td,
.pn-category-import-headings th {
    padding: 8px 10px !important;
    vertical-align: top !important;
}
.pn-category-import-required {
    color: #c0392b;
    font-weight: 800;
}
.pn-category-import-note {
    margin-top: 12px;
    padding: 10px 12px;
    border-left: 3px solid #3b82f6;
    background: #f7faff;
    color: #53677d;
    font-size: 12px;
    line-height: 1.5;
}
</style>

@endpush

@section('productsnew_content')
@php
    $activeFilters = $filters ?? request()->only(['search', 'type', 'vat_exempted']);
    $oldCategoryPayload = $errors->any() ? [
        'name' => old('name'),
        'short_code' => old('short_code'),
        'category_code_is_hsn' => old('category_code_is_hsn', 0),
        'add_as_sub_category' => old('add_as_sub_category', 0),
        'parent_id' => old('parent_id'),
        'add_related_account' => old('add_related_account'),
        'cogs_account_id' => old('cogs_account_id'),
        'sales_income_account_id' => old('sales_income_account_id'),
        'weight_excess_loss_applicable' => old('weight_excess_loss_applicable', 0),
        'vat_exempted' => old('vat_exempted', 'No'),
        'vat_based_on' => old('vat_based_on', 'sale_price'),
        'apply_vat_on' => old('apply_vat_on', 'on_product_sub_category_settings'),
    ] : null;
@endphp

@if(session('status'))
    <div class="alert alert-success pn-alert">
        <i class="fa fa-check-circle"></i> {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger pn-alert">
        <strong><i class="fa fa-exclamation-circle"></i> The category could not be saved.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="pn-card pn-category-toolbar-card">
    <form method="get" action="{{ route('products-new.settings.categories.index') }}" class="pn-category-toolbar">
        <div class="form-group pn-category-search">
            <label for="pn_category_search">Search</label>
            <div class="pn-input-icon"><i class="fa fa-search"></i><input id="pn_category_search" class="form-control" name="search" value="{{ $activeFilters['search'] ?? '' }}" placeholder="Category, subcategory, category code or sub category code"></div>
        </div>
        <div class="form-group">
            <label for="pn_category_type">Filter</label>
            <select id="pn_category_type" class="form-control" name="type" data-no-search>
                <option value="">All Levels</option>
                <option value="category" @selected(($activeFilters['type'] ?? '') === 'category')>Categories only</option>
                <option value="subcategory" @selected(($activeFilters['type'] ?? '') === 'subcategory')>Subcategories only</option>
            </select>
        </div>
        <div class="form-group">
            <label for="pn_category_vat">VAT Exempt</label>
            <select id="pn_category_vat" class="form-control" name="vat_exempted" data-no-search>
                <option value="">All</option>
                <option value="Yes" @selected(($activeFilters['vat_exempted'] ?? '') === 'Yes')>Yes</option>
                <option value="No" @selected(($activeFilters['vat_exempted'] ?? '') === 'No')>No</option>
            </select>
        </div>
        <div class="pn-category-toolbar-actions">
            <button type="button"
                    class="pn-btn pn-btn-info"
                    data-toggle="modal"
                    data-target="#pnCategoryImportModal">
                <i class="fa fa-upload"></i> Import Product Categories
            </button>
            <button type="button"
                    class="pn-btn pn-btn-primary"
                    data-pn-open-category
                    data-title="Add Category"
                    data-method="POST"
                    data-action="{{ route('products-new.settings.categories.store') }}"
                    data-category="{}">
                <i class="fa fa-plus"></i> Add Category
            </button>
        </div>
    </form>
</div>

<div class="pn-card pn-category-list-card">
    <div class="pn-toolbar">
        <div><strong>Category Master</strong><span>{{ $categories->total() }} records</span></div>
        <div class="pn-category-legend"><span><i class="fa fa-folder"></i> Category</span><span><i class="fa fa-level-down"></i> Subcategory</span></div>
    </div>

    <div id="pn_category_functionality_bar" class="pn-category-functionality-bar no-print" aria-label="Category table export tools"></div>

    <div class="table-responsive pn-category-table-wrap">
        <table id="pn_category_table" class="table pn-table pn-category-table">
            <thead>
                <tr>
                    <th>Category</th>
                    <th><span class="pn-th-multiline">Sub Category<br>Code</span></th>
                    <th>COGS</th>
                    <th><span class="pn-th-multiline">Sales Income<br>Account</span></th>
                    <th><span class="pn-th-multiline">VAT<br>Based On</span></th>
                    <th><span class="pn-th-multiline">Apply VAT<br>On</span></th>
                    <th><span class="pn-th-multiline">VAT<br>Exempted</span></th>
                    <th class="pn-action-column">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    @php
                        $isSubCategory = (int) ($category->parent_id ?? 0) > 0;
                        $editPayload = [
                            'name' => $category->name,
                            'short_code' => $category->short_code,
                            'category_code_is_hsn' => (int) ($category->category_code_is_hsn ?? 0),
                            'add_as_sub_category' => $isSubCategory ? 1 : 0,
                            'parent_id' => $isSubCategory ? (int) $category->parent_id : '',
                            'add_related_account' => $category->add_related_account,
                            'cogs_account_id' => $category->cogs_account_id,
                            'sales_income_account_id' => $category->sales_income_account_id,
                            'weight_excess_loss_applicable' => (int) ($category->weight_excess_loss_applicable ?? 0),
                            'vat_exempted' => $category->vat_exempted ?? 'No',
                            'vat_based_on' => $category->vat_based_on ?? 'sale_price',
                            'apply_vat_on' => $category->apply_vat_on ?? 'on_product_sub_category_settings',
                        ];
                    @endphp
                    <tr>
                        @php
                            $displayCategoryCode = $isSubCategory
                                ? optional($parentCategories->firstWhere('id', $category->parent_id))->short_code
                                : $category->short_code;
                        @endphp
                        <td class="pn-category-name-cell">
                            <strong>{{ $isSubCategory ? ($category->parent_name ?: 'Unassigned') : $category->name }}</strong>
                            <span class="pn-category-code-inline">{{ $displayCategoryCode ?: '—' }}</span>
                        </td>
                        <td class="pn-subcategory-cell">
                            @if($isSubCategory)
                                <span class="pn-subcategory-badge"><i class="fa fa-level-down"></i>{{ $category->name }}</span>
                                <span class="pn-subcategory-code-inline">{{ $category->short_code ?: '—' }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $category->cogs_name ?: '—' }}</td>
                        <td>{{ $category->sales_income_name ?: '—' }}</td>
                        <td>{{ ($category->vat_based_on ?? 'sale_price') === 'purchase_price' ? 'Purchase Price' : 'Sale Price' }}</td>
                        <td>{{ ($category->apply_vat_on ?? '') === 'on_product_tax_settings_section' ? 'Product Tax Settings' : 'Category / Subcategory Settings' }}</td>
                        <td><span class="pn-status-pill {{ ($category->vat_exempted ?? 'No') === 'Yes' ? 'is-warning' : 'is-success' }}">{{ $category->vat_exempted ?? 'No' }}</span></td>
                        <td class="pn-action-column">
                            <div class="dropdown pn-action-dropdown">
                                <button class="pn-action-parent dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-cog"></i> Action <span class="caret"></span></button>
                                <ul class="dropdown-menu dropdown-menu-right">
                                    <li>
                                        <button type="button"
                                                class="pn-action-child pn-action-edit"
                                                data-pn-open-category
                                                data-title="Edit Category"
                                                data-method="PUT"
                                                data-action="{{ route('products-new.settings.categories.update', $category->id) }}"
                                                data-category="{{ e(json_encode($editPayload)) }}">
                                            <i class="fa fa-pencil"></i> Edit
                                        </button>
                                    </li>
                                    <li>
                                        <form method="post" action="{{ route('products-new.settings.categories.destroy', $category->id) }}">
                                            @csrf @method('delete')
                                            <button type="submit" class="pn-action-child pn-action-delete" data-pn-confirm="Delete this category? This is allowed only when it has no products or subcategories."><i class="fa fa-trash"></i> Delete</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="pn-empty-state"><i class="fa fa-folder-open-o"></i><strong>No categories found</strong><span>Change the filters or add a new category.</span></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pn-pagination">{{ $categories->links() }}</div>
</div>

{{--
    IS1971 #1: the Save Category and Cancel buttons were unclickable.

    Measured with elementFromPoint at the centre of the Save button:
        whatIsActuallyAtThatPoint: "DIV.row pn-form-grid"
        isTheSaveButton: false
    so the clicks never reached either button - which is why nothing happened
    and the console stayed silent (no submit, no validation warning, no error).

    Cause: this module's CSS redefines the grid with floats
        .col-md-6 { width:50%; float:left }
    and a FLOATED element is painted ABOVE the background of an in-flow block
    box that follows it. .modal-footer is in-flow and unpositioned, so the
    floated .pn-form-grid painted over it and swallowed the pointer events.
    It also explains the screenshot, where the Cancel button appeared clipped
    by the Save button - the footer was being overlapped, not truncated.

    Two independent guards, because either alone would fix it and both are
    cheap:
      - clear: both      moves the footer below the floats in normal flow
      - position/z-index gives it its own stacking level, so it can never be
                         painted under a float again

    Bootstrap itself was fine - bootstrapKnowsModal returned true - so once
    clicks land, data-dismiss="modal" closes the modal with no other change.
    Scoped to this modal only.
--}}
<style>
    .pn-category-modal .modal-footer {
        clear: both;
        position: relative;
        z-index: 5;
    }

    .pn-category-modal .pn-form-grid {
        position: relative;
        z-index: 1;
    }

    /* The section wrappers hold the floated rows - contain them so the footer
       is never overlapped by a section either. */
    .pn-category-modal .pn-form-section::after {
        content: "";
        display: table;
        clear: both;
    }
</style>

<div class="modal fade pn-category-modal" tabindex="-1" role="dialog" aria-hidden="true" data-pn-category-modal data-pn-category-old="{{ $oldCategoryPayload ? e(json_encode($oldCategoryPayload)) : '' }}">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="{{ route('products-new.settings.categories.store') }}" data-store-action="{{ route('products-new.settings.categories.store') }}" data-pn-category-form autocomplete="off">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <div class="pn-category-modal-heading"><i class="fa fa-folder-open"></i><div><h3 data-pn-category-modal-title>Add Category</h3><p>Create a category or link it as a subcategory.</p></div></div>
                </div>
                <div class="modal-body">
                    <div class="row pn-form-grid">
                        <div class="col-md-6 form-group">
                            <label>Category Name <span class="text-danger">*</span></label>
                            <input class="form-control" name="name" required maxlength="191">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Category Code</label>
                            <input class="form-control" name="short_code" maxlength="50" placeholder="Category / HSN code">
                        </div>
                        <div class="col-md-6 form-group pn-checkbox-field">
                            <label><input type="checkbox" name="category_code_is_hsn" value="1"><span><i class="fa fa-check"></i></span> Category code is same as HSN code</label>
                        </div>
                        <div class="col-md-6 form-group pn-checkbox-field">
                            <label><input type="checkbox" name="add_as_sub_category" value="1"><span><i class="fa fa-check"></i></span> Add as sub-category</label>
                        </div>
                        <div class="col-md-6 form-group" data-pn-category-parent-wrap hidden>
                            <label>Parent Category <span class="text-danger">*</span></label>
                            <select class="form-control" name="parent_id" data-placeholder="Select parent category">
                                <option value="">Please select</option>
                                @foreach($parentCategories as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->name }}{{ !empty($parent->short_code) ? ' — '.$parent->short_code : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Add Related Account At</label>
                            <select class="form-control" name="add_related_account" data-no-search>
                                <option value="">Please select</option>
                                <option value="category_level">Category Level</option>
                                <option value="sub_category_level">Subcategory Level</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group pn-checkbox-field">
                            <label><input type="checkbox" name="weight_excess_loss_applicable" value="1"><span><i class="fa fa-check"></i></span> Weight Loss / Excess Applicable</label>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>COGS Accounts</label>
                            <select class="form-control" name="cogs_account_id" data-placeholder="Select COGS account">
                                <option value="">Please select</option>
                                @foreach($cogsAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Sales Income Accounts</label>
                            <select class="form-control" name="sales_income_account_id" data-placeholder="Select sales income account">
                                <option value="">Please select</option>
                                @foreach($salesIncomeAccounts as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>VAT Exempted Products <span class="text-danger">*</span></label>
                            <select class="form-control" name="vat_exempted" required data-no-search>
                                <option value="No">No</option>
                                <option value="Yes">Yes</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>VAT Based On <span class="text-danger">*</span></label>
                            <select class="form-control" name="vat_based_on" required data-no-search>
                                <option value="sale_price">Sale Price</option>
                                <option value="purchase_price">Purchase Price</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Apply VAT On <span class="text-danger">*</span></label>
                            <select class="form-control" name="apply_vat_on" required data-no-search>
                                <option value="on_product_sub_category_settings">Category / Subcategory Settings</option>
                                <option value="on_product_tax_settings_section">Product Tax Settings Section</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="pn-btn pn-btn-light" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                    <button type="submit" class="pn-btn pn-btn-primary"><i class="fa fa-save"></i> Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>



<div class="modal fade pn-category-import-modal" id="pnCategoryImportModal" tabindex="-1" role="dialog" aria-labelledby="pnCategoryImportModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="post" action="{{ route('products-new.settings.categories.import') }}" enctype="multipart/form-data" autocomplete="off">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <div class="pn-category-modal-heading">
                        <i class="fa fa-upload"></i>
                        <div>
                            <h3 id="pnCategoryImportModalTitle">Import Product Categories</h3>
                            <p>Import categories and subcategories from an Excel-compatible file.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-body">
                    @if($errors->getBag('categoryImport')->any())
                        <div class="alert alert-danger pn-alert">
                            <strong><i class="fa fa-exclamation-circle"></i> The category import could not be completed.</strong>
                            <ul>
                                @foreach($errors->getBag('categoryImport')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="pn-category-import-guide">
                        <h4><i class="fa fa-info-circle"></i> File and column guide</h4>
                        <p>Use the headings below in the first row. CSV and XLSX files are accepted. The two headings marked Required must be present; the other columns may be left blank when not applicable.</p>

                        <div class="pn-category-import-headings table-responsive">
                            <table class="table table-condensed">
                                <thead>
                                    <tr>
                                        <th>Excel Column Heading</th>
                                        <th>Required</th>
                                        <th>Accepted Value / Instruction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>category_name</code></td><td class="pn-category-import-required">Yes</td><td>Category or subcategory name.</td></tr>
                                    <tr><td><code>category_code</code></td><td>No</td><td>Category / HSN / short code.</td></tr>
                                    <tr><td><code>category_code_is_hsn</code></td><td>No</td><td>Yes or No. Default: No.</td></tr>
                                    <tr><td><code>type</code></td><td class="pn-category-import-required">Yes</td><td>Category or Subcategory.</td></tr>
                                    <tr><td><code>parent_category</code></td><td>For subcategory</td><td>Parent category name or category code. Parent rows in the same file are imported first automatically.</td></tr>
                                    <tr><td><code>add_related_account_at</code></td><td>No</td><td>Category Level, Subcategory Level, or blank.</td></tr>
                                    <tr><td><code>cogs_account</code></td><td>No</td><td>Existing COGS account name or account ID.</td></tr>
                                    <tr><td><code>sales_income_account</code></td><td>No</td><td>Existing Sales Income account name or account ID.</td></tr>
                                    <tr><td><code>weight_loss_excess_applicable</code></td><td>No</td><td>Yes or No. Default: No.</td></tr>
                                    <tr><td><code>vat_exempted</code></td><td>No</td><td>Yes or No. Default: No.</td></tr>
                                    <tr><td><code>vat_based_on</code></td><td>No</td><td>Sale Price or Purchase Price. Default: Sale Price.</td></tr>
                                    <tr><td><code>apply_vat_on</code></td><td>No</td><td>Category / Subcategory Settings or Product Tax Settings Section.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pn-category-import-note">
                            <strong>Import rules:</strong> existing category/subcategory names under the same parent are skipped to prevent duplicates. If an account name is supplied, it must already exist for the active business. Old <strong>.xls</strong> files should be saved as <strong>.xlsx</strong> before upload.
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="pn_category_import_file">Select category file <span class="text-danger">*</span></label>
                        <input type="file"
                               class="form-control"
                               id="pn_category_import_file"
                               name="category_import_file"
                               accept=".csv,.txt,.xlsx"
                               required>
                        <small class="help-block">Maximum file size: 10 MB.</small>
                    </div>

                    <a href="{{ route('products-new.settings.categories.import-template') }}" class="pn-btn pn-btn-light">
                        <i class="fa fa-download"></i> Download Sample Category Template
                    </a>
                </div>
                <div class="modal-footer">
                    <button type="button" class="pn-btn pn-btn-light" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
                    <button type="submit" class="pn-btn pn-btn-primary"><i class="fa fa-upload"></i> Import Categories</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('javascript')
<script>
(function (window, document, $) {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        var form = document.querySelector('.pn-category-toolbar');
        if (form) {
            var timer = null;
            var search = form.querySelector('#pn_category_search');
            var selects = form.querySelectorAll('#pn_category_type, #pn_category_vat');

            function submitFilters() {
                if (timer) {
                    window.clearTimeout(timer);
                    timer = null;
                }
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }

            selects.forEach(function (select) {
                select.addEventListener('change', submitFilters);
            });

            if (search) {
                search.addEventListener('input', function () {
                    if (timer) window.clearTimeout(timer);
                    timer = window.setTimeout(submitFilters, 350);
                });

                search.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        submitFilters();
                    }
                });
            }
        }

        @if(session('open_category_import') || $errors->getBag('categoryImport')->any())
        if ($ && $.fn && $.fn.modal) {
            $('#pnCategoryImportModal').modal('show');
        }
        @endif
    });
})(window, document, window.jQuery);
</script>

@if($categories->count() > 0)
<script>
(function (window, document, $) {
    'use strict';

    if (!$ || !$.fn || !$.fn.DataTable || !$.fn.dataTable || !$.fn.dataTable.Buttons) {
        return;
    }

    $(function () {
        var $table = $('#pn_category_table');
        var $toolbar = $('#pn_category_functionality_bar');
        if (!$table.length || !$toolbar.length || $.fn.dataTable.isDataTable($table[0])) {
            return;
        }

        var table = $table.DataTable({
            paging: false,
            searching: false,
            info: false,
            ordering: false,
            autoWidth: false,
            dom: 'Brt',
            buttons: [
                {
                    extend: 'excelHtml5',
                    text: '<i class="fa fa-file-excel-o"></i> Export to Excel',
                    className: 'btn btn-info',
                    title: 'Product Categories',
                    exportOptions: { columns: ':not(.pn-action-column)' }
                },
                {
                    extend: 'csvHtml5',
                    text: '<i class="fa fa-file-text-o"></i> Export to CSV',
                    className: 'btn btn-success',
                    title: 'Product Categories',
                    exportOptions: { columns: ':not(.pn-action-column)' }
                },
                {
                    extend: 'colvis',
                    text: '<i class="fa fa-columns"></i> Column Visibility',
                    className: 'btn btn-primary',
                    columns: ':not(.pn-action-column)'
                },
                {
                    extend: 'pdfHtml5',
                    text: '<i class="fa fa-file-pdf-o"></i> PDF',
                    className: 'btn btn-danger',
                    title: 'Product Categories',
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':not(.pn-action-column)' }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    className: 'btn btn-warning',
                    title: 'Product Categories',
                    exportOptions: { columns: ':not(.pn-action-column)' }
                }
            ]
        });

        table.buttons().container().appendTo($toolbar);
    });
})(window, document, window.jQuery);
</script>
@endif
@endpush

@push('javascript')
<script>
(function ($) {
    'use strict';
    if (!$ || !$.fn) return;
    $(document).on('show.bs.dropdown', '#pn_category_table .pn-action-dropdown', function () {
        var menu = this.querySelector('.dropdown-menu');
        var wrap = this.closest('.pn-category-table-wrap');
        if (!menu || !wrap) return;
        var triggerRect = this.getBoundingClientRect();
        var wrapRect = wrap.getBoundingClientRect();
        var visibleBottom = Math.min(window.innerHeight || document.documentElement.clientHeight, wrapRect.bottom);
        var menuHeight = Math.max(menu.scrollHeight || 0, 118);
        var spaceBelow = visibleBottom - triggerRect.bottom;
        var spaceAbove = triggerRect.top - Math.max(0, wrapRect.top);
        var shouldDropUp = spaceBelow < (menuHeight + 12) && spaceAbove > spaceBelow;
        this.classList.toggle('dropup', shouldDropUp);
    });
})(window.jQuery);
</script>
@endpush

@endsection
