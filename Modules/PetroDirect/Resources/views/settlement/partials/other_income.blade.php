<br>
<div class="row">
    <div class="col-md-12">
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('other_income_product_id', __( 'petrodirect::lang.service' ) ) !!}
                {!! Form::select('other_income_product_id', $services, null, ['class' => 'form-control other_income_fields check_pumper other_income_product', 'required',
                'placeholder' => __(
                'petrodirect::lang.please_select' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('other_income_price', __( 'petrodirect::lang.price' ) ) !!}
                {!! Form::text('other_income_price', null, ['class' => 'form-control other_income_fields check_pumper other_income_price input_number', 'readonly',
                'placeholder' => __(
                'petrodirect::lang.price' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('other_income_qty', __( 'petrodirect::lang.qty' ) ) !!}
                {!! Form::text('other_income_qty', null, ['class' => 'form-control other_income_fields check_pumper other_income_qty input_number', 'required',
                'placeholder' => __(
                'petrodirect::lang.qty' ) ]); !!}
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('other_income_reason', __( 'petrodirect::lang.reason' ) ) !!}
                {!! Form::text('other_income_reason', null, ['class' => 'form-control other_income_fields check_pumper other_income_reason', 'required',
                'placeholder' => __(
                'petrodirect::lang.reason' ) ]); !!}
            </div>
        </div>

        <div class="col-md-3">
            @can('edit_other_income_prices')
            <button type="button" class="btn btn-warning edit_price_other_income" style="margin-top: 23px; margin-right: 5px;">@lang('petrodirect::lang.edit_price')</button>
            @endcan
            <button type="button" class="btn btn-primary btn_other_income" style="margin-top: 23px;">@lang('messages.add')</button>
        </div>
    </div>
</div>
<br>
<br>
<div class="row">
    <div class="col-md-12">
        <table class="table table-bordered table-striped" id="other_income_table">
            <thead>
                <tr>
                    <th>@lang('petrodirect::lang.service' )</th>
                    <th>@lang('petrodirect::lang.qty' )</th>
                    <th>@lang('petrodirect::lang.reason' )</th>
                    <th>@lang('petrodirect::lang.sub_total' )</th>
                    <th>@lang('petrodirect::lang.action' )</th>
                </tr>
            </thead>
            <tbody>
                @php
                $other_income_final_total = 0.00;
                @endphp
                @if (!empty($active_settlement))
                @foreach ($active_settlement->other_incomes as $other_income_item)
                @php
                $product = App\Product::where('id', $other_income_item->product_id)->first();
                $other_income_sub_total = (float) $other_income_item->qty * (float) $other_income_item->price;
                $other_income_final_total = $other_income_final_total + $other_income_sub_total;
                @endphp
                <tr>
                    <td>{{$product->name}}</td>
                    <td>{{@num_format($other_income_item->qty)}}</td>
                    <td>{{$other_income_item->reason}}</td>
                    <td>{{@num_format($other_income_sub_total)}}</td>
                    <td><button class="btn btn-xs btn-danger delete_other_income" data-href="/petrodirect/settlement/delete-other-income/{{$other_income_item->id}}"><i
                                class="fa fa-times"></i></td>
                </tr>
                @endforeach
                @endif
            </tbody>

            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">@lang('petrodirect::lang.other_income_total') :</td>
                    <td style="text-align: left; font-weight: bold;" class="other_income_total">
                        {{@num_format( $other_income_final_total)}}</td>
                    <td></td>
                </tr>
                <input type="hidden" value="{{$other_income_final_total}}" name="other_income_total" id="other_income_total">
            </tfoot>
        </table>
    </div>
</div>


<style id="petrodirect-other-income-edit-price-modal-fix">
    /*
     * PetroDirect Other Income price editor.
     *
     * Do NOT use a Bootstrap modal here. The Add Payment payment-tab engine keeps a
     * hidden template bank and clones the active tab into the visible area, so a
     * nested Bootstrap modal produces duplicate IDs/backdrops and can become covered
     * or non-interactive. One body-level editor avoids both problems.
     */
    #pd_other_income_price_editor {
        display: none;
        position: fixed !important;
        inset: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 2147483000 !important;
        background: rgba(15, 23, 42, 0.42) !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 20px !important;
        pointer-events: auto !important;
    }

    #pd_other_income_price_editor.pd-price-editor-open {
        display: flex !important;
    }

    #pd_other_income_price_editor .pd-price-editor-panel {
        position: relative !important;
        z-index: 2147483001 !important;
        width: min(680px, calc(100vw - 40px)) !important;
        max-height: calc(100vh - 40px) !important;
        overflow: auto !important;
        background: #ffffff !important;
        border-radius: 14px !important;
        box-shadow: 0 24px 70px rgba(15, 23, 42, 0.35) !important;
        pointer-events: auto !important;
    }

    #pd_other_income_price_editor .pd-price-editor-header,
    #pd_other_income_price_editor .pd-price-editor-footer {
        display: flex !important;
        align-items: center !important;
        padding: 18px 24px !important;
    }

    #pd_other_income_price_editor .pd-price-editor-header {
        justify-content: space-between !important;
        border-bottom: 1px solid #e5e7eb !important;
    }

    #pd_other_income_price_editor .pd-price-editor-header h4 {
        margin: 0 !important;
        font-weight: 700 !important;
    }

    #pd_other_income_price_editor .pd-price-editor-body {
        padding: 24px !important;
    }

    #pd_other_income_price_editor .pd-price-editor-footer {
        justify-content: flex-end !important;
        gap: 10px !important;
        border-top: 1px solid #e5e7eb !important;
    }

    #pd_other_income_price_editor .pd-price-editor-close-x {
        border: 0 !important;
        background: transparent !important;
        font-size: 28px !important;
        line-height: 1 !important;
        padding: 2px 6px !important;
        cursor: pointer !important;
        color: #475569 !important;
    }

    #pd_other_income_price_editor input,
    #pd_other_income_price_editor button {
        pointer-events: auto !important;
    }
