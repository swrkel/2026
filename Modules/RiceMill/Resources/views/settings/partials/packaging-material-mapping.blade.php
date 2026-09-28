<section id="material-usage-mapping-settings" class="rcm-card rcm-settings-section rcm-material-usage-design-card">
    <div class="rcm-settings-section-head rcm-material-usage-design-head">
        <div class="rcm-material-usage-heading-copy">
            <div class="rcm-section-title">Material Usage Mapping</div>
        </div>
        <a class="rcm-btn secondary rcm-material-usage-packaging-btn" href="{{ route('rice-mill.packaging-materials.index') }}">
            <i class="fa fa-cubes"></i> Packaging Materials
        </a>
    </div>

    <p class="rcm-muted rcm-material-usage-description">Map Rice Product + Bag Size to each packaging material used per bag. Packing automatically reduces the mapped material quantities.</p>

    <form method="post" action="{{ route('rice-mill.packaging-material-mappings.store') }}" class="rcm-material-usage-bulk-form rcm-material-usage-design-form">
        @csrf
        <input type="hidden" name="return_to_settings" value="1">

        <div class="rcm-material-usage-design-grid">
            <div class="rcm-field rcm-material-usage-product-field">
                <label>Rice Product</label>
                <select class="rcm-searchable" name="product_id" required>
                    <option value="">Select Rice Product</option>
                    @foreach($products->where('active',1) as $product)
                        <option value="{{ $product->id }}" {{ (string)old('product_id') === (string)$product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="rcm-field rcm-material-usage-bag-field">
                <label>Bag Size (kg)</label>
                <input type="number" name="bag_size_kg" required min="{{ $rcmQuantityStep }}" step="{{ $rcmQuantityStep }}" value="{{ old('bag_size_kg') }}">
            </div>

            <div class="rcm-material-usage-material-column">
                <div class="rcm-material-usage-column-label">Packaging Material</div>
                <div class="rcm-material-usage-design-list">
                    @forelse($packagingMaterials as $material)
                        <div class="rcm-material-usage-material-row {{ $material->active ? '' : 'is-inactive' }}">
                            <input
                                type="text"
                                class="rcm-material-usage-material-display"
                                value="{{ $material->name }}{{ !empty($material->unit) ? ' '.$material->unit : '' }}{{ !$material->active ? ' - Inactive' : '' }}"
                                title="{{ $material->name }}"
                                readonly
                                tabindex="-1"
                                aria-label="Packaging Material {{ $material->name }}"
                            >
                        </div>
                    @empty
                        <div class="rcm-material-usage-material-row is-empty">
                            <input type="text" class="rcm-material-usage-material-display" value="No mapped Packaging Materials yet." readonly tabindex="-1">
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rcm-material-usage-usage-column">
                <div class="rcm-material-usage-column-label">Usage per Bag</div>
                <div class="rcm-material-usage-design-list">
                    @forelse($packagingMaterials as $material)
                        <div class="rcm-material-usage-design-input-row {{ $material->active ? '' : 'is-inactive' }}" data-material="{{ $material->name }}">
                            <input
                                type="number"
                                name="material_usages[{{ $material->id }}]"
                                min="0"
                                step="0.0001"
                                inputmode="decimal"
                                value="{{ old('material_usages.'.$material->id) }}"
                                placeholder="0.0000"
                                aria-label="Usage per Bag for {{ $material->name }}"
                                {{ $material->active ? '' : 'disabled' }}
                            >
                        </div>
                    @empty
                        <div class="rcm-material-usage-design-input-row is-empty">
                            <input type="text" disabled>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rcm-material-usage-save-column">
                <button class="rcm-btn rcm-material-usage-save-btn" type="submit" {{ $packagingMaterials->where('active',1)->isEmpty() ? 'disabled' : '' }}>
                    <i class="fa fa-save"></i> Save Mapping
                </button>
            </div>
        </div>

        @error('material_usages')
            <div class="rcm-alert rcm-alert-danger rcm-material-usage-error">{{ $message }}</div>
        @enderror
        @error('material_usages.*')
            <div class="rcm-alert rcm-alert-danger rcm-material-usage-error">{{ $message }}</div>
        @enderror

        @if($packagingMaterials->isEmpty())
            <div class="rcm-material-usage-empty-note rcm-muted">
                Map Products first in <strong>Product Category Mapping → Select Packaging Material</strong>. Only those mapped Products are shown here.
            </div>
        @endif
    </form>
</section>
