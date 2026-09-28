<style>
.action-buttons-fixed {
    position: sticky;
    top: 0;
    z-index: 10;
    background: #fff;
    padding: 10px 30px;
    margin-bottom: 10px;
}

.modal-body {
    max-height: calc(100vh - 220px);
    overflow-y: auto;
}

#operators-container {
    max-height: 60vh;
    overflow-y: auto;
    overflow-x: hidden;
}

.operator-block {
    border-bottom: 1px solid #ddd;
    padding-top: 10px;
}

.operator-block:last-child {
    border-bottom: none;
}

.operator-block:first-child .remove-block {
    display: none;
}

.operator-list-box {
    border: 1px solid #ced4da;
    max-height: 220px;
    overflow-y: auto;
}

.operator-item {
    padding: 6px 8px;
    cursor: pointer;
}

.operator-item:hover {
    background: #f5f5f5;
}

.operator-item.active {
    background: #d9edf7;
    font-weight: bold;
}
</style>
<div class="modal-dialog" role="document" style="width: 80%;">
    <div class="modal-content">

        {!! Form::open([
            'url' => action('\Modules\Petro\Http\Controllers\PumpOperatorMappingController@store'),
            'method' => 'post',
            'id' => 'pump_operator_mapping_form',
        ]) !!}
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                    aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('petro::lang.add_pump_to_operator')</h4>
        </div>

        <div class="modal-body">
            <div class="action-buttons-fixed">
                <div class="form-group">
                    <button type="button" id="reset-all" class="btn btn-warning">Reset</button>
                    <button type="button" id="add-operator-edit" class="btn btn-success">Add</button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('date', __('petro::lang.date')) !!}
                    {!! Form::text('date', null, [
                        'class' => 'form-control',
                        'id' => 'date',
                        'placeholder' => __('petro::lang.date'),
                        'readonly',
                    ]) !!}
                </div>
            </div>
            <div id="operators-container" class="col-md-12">
                <div class="operator-block row" style="display:none;">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>@lang('petro::lang.pump_operator')</label>

                            <input type="hidden" name="operator_id[]" class="selected-operator-id">

                            <input type="text" class="form-control operator-search"
                                placeholder="Type to filter operators">

                            <div class="operator-list-box operator-list">
                                @foreach ($pump_operators as $id => $name)
                                    <div class="operator-item" data-id="{{ $id }}">
                                        {{ $name }}
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            {!! Form::label('pump_id', __('petro::lang.pump')) !!}
                            {!! Form::select('pump_id[]', $pumps, null, [
                                'class' => 'form-control select-multiple',
                                'style' => 'width:100%;',
                                'data-placeholder' => __('petro::lang.please_select'),
                                'multiple' => 'multiple',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group" id="pump-products-container">
                            <label>@lang('petro::lang.product')</label>
                            <div id="pump-products-list">
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-link remove-block">
                            ×
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary" id="save_issue_bill_customer_btn">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>

<script>
/* ==========================================================
   REQUIRED: Disable Bootstrap modal focus trap (Select2 fix)
   ========================================================== */
$.fn.modal.Constructor.prototype.enforceFocus = function () {};

/* ==========================================================
   Date
   ========================================================== */
$('#date').datetimepicker({ defaultDate: moment() });

/* ==========================================================
   Select2 Init Helper
   ========================================================== */
function initSelect2(context) {
    context.find('.select-multiple').select2({
        dropdownParent: context.closest('.modal'),
        closeOnSelect: false,
        width: '100%'
    });
}

/* ==========================================================
   Add Operator Block
   ========================================================== */
function addOperator() {
    let template = $('.operator-block:first');

    let block = template.clone(true, true).show();
    block.find('.selected-operator-id').val('');
    block.find('.operator-search').val('');
    block.find('.operator-item').removeClass('active');
    block.find('#pump-products-list').empty();

    block.find('.select-multiple').val(null).empty();

    $('#operators-container').append(block);
    initSelect2(block);
    refreshPumpOptions();
}

addOperator();

$('#add-operator-edit').on('click', addOperator);

/* ==========================================================
   Remove Block
   ========================================================== */
$(document).on('click', '.remove-block', function () {
    if ($('.operator-block:visible').length > 1) {
        $(this).closest('.operator-block').remove();
        refreshPumpOptions();
    }
});

/* ==========================================================
   Reset
   ========================================================== */
$('#reset-all').on('click', function () {
    $('.operator-block:visible').remove();
    addOperator();
});

/* ==========================================================
   Operator Search & Select
   ========================================================== */
$(document).on('keyup', '.operator-search', function () {
    let val = $(this).val().toLowerCase();
    $(this).closest('.operator-block')
        .find('.operator-item')
        .each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(val));
        });
});

