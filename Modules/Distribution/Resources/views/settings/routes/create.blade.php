<div class="modal-dialog" role="document">
  <div class="modal-content">

    {!! Form::open(['url' => action('\Modules\Distribution\Http\Controllers\DistributionRoutesController@store'), 'method' =>
    'post', 'id' => !empty($quick_add) ? 'quick_add_route' : 'routes_add_form' ]) !!}

    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
          aria-hidden="true">&times;</span></button>
      <h4 class="modal-title">Routes</h4>
    </div>

    <div class="modal-body">
      <div class="row">
         <div class="form-group col-sm-12">
          <label>Route No</label>
          <input type="text" class="form-control" value="{{$id}}" disabled>
        </div>
        
        <div class="form-group col-sm-12">
          {!! Form::label('name', 'Name' . ':*') !!}
          {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => 'Name', 'id'
          => 'name']); !!}
        </div>
        
        <div class="form-group col-sm-12">
         {!! Form::label('province_id', 'Province' . ':*') !!}
          <select name="province_id[]" id="province_id" class="form-control select2 input-sm" required multiple data-placeholder="Please Select">
            @foreach($provinces as $key => $value)
              <option value="{{ $key }}">{{ $value }}</option>
            @endforeach
          </select>
        </div> 
        
        <div class="form-group col-sm-12">
          {!! Form::label('district_id[]', 'District' . ':*') !!}
          <select name="district_id[]" id="district_id" class="form-control select2 input-sm" required multiple data-placeholder="Please Select">
            @foreach($districts as $district)
              <option value="{{ $district->id }}" data-province-id="{{ $district->province_id }}">{{ $district->name }}</option>
            @endforeach
          </select>
        </div>
        
        <div class="form-group col-sm-12">
          {!! Form::label('area_id', 'Area' . ':*') !!}
          <select name="area_id[]" id="area_id" class="form-control select2 input-sm" required multiple data-placeholder="Please Select">
            @foreach($areas as $area)
              <option value="{{ $area->id }}" data-district-id="{{ $area->district_id }}">{{ $area->name }}</option>
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
(function() {
    function initDistributionRouteForm(context) {
        var $ctx = context ? $(context) : $(document);
        var $form = $ctx.find('#routes_add_form, #routes_edit_form, #quick_add_route').first();
        if (!$form.length) { return; }
        var $province = $form.find('#province_id');
        var $district = $form.find('#district_id');
        var $area = $form.find('#area_id');
        if (!$province.length || !$district.length || !$area.length) { return; }
        if (!$district.data('all-options-html')) { $district.data('all-options-html', $district.html()); }
        if (!$area.data('all-options-html')) { $area.data('all-options-html', $area.html()); }
        function normalise(values) {
            values = values || [];
            if (!$.isArray(values)) { values = [values]; }
            return $.map(values, function(v) { return String(v); });
        }
        function refreshSelect2($el) {
            if ($el.hasClass('select2-hidden-accessible')) { $el.trigger('change.select2'); }
            else if ($.fn.select2) { $el.select2({ width:'100%', dropdownParent:$form.closest('.modal') }); }
        }
        function filterDistricts() {
            var selected = normalise($province.val());
            var previous = normalise($district.val());
            var html = '';
            $($district.data('all-options-html')).each(function() {
                var $opt = $(this);
                if (!$opt.val()) { return; }
                var provinceId = String($opt.data('province-id') || $opt.attr('data-province-id') || '');
                if (selected.length && selected.indexOf(provinceId) !== -1) {
                    html += $('<div>').append($opt.clone()).html();
                }
            });
            $district.html(html);
            var keep = previous.filter(function(v){ return $district.find('option[value="'+v+'"]').length; });
            $district.val(keep).trigger('change');
            refreshSelect2($district);
            filterAreas();
        }
        function filterAreas() {
            var selected = normalise($district.val());
            var previous = normalise($area.val());
            var html = '';
            $($area.data('all-options-html')).each(function() {
                var $opt = $(this);
                if (!$opt.val()) { return; }
                var districtId = String($opt.data('district-id') || $opt.attr('data-district-id') || '');
                if (selected.length && selected.indexOf(districtId) !== -1) {
                    html += $('<div>').append($opt.clone()).html();
                }
            });
            $area.html(html);
            var keep = previous.filter(function(v){ return $area.find('option[value="'+v+'"]').length; });
            $area.val(keep).trigger('change');
            refreshSelect2($area);
        }
        $province.off('change.disRouteFilter').on('change.disRouteFilter', filterDistricts);
        $district.off('change.disAreaFilter').on('change.disAreaFilter', filterAreas);
        refreshSelect2($province); refreshSelect2($district); refreshSelect2($area);
        filterDistricts();
    }
    $(document).on('shown.bs.modal', '.modal', function(){ initDistributionRouteForm(this); });
    $(document).ready(function(){ initDistributionRouteForm(document); });
})();
</script>
