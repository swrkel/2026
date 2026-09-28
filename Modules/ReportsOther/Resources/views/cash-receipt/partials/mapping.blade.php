<div class="reo-section-head">
    <div>
        <h2>Map Sub Products to Source</h2>
        <p>Create a source and map multiple Product Categories and Product Sub Categories.</p>
    </div>
</div>

<form method="post" action="{{ route('reports-other.cash-receipt.source-mappings.store') }}" class="reo-form" id="reo-source-map-form">
    @csrf
    <div class="reo-map-grid">
        <div class="reo-panel">
            <label class="reo-label" for="source_name">Source Name <span class="required">*</span></label>
            <input class="reo-input" type="text" name="source_name" id="source_name" value="{{ old('source_name') }}" maxlength="120" required autocomplete="off" placeholder="Enter source name">
            <div class="reo-help">A source name is stored only inside Reports - Other.</div>
        </div>

        <div class="reo-panel">
            <label class="reo-label" for="reo-catalog-toggle">Product Categories &amp; Sub Categories <span class="required">*</span></label>
            <div class="reo-help reo-help-before-control">Open the dropdown and select one or many categories / sub categories.</div>

            <div class="reo-multiselect" data-reo-catalog-multiselect>
                <button type="button" class="reo-multiselect-toggle" id="reo-catalog-toggle" data-reo-catalog-toggle aria-expanded="false">
                    <span data-reo-catalog-summary>Select Product Categories &amp; Sub Categories</span>
                    <span class="reo-multiselect-arrow" aria-hidden="true">▾</span>
                </button>

                <div class="reo-multiselect-menu" data-reo-catalog-menu hidden>
                    <div class="reo-multiselect-tools">
                        <input class="reo-input reo-search reo-multiselect-search" type="search" id="reo-catalog-search" data-reo-catalog-search placeholder="Type to filter categories / sub categories..." autocomplete="off">
                        <div class="reo-multiselect-actions">
                            <button class="reo-btn reo-btn-light reo-btn-sm" type="button" data-reo-catalog-select-all>Select All</button>
                            <button class="reo-btn reo-btn-light reo-btn-sm" type="button" data-reo-catalog-clear>Clear All</button>
                        </div>
                    </div>

                    <div class="reo-multiselect-options" id="reo-catalog-tree">
                        @forelse($catalogTree as $category)
                            @php
                                $categorySearch = strtolower($category['name'].' '.collect($category['children'])->pluck('name')->implode(' '));
                            @endphp
                            <div class="reo-multiselect-group" data-reo-catalog-group data-search="{{ $categorySearch }}">
                                <label class="reo-multiselect-option reo-multiselect-category" data-reo-catalog-option-row data-search="{{ strtolower($category['name']) }}">
                                    <input type="checkbox"
                                           name="category_ids[]"
                                           value="{{ $category['id'] }}"
                                           data-reo-catalog-option
                                           data-label="{{ $category['name'] }}"
                                           @checked(in_array($category['id'], old('category_ids', [])))>
                                    <span class="reo-multiselect-option-text">
                                        <strong>{{ $category['name'] }}</strong>
                                        <small>Product Category</small>
                                    </span>
                                </label>

                                @foreach($category['children'] as $child)
                                    <label class="reo-multiselect-option reo-multiselect-subcategory"
                                           data-reo-catalog-option-row
                                           data-search="{{ strtolower($child->name.' '.$category['name']) }}"
                                           style="--reo-depth: {{ max(1, (int) ($child->depth ?? 1)) }};">
                                        <input type="checkbox"
                                               name="sub_category_ids[]"
                                               value="{{ $child->id }}"
                                               data-reo-catalog-option
                                               data-label="{{ $child->name }}"
                                               @checked(in_array($child->id, old('sub_category_ids', [])))>
                                        <span class="reo-multiselect-option-text">
                                            <span>{{ $child->name }}</span>
                                            <small>Product Sub Category</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @empty
                            <div class="reo-empty-inline reo-catalog-empty">
                                No Product Categories / Sub Categories were found for this business. The module checks Products New first and the legacy product categories table as a fallback.
                            </div>
                        @endforelse
                    </div>

                    <div class="reo-multiselect-footer">
                        <strong data-reo-catalog-count>0 selected</strong>
                        <button class="reo-btn reo-btn-primary reo-btn-sm" type="button" data-reo-catalog-done>Done</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="reo-actions">
        <button class="reo-btn reo-btn-primary" type="submit">Save Mapping</button>
        <button class="reo-btn reo-btn-light" type="reset">Clear</button>
    </div>
</form>

<div class="reo-divider"></div>
<div class="reo-section-head">
    <div><h3>Saved Sources</h3><p>All source mappings for the current business/location/store scope.</p></div>
    <input class="reo-input reo-search" type="search" id="reo-source-search" placeholder="Search saved sources...">
</div>

<div class="reo-table-wrap">
<table class="reo-table" id="reo-source-table">
    <thead>
        <tr><th>Date</th><th>Source</th><th>Mapped Product Categories and Product Sub Categories</th><th>User Created</th><th class="reo-text-right">Action</th></tr>
    </thead>
    <tbody>
    @forelse($sources as $source)
        <tr data-source-row data-search="{{ strtolower($source->source_name.' '.$source->created_by_name.' '.$source->mappings->pluck('item_name_snapshot')->implode(' ')) }}">
            <td>{{ optional($source->created_at)->format('d M Y, h:i A') }}</td>
            <td><strong>{{ $source->source_name }}</strong></td>
            <td>
                <div class="reo-mapped-list">
                    @foreach($source->mappings as $mapping)
                        <div><span>{{ $mapping->item_name_snapshot }}</span><small>{{ $mapping->item_type === 'category' ? 'Category' : 'Sub Category' }}</small></div>
                    @endforeach
                </div>
            </td>
            <td>{{ $source->created_by_name }}</td>
            <td class="reo-text-right">
                <form method="post" action="{{ route('reports-other.cash-receipt.source-mappings.destroy', $source) }}" data-confirm="Delete this source mapping?">
                    @csrf @method('DELETE')
                    <button class="reo-btn reo-btn-danger reo-btn-sm" type="submit">Delete</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="reo-empty-inline">No source mappings have been added yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