</style>

<script>
(function ($) {
    'use strict';

    var eventNs = '.petrodirectOtherIncomeEditPriceV2';
    var editorSelector = '#pd_other_income_price_editor';

    function normalisePrice(value) {
        return (value == null ? '' : String(value)).replace(/,/g, '').trim();
    }

    function findSourcePriceInput(trigger) {
        var $trigger = $(trigger);

        /*
         * The visible payment tab is a clone. Always start from the clicked clone so
         * duplicate IDs in the hidden template bank cannot redirect the edit to the
         * wrong Other Income input.
         */
        var $scope = $trigger.closest('.s271-rendered-tab');
        if (!$scope.length) {
            $scope = $trigger.closest('.s271-active-payment-content');
        }
        if (!$scope.length) {
            $scope = $trigger.closest('.tab-pane');
        }
        if (!$scope.length) {
            $scope = $trigger.closest('form');
        }
        if (!$scope.length) {
            $scope = $trigger.closest('.add_payment');
        }

        var $price = $scope.find('.other_income_price').first();
        if (!$price.length) {
            $price = $trigger.closest('.row').find('.other_income_price').first();
        }
        if (!$price.length) {
            $price = $('.s271-active-payment-content:visible .other_income_price').first();
        }

        return $price;
    }

    function ensureEditor() {
        var $editor = $(editorSelector);
        if ($editor.length) {
            return $editor;
        }

        /* Remove any stale legacy Bootstrap child modal left by an older AJAX load. */
        $('body > #edit_price_other_income').remove();

        var html = '' +
            '<div id="pd_other_income_price_editor" role="dialog" aria-modal="true" aria-hidden="true">' +
                '<div class="pd-price-editor-panel" role="document">' +
                    '<div class="pd-price-editor-header">' +
                        '<h4>Edit Price</h4>' +
                        '<button type="button" class="pd-price-editor-close-x" aria-label="Close">&times;</button>' +
                    '</div>' +
                    '<div class="pd-price-editor-body">' +
                        '<div class="form-group" style="margin-bottom:0;">' +
                            '<label for="pd_other_income_price_editor_input">Price:</label>' +
                            '<input type="text" id="pd_other_income_price_editor_input" class="form-control input_number" autocomplete="off" inputmode="decimal">' +
                        '</div>' +
                    '</div>' +
                    '<div class="pd-price-editor-footer">' +
                        '<button type="button" class="btn btn-primary pd-save-other-income-price">Save</button>' +
                        '<button type="button" class="btn btn-secondary pd-close-other-income-price">Close</button>' +
                    '</div>' +
                '</div>' +
            '</div>';

        $('body').append(html);
        return $(editorSelector);
    }

    function openEditor(trigger) {
        var $sourcePrice = findSourcePriceInput(trigger);
        if (!$sourcePrice.length) {
            return;
        }

        var $editor = ensureEditor();
        var currentPrice = normalisePrice($sourcePrice.val());
        if (currentPrice === '' || isNaN(currentPrice)) {
            currentPrice = '0.00';
        }

        $editor.data('pdSourcePriceInput', $sourcePrice[0]);
        $editor.attr('aria-hidden', 'false').addClass('pd-price-editor-open');
        $('#pd_other_income_price_editor_input').val(currentPrice);

        window.setTimeout(function () {
            $('#pd_other_income_price_editor_input').trigger('focus').select();
        }, 20);
    }

    function closeEditor() {
        var $editor = $(editorSelector);
        $editor.attr('aria-hidden', 'true').removeClass('pd-price-editor-open');
        $editor.removeData('pdSourcePriceInput');
    }

    $(document)
        .off('click' + eventNs, '.edit_price_other_income')
        .on('click' + eventNs, '.edit_price_other_income', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            openEditor(this);
            return false;
        });

    $(document)
        .off('click' + eventNs, '.pd-save-other-income-price')
        .on('click' + eventNs, '.pd-save-other-income-price', function (e) {
            e.preventDefault();
            e.stopPropagation();

            var $editor = $(editorSelector);
            var sourceNode = $editor.data('pdSourcePriceInput');
            var value = normalisePrice($('#pd_other_income_price_editor_input').val());

            if (value === '' || isNaN(value) || parseFloat(value) < 0) {
                if (window.toastr && toastr.error) {
                    toastr.error('Please enter a valid price.');
                }
                $('#pd_other_income_price_editor_input').trigger('focus').select();
                return false;
            }

            var normalised = parseFloat(value).toFixed(2);
            if (sourceNode && document.documentElement.contains(sourceNode)) {
                $(sourceNode).val(normalised).trigger('change');
            }

            closeEditor();
            return false;
        });

    $(document)
        .off('click' + eventNs, '.pd-close-other-income-price, .pd-price-editor-close-x')
        .on('click' + eventNs, '.pd-close-other-income-price, .pd-price-editor-close-x', function (e) {
            e.preventDefault();
            closeEditor();
        });

    $(document)
        .off('mousedown' + eventNs, editorSelector)
        .on('mousedown' + eventNs, editorSelector, function (e) {
            if (e.target === this) {
                closeEditor();
            }
        });

    $(document)
        .off('keydown' + eventNs)
        .on('keydown' + eventNs, function (e) {
            if (e.key === 'Escape' && $(editorSelector).hasClass('pd-price-editor-open')) {
                e.preventDefault();
                closeEditor();
            }
        });

    $(document)
        .off('keydown' + eventNs, '#pd_other_income_price_editor_input')
        .on('keydown' + eventNs, '#pd_other_income_price_editor_input', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $(editorSelector).find('.pd-save-other-income-price').trigger('click');
            }
        });
})(jQuery);
</script>