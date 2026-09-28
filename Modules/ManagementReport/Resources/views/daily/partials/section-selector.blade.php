<div class="mgmt-panel mgmt-section-selector-panel is-collapsed" data-mgmt-section-panel>
    <button
        type="button"
        class="mgmt-panel-header mgmt-section-toggle"
        data-mgmt-section-toggle
        aria-expanded="false"
        aria-controls="mgmt-section-selector-content"
    >
        <span class="mgmt-section-toggle-heading">
            <span class="mgmt-section-toggle-title"><i class="fa fa-list-alt"></i> Sections to Include</span>
            <span class="mgmt-section-toggle-help" data-mgmt-section-toggle-help>Click here to expand and view sections</span>
        </span>
        <span class="mgmt-section-toggle-meta">
            <span class="mgmt-selection-count"><strong data-mgmt-selected-count>0</strong> selected</span>
            <i class="fa fa-chevron-down mgmt-section-toggle-icon" aria-hidden="true"></i>
        </span>
    </button>

    <div id="mgmt-section-selector-content" class="mgmt-section-collapsible-content" data-mgmt-section-content hidden>
        <div class="mgmt-section-grid">
            @foreach($sections as $key => $section)
                <label class="mgmt-section-option">
                    <input type="checkbox" name="sections[]" value="{{ $key }}" {{ old('sections') ? (in_array($key, old('sections', [])) ? 'checked' : '') : (!empty($section['default']) ? 'checked' : '') }}>
                    <span class="mgmt-check-ui"><i class="fa fa-check"></i></span>
                    <span><strong>{{ $section['label'] }}</strong><small>Include this section in preview, print, PDF and shared link.</small></span>
                </label>
            @endforeach
        </div>
    </div>
</div>
