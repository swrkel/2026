{{--
 | Cascading country / province / district fields.
 | Document MA 007
 |
 | WHY THIS EXISTS
 |
 | country, state and city in business_locations were free text. Measured
 | on nivasa_base before this change:
 |
 |   country   "Sri Lanka" (5 rows) and "srilanka" (9 rows)
 |   state     "Western" (a province), "Colombo" (a district), and "1"
 |   district  NULL on every row - the column existed but no form wrote it
 |   city      "Colombo", "col", "M", "Malabe"
 |
 | A Super Admin report grouped by country would have shown two Sri Lankas;
 | by province it would have treated Colombo as one; by district it would
 | have returned nothing at all.
 |
 | HOW IT WORKS
 |
 | The lists come from admin_divisions in the CENTRAL database, so there is
 | one list for every tenant. The chosen values are stored as text in the
 | tenant's own business_locations row, exactly as country and state are
 | stored today - so reports inside a tenant need no cross-database join.
 |
 | Each level loads only when the level above is chosen. Nothing ever
 | fetches a full global list.
 |
 | city stays free text: Sri Lanka has thousands of towns and no clean
 | standard list, and reporting is at district level.
 |
 | Expects: $adminCountries or $countries (country => country array).
--}}

<div class="col-sm-6">
  <div class="form-group">
    {!! Form::label('country', __('business.country') . ':*') !!}
    {!! Form::select('country', $adminCountries ?? ($countries ?? []), null, [
        'class' => 'form-control ad-plain-select',
        'id' => 'ad_country',
        'placeholder' => __('business.country'),
        'required',
        'style' => 'width: 100%;',
        'data-placeholder' => __('business.country'),
    ]) !!}
  </div>
</div>

<div class="col-sm-6">
  <div class="form-group">
    {!! Form::label('state', __('business.state') . ':*') !!}
    {!! Form::select('state', $adminProvinces ?? [], null, [
        'class' => 'form-control ad-plain-select',
        'id' => 'ad_state',
        'placeholder' => __('business.state'),
        'required',
        'style' => 'width: 100%;',
        'data-placeholder' => __('business.state'),
    ]) !!}
    <small class="text-muted ad-hint" id="ad_state_hint" style="display:none;">
      Choose a country first.
    </small>
  </div>
</div>

<div class="col-sm-6">
  <div class="form-group">
    {!! Form::label('district', 'District:') !!}
    {!! Form::select('district', $adminDistricts ?? [], null, [
        'class' => 'form-control ad-plain-select',
        'id' => 'ad_district',
        'placeholder' => 'District',
        'style' => 'width: 100%;',
        'data-placeholder' => 'District',
    ]) !!}
    <small class="text-muted ad-hint" id="ad_district_hint" style="display:none;">
      Choose a province first.
    </small>
  </div>
</div>

<div class="col-sm-6">
  <div class="form-group">
    {{-- City sits beside District: the two are read together, and city is the
         one level with no controlled list - Sri Lanka has thousands of towns
         and no clean standard source, so it stays free text. --}}
    {!! Form::label('city', __('business.city') . ':*') !!}
    {!! Form::text('city', null, [
        'class' => 'form-control',
        'placeholder' => __('business.city'),
        'required',
    ]) !!}
  </div>
</div>

<script>
/*
 | Plain <select> elements, deliberately.
 |
 | These three fields previously used Select2, matching the rest of the form.
 | Inside this modal Select2 appends its dropdown to <body> and computes the
 | position from the field offset, which came out wrong: measured at
 | top:-97px, above the viewport, so the field appeared not to open at all.
 |
 | The textbook fix - re-initialising with dropdownParent - collided with
 | ERPDesign.refreshSelect2(), which matches 'select.select2,
 | select.form-control' and initialises the same elements. The two fought and
 | left the field unusable.
 |
 | A native select opens where the browser puts it, which is always correct.
 | The only thing lost is type-to-search, which is of little value for nine
 | provinces and twenty-five districts.
 |
 | The class is 'ad-plain-select' precisely so no other script claims these.
 */
(function () {
    function fill($select, items, keep, placeholder) {
        $select.empty().append($('<option>').val('').text(placeholder));
        $.each(items, function (i, name) {
            $select.append($('<option>').val(name).text(name));
        });
        if (keep && items.indexOf(keep) !== -1) {
            $select.val(keep);
        }
    }

    function loadProvinces($form, keep) {
        var country = $form.find('#ad_country').val();
        var $state = $form.find('#ad_state');
        var $district = $form.find('#ad_district');

        if (!country) {
            fill($state, [], null, 'Select a country first');
            fill($district, [], null, 'Select a province first');
            return;
        }

        fill($state, [], null, 'Loading...');

        $.getJSON('{{ url("admin-divisions/provinces") }}', { country: country })
            .done(function (list) {
                fill($state, list || [], keep, 'Select a province');
                loadDistricts($form, null);
            })
            .fail(function () {
                fill($state, [], null, 'Could not load provinces');
            });
    }

    function loadDistricts($form, keep) {
        var country = $form.find('#ad_country').val();
        var state = $form.find('#ad_state').val();
        var $district = $form.find('#ad_district');

        if (!state) {
            fill($district, [], null, 'Select a province first');
            return;
        }

        fill($district, [], null, 'Loading...');

        $.getJSON('{{ url("admin-divisions/districts") }}', { country: country, province: state })
            .done(function (list) {
                fill($district, list || [], keep, 'Select a district');
            })
            .fail(function () {
                fill($district, [], null, 'Could not load districts');
            });
    }

    // Delegated: the modal's contents are loaded after the page, so binding
    // directly to the elements would attach to nothing.
    $(document).on('change', '#ad_country', function () {
        loadProvinces($(this).closest('form'), null);
    });

    $(document).on('change', '#ad_state', function () {
        loadDistricts($(this).closest('form'), null);
    });

    // On edit, the country is already set - populate the levels below it.
    $(document).on('shown.bs.modal', function (event) {
        var $country = $(event.target).find('#ad_country');
        if ($country.length && $country.val()) {
            var $form = $country.closest('form');
            loadProvinces($form, $form.find('#ad_state').val());
        }
    });
}());
</script>