$(document).on('click', '.operator-item', function () {
    let block = $(this).closest('.operator-block');
    block.find('.operator-item').removeClass('active');
    $(this).addClass('active');
    block.find('.selected-operator-id').val($(this).data('id'));
    block.find('.operator-search').val($(this).text());
});

/* ==========================================================
   Refresh Pump Options (NO SCROLL RESET)
   ========================================================== */
function refreshPumpOptions() {
    let used = [];

    $('.operator-block:visible').each(function () {
        let v = $(this).find('.select-multiple').val();
        if (v) used.push(...v);
    });

    $('.operator-block:visible .select-multiple').each(function () {
        let select = $(this);
        let current = select.val() || [];
        let all = @json($pumps);

        select.empty();
        $.each(all, function (id, name) {
            if (!current.includes(id.toString()) && used.includes(id.toString())) return;
            select.append(new Option(name, id, false, current.includes(id.toString())));
        });

        select.trigger('change.select2');
    });
}

/* ==========================================================
   Load Products ONLY (NO DOM REBUILD)
   ========================================================== */
function loadProductsByPump(pumpIds, block) {
    $.get('/petro/get-products-by-pump', { pump_ids: pumpIds }, function (products) {
        let box = block.find('#pump-products-list');
        box.empty();
        products.forEach(p =>
            box.append(`<span class="badge badge-info">${p.product_name}</span>`)
        );
    });
}

/* ==========================================================
   Load Last Mapping INTO CURRENT BLOCK (CRITICAL FIX)
   ========================================================== */
$(document).on('change', '.select-multiple', function () {
    let block = $(this).closest('.operator-block');
    let pumpIds = $(this).val();
    if (!pumpIds || !pumpIds.length) return;

    $.get(`/pump-operator-mapping/last/${pumpIds[0]}`, function (response) {
        if (!response || !response.length) return;

        let map = response[0];

        block.find('.selected-operator-id').val(map.operator_id);
        block.find('.operator-search').val(map.operator_name);
        block.find('.operator-item').removeClass('active');
        block.find(`.operator-item[data-id="${map.operator_id}"]`).addClass('active');

        block.find('.select-multiple').val(map.pump_ids).trigger('change.select2');
        loadProductsByPump(map.pump_ids, block);
    });
});
</script>
<script>
$('#pump_operator_mapping_form').on('submit', function (e) {
    e.preventDefault();

    let mappings = [];

    $('.operator-block:visible').each(function () {
        let operatorId = $(this).find('.selected-operator-id').val();
        let pumpIds = $(this).find('.select-multiple').val();

        if (!operatorId || !pumpIds || !pumpIds.length) {
            toastr.error('Operator and pumps are required');
            return false;
        }

        mappings.push({
            operator_id: operatorId,
            pump_ids: pumpIds
        });
    });

    if (!mappings.length) {
        toastr.error('No mappings found');
        return;
    }

    $.ajax({
        url: $('#pump_operator_mapping_form').attr('action'),
        method: 'POST',
        contentType: 'application/json',
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: JSON.stringify({
            date: $('#date').val(),
            mappings: mappings
        }),
        success: function (res) {
            if (res.success) {
                toastr.success(res.message);
                $('.modal').modal('hide');
            } else {
                toastr.error(res.message);
            }
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error');
        }
    });
});
</script>
