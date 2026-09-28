@php
    $existingLines = old('lines_json');
    if ($existingLines === null && $change) {
        $existingLines = $change->lines->map(function($line){
            return [
                'variation_id' => (int) $line->variation_id,
                'product_id' => (int) $line->product_id,
                'product_name' => $line->product_name,
                'variation_name' => $line->variation_name,
                'sku' => $line->sku,
                'tax_name' => $line->tax_name,
                'tax_rate' => (float) $line->tax_rate,
                'tax_type' => $line->tax_type,
                'stock_quantity' => (float) $line->stock_quantity,
                'current_purchase_price_ex_tax' => (float) $line->current_purchase_price_ex_tax,
                'current_purchase_price_inc_tax' => (float) $line->current_purchase_price_inc_tax,
                'current_sell_price_ex_tax' => (float) $line->current_sell_price_ex_tax,
                'current_sell_price_inc_tax' => (float) $line->current_sell_price_inc_tax,
                'current_profit_percent' => (float) $line->current_profit_percent,
                'purchase_price_basis' => $line->purchase_price_basis ?: 'inc_tax',
                'new_purchase_price' => $line->purchase_price_basis === 'ex_tax'
                    ? $line->new_purchase_price_ex_tax : $line->new_purchase_price_inc_tax,
                'sell_price_basis' => $line->sell_price_basis ?: 'inc_tax',
                'new_sell_price' => $line->sell_price_basis === 'ex_tax'
                    ? $line->new_sell_price_ex_tax : $line->new_sell_price_inc_tax,
            ];
        })->values()->toJson();
    }
    $existingLines = $existingLines ?: '[]';
    $scopeValue = old('application_scope', optional($change)->application_scope ?: ($defaultApplicationScope ?? 'business_base'));
@endphp

<div class="ch-card pcn-form-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-file-text-o text-primary"></i> Price Change Details</h3>
            <div class="ch-card-subtitle">Define the business scope, locations and effective date before adding products.</div>
        </div>
    </div>
    <div class="ch-card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" maxlength="191" required
                           value="{{ old('title', optional($change)->title) }}"
                           placeholder="Example: August retail price revision">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Effective Date and Time</label>
                    <input type="datetime-local" name="effective_at" class="form-control"
                           @php
                               /*
                                * MA-008: a NEW price change opens on the current date
                                * and time. A saved one keeps its own effective date, and
                                * old() still wins after a failed save so a figure the
                                * operator typed is never replaced.
                                */
                               $pcnEffectiveAt = old('effective_at');

                               if ($pcnEffectiveAt === null) {
                                   $pcnEffectiveAt = $change && $change->effective_at
                                       ? $change->effective_at->format('Y-m-d\TH:i')
                                       : (empty(optional($change)->id) ? now()->format('Y-m-d\TH:i') : '');
                               }
                           @endphp
                           value="{{ $pcnEffectiveAt }}">
                    <p class="help-block">Leave empty to make the approved change immediately eligible for application.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Application Scope <span class="text-danger">*</span></label>
                    <select name="application_scope" id="pcn_application_scope" class="form-control select2" required>
                        @foreach(config('pricechangenew.application_scopes', []) as $key => $label)
                            <option value="{{ $key }}" {{ $scopeValue === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="help-block">Location price groups change selling prices only. Business base can change purchase and selling prices.</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Business Locations <span class="text-danger">*</span></label>
                    <select name="location_ids[]" id="pcn_location_ids" class="form-control select2" multiple required>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ in_array((int) $location->id, array_map('intval', (array) $selectedLocationIds), true) ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <p class="help-block">The first assigned location is selected automatically. Only locations assigned to the logged-in user are available.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Stock Price Rule</label>
                    <input type="hidden" name="stock_price_mode" value="all_stock">
                    <input type="text" class="form-control" value="Apply to all stock" readonly>
                    <p class="help-block">Old-stock/new-stock layering is reserved for the next verified sequence.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Reason / Notes</label>
                    <textarea name="reason" class="form-control" rows="3" maxlength="5000" placeholder="Why is this price change required?">{{ old('reason', optional($change)->reason) }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="ch-card pcn-form-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-cubes text-primary"></i> Product Price Lines</h3>
            <div class="ch-card-subtitle">Search by product name, SKU or variation and enter the proposed prices.</div>
        </div>
        <span class="pcn-line-count"><strong id="pcn_line_count">0</strong> line(s)</span>
    </div>
    <div class="ch-card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="form-group pcn-product-search-wrap">
                    <label>Search Product / SKU / Variation</label>
                    <div class="pcn-search-input">
                        <input type="text" id="pcn_product_search" class="form-control" autocomplete="off"
                               placeholder="Type at least two characters">
                        <i class="fa fa-search"></i>
                    </div>
                    <div id="pcn_product_search_results" class="pcn-search-results hidden"></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="pcn-note-box">
                    <i class="fa fa-shield"></i>
                    <span>Search results and price snapshots are restricted to the logged-in tenant and business.</span>
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered pos-standard-table pcn-lines-table" id="pcn_lines_table">
                <thead>
                    <tr>
                        <th class="pcn-col-product">Product / Variation</th>
                        <th>SKU</th>
                        <th class="text-right pcn-num">Stock</th>
                        <th class="text-right pcn-num">Tax</th>
                        <th class="text-right pcn-num pcn-col-current">Current Purchase<br>Ex / Inc</th>
                        <th class="text-right pcn-num pcn-col-current">Current Selling<br>Ex / Inc</th>
                        <th style="min-width:185px">New Purchase Price</th>
                        <th style="min-width:185px">New Selling Price <span class="text-danger">*</span></th>
                        <th class="text-right pcn-num pcn-col-profit">New Profit %</th>
                        <th class="text-center">Remove</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div id="pcn_lines_empty" class="pcn-empty-state">
            <span class="pcn-empty-icon"><i class="fa fa-search"></i></span>
            <strong>No products added yet</strong>
            <span>Use the search field above to add the first product variation.</span>
        </div>

        <input type="hidden" name="lines_json" id="pcn_lines_json" value="{{ e($existingLines) }}">
    </div>
    <div class="pcn-form-footer no-print">
        <a href="{{ route('pricechangenew.changes.index') }}" class="btn btn-default"><i class="fa fa-times"></i> Cancel</a>
        <button type="submit" class="btn btn-success pos-large-save" id="pcn_save_draft"><i class="fa fa-save"></i> Save Draft</button>
    </div>
</div>

<div id="pcn_form_config"
     data-search-url="{{ route('pricechangenew.products.search') }}"
     data-details-template="{{ url('/pricechangenew/products') }}/__ID__">
</div>
<script type="application/json" id="pcn_existing_lines">@json(json_decode($existingLines, true) ?: [])</script>
