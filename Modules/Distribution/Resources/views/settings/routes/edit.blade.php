<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\Distribution\Http\Controllers\DistributionRoutesController@update', $route->id), 'method' =>
    'put', 'id' => 'area_edit_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">Routes</h4>
    </div>

    <div class="modal-body">
      <div class="row">
        <div class="form-group col-sm-12">
          {!! Form::label('name', 'Name' . ':*') !!}
          {!! Form::text('name', $route->name, ['class' => 'form-control', 'required', 'placeholder' => 'Name', 'id'
          => 'name']); !!}
        </div>
        
        <div class="form-group col-sm-12">
         {!! Form::label('province_id', 'Province' . ':*') !!}
          <select name="province_id[]" id="province_id" class="form-control select2 input-sm" multiple data-placeholder="Please Select">
            @foreach($provinces as $key => $value)
              <option value="{{ $key }}" {{ in_array($key, json_decode($route->province_id) ?? []) ? 'selected' : '' }}>{{ $value }}</option>
            @endforeach
          </select>
        </div>  
        
        <div class="form-group col-sm-12">
          {!! Form::label('district_id', 'District' . ':*') !!}
          <select name="district_id[]" id="district_id" class="form-control select2 input-sm" required multiple data-placeholder="Please Select">
            @foreach($districts as $district)
              <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}" {{ in_array($district->id, json_decode($route->district_id) ?? []) ? 'selected' : '' }}>{{ $district->name }}</option>
            @endforeach
          </select>
        </div>
        
        <div class="form-group col-sm-12">
          {!! Form::label('area_id', 'Area' . ':*') !!}
          <select name="area_id[]" id="area_id" class="form-control select2 input-sm" required multiple data-placeholder="Please Select">
            @foreach($areas as $area)
              <option value="{{ $area->id }}" data-district-id="{{ $area->district_id }}" {{ in_array($area->id, json_decode($route->area_id) ?? []) ? 'selected' : '' }}>{{ $area->name }}</option>
            @endforeach
          </select>
        </div>
             
      </div>

    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary">@lang( 'messages.save' )</button>
      <button type="button" class="btn btn-default" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>

    {!! Form::close() !!}

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

    <script>
    $(document).ready(function() {
        // Initialize when modal is shown
        $(document).on('shown.bs.modal', '.modal', function() {
            var $modal = $(this);
            
            // Only initialize if this modal contains our form
            if ($modal.find('#province_id').length === 0) {
                return;
            }
            
            // Store all districts and areas for client-side filtering
            var allDistricts = [];
            var allAreas = [];
            var selectedDistricts = $modal.find('#district_id').val() || [];
            var selectedAreas = $modal.find('#area_id').val() || [];
            
            // Collect all districts with their province_id
            $modal.find('#district_id option').each(function() {
                if ($(this).val()) {
                    allDistricts.push({
                        value: $(this).val(),
                        text: $(this).text(),
                        provinceId: $(this).data('province-id') || ''
                    });
                }
            });
            
            // Collect all areas with their district_id
            $modal.find('#area_id option').each(function() {
                if ($(this).val()) {
                    allAreas.push({
                        value: $(this).val(),
                        text: $(this).text(),
                        districtId: $(this).data('district-id') || ''
                    });
                }
            });
            
            // Destroy existing Select2 instances if any
            if ($modal.find('#province_id').hasClass('select2-hidden-accessible')) {
                $modal.find('#province_id').select2('destroy');
            }
            if ($modal.find('#district_id').hasClass('select2-hidden-accessible')) {
                $modal.find('#district_id').select2('destroy');
            }
            if ($modal.find('#area_id').hasClass('select2-hidden-accessible')) {
                $modal.find('#area_id').select2('destroy');
            }
            
            // For edit form, preserve existing selections but clear if empty to show placeholder
            var provinceVal = $modal.find('#province_id').val();
            if (!provinceVal || provinceVal.length === 0) {
                $modal.find('#province_id').val(null);
            }
            
            // Initialize select2 with placeholder for multi-select
            $modal.find('#province_id').select2({
                placeholder: "Please Select",
                allowClear: false,
                width: '100%',
                closeOnSelect: false,
                dropdownParent: $modal
            });
            
            $modal.find('#district_id').select2({
                placeholder: "Please Select",
                allowClear: false,
                width: '100%',
                closeOnSelect: false,
                dropdownParent: $modal
            });
            
            $modal.find('#area_id').select2({
                placeholder: "Please Select",
                allowClear: false,
                width: '100%',
                closeOnSelect: false,
                dropdownParent: $modal
            });
            
            // Filter districts based on selected provinces (instant client-side filtering)
            $modal.find('#province_id').off('change.filterDistricts').on('change.filterDistricts', function() {
                var selectedProvinces = $(this).val() || [];
                var currentSelectedDistricts = $modal.find('#district_id').val() || [];
                
                // Clear and repopulate districts based on selected provinces
                $modal.find('#district_id').empty();
                
                if (selectedProvinces.length === 0) {
                    // If no provinces selected, show all districts
                    allDistricts.forEach(function(district) {
                        var isSelected = currentSelectedDistricts.indexOf(district.value) !== -1 || currentSelectedDistricts.indexOf(String(district.value)) !== -1;
                        $modal.find('#district_id').append('<option value="' + district.value + '" data-province-id="' + district.provinceId + '"' + (isSelected ? ' selected' : '') + '>' + district.text + '</option>');
                    });
                } else {
                    // Filter districts that belong to selected provinces
                    allDistricts.forEach(function(district) {
                        var provinceIdStr = String(district.provinceId);
                        if (selectedProvinces.indexOf(provinceIdStr) !== -1 || selectedProvinces.indexOf(Number(district.provinceId)) !== -1) {
                            var isSelected = currentSelectedDistricts.indexOf(district.value) !== -1 || currentSelectedDistricts.indexOf(String(district.value)) !== -1;
                            $modal.find('#district_id').append('<option value="' + district.value + '" data-province-id="' + district.provinceId + '"' + (isSelected ? ' selected' : '') + '>' + district.text + '</option>');
                        }
                    });
                }
                
                $modal.find('#district_id').trigger('change');
            });
            
            // Filter areas based on selected districts (instant client-side filtering)
            $modal.find('#district_id').off('change.filterAreas').on('change.filterAreas', function() {
                var selectedDistricts = $(this).val() || [];
                var currentSelectedAreas = $modal.find('#area_id').val() || [];
                
                // Clear and repopulate areas based on selected districts
                $modal.find('#area_id').empty();
                
                if (selectedDistricts.length === 0) {
                    // If no districts selected, show all areas
                    allAreas.forEach(function(area) {
                        var isSelected = currentSelectedAreas.indexOf(area.value) !== -1 || currentSelectedAreas.indexOf(String(area.value)) !== -1;
                        $modal.find('#area_id').append('<option value="' + area.value + '" data-district-id="' + area.districtId + '"' + (isSelected ? ' selected' : '') + '>' + area.text + '</option>');
                    });
                } else {
                    // Filter areas that belong to selected districts
                    allAreas.forEach(function(area) {
                        var districtIdStr = String(area.districtId);
                        if (selectedDistricts.indexOf(districtIdStr) !== -1 || selectedDistricts.indexOf(Number(area.districtId)) !== -1) {
                            var isSelected = currentSelectedAreas.indexOf(area.value) !== -1 || currentSelectedAreas.indexOf(String(area.value)) !== -1;
                            $modal.find('#area_id').append('<option value="' + area.value + '" data-district-id="' + area.districtId + '"' + (isSelected ? ' selected' : '') + '>' + area.text + '</option>');
                        }
                    });
                }
                
                $modal.find('#area_id').trigger('change');
            });
        });
    });
</script>

