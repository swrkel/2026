@extends('layouts.app')
@section('title', __('business.business_locations'))
@section('css')
<style>
    .business-location-table-wrap {
        width: 100%;
        overflow-x: visible;
    }

    #business_location_table {
        width: 100% !important;
        table-layout: fixed;
        margin-bottom: 0;
        font-size: 12px;
    }

    #business_location_table thead th {
        padding: 8px 4px;
        text-align: center;
        vertical-align: middle;
        white-space: normal;
        line-height: 1.12;
        overflow-wrap: normal;
        word-break: normal;
    }

    #business_location_table tbody td {
        padding: 10px 8px;
        vertical-align: middle;
        white-space: normal;
        line-height: 1.3;
        overflow-wrap: break-word;
        word-break: normal;
    }

    #business_location_table .two-line-heading {
        display: inline-block;
        min-width: 54px;
        white-space: normal;
        line-height: 1.05;
        text-align: center;
    }

    #business_location_table thead th:nth-child(3),
    #business_location_table tbody td:nth-child(3),
    #business_location_table thead th:last-child,
    #business_location_table tbody td:last-child {
        text-align: center;
    }

    #business_location_table .view-location-landmark {
        display: inline-flex;
        width: 100%;
        max-width: 96px;
        min-height: 42px;
        align-items: center;
        justify-content: center;
        gap: 4px;
        margin: 0 auto;
        padding: 6px 8px;
        white-space: normal;
        box-sizing: border-box;
        font-size: 12px;
        line-height: 1.05;
        text-align: center;
    }

    #business_location_table .view-location-landmark .landmark-button-label {
        display: inline-block;
        white-space: normal;
        line-height: 1.05;
    }

    #business_location_table .business-location-action-menu {
        display: inline-block;
        text-align: left;
    }

    #business_location_table .business-location-action-parent {
        min-width: 92px;
        padding-left: 8px;
        padding-right: 8px;
    }

    #business_location_table .business-location-action-menu .dropdown-menu {
        min-width: 220px;
        padding: 7px;
        z-index: 1200;
        border-radius: 7px;
    }

    #business_location_table .business-location-action-menu .dropdown-menu > li {
        margin: 0 0 5px;
    }

    #business_location_table .business-location-action-menu .dropdown-menu > li:last-child {
        margin-bottom: 0;
    }

    #business_location_table .business-location-action-menu .dropdown-menu > .divider {
        margin: 7px 0;
    }

    #business_location_table .business-location-menu-item {
        display: block;
        width: 100%;
        min-height: 32px;
        padding: 7px 10px;
        border: 0;
        border-radius: 5px;
        color: #fff !important;
        font-weight: 600;
        line-height: 18px;
        text-align: left;
        white-space: normal;
        text-decoration: none !important;
        cursor: pointer;
        font-size: 13px;
    }

    #business_location_table .business-location-menu-item i {
        width: 18px;
        margin-right: 3px;
        text-align: center;
    }

    #business_location_table .location-edit-menu-item {
        background: #2d8cff;
    }

    #business_location_table .location-settings-menu-item {
        background: #16a765;
    }

    #business_location_table .location-deactivate-menu-item {
        background: #f39c12;
    }

    #business_location_table .location-activate-menu-item {
        background: #20a957;
    }

    #business_location_table .location-delete-menu-item {
        background: #e74c3c;
    }

    #business_location_table .location-delete-checking {
        display: block;
        padding: 7px 10px;
        color: #777;
        font-size: 12px;
        font-weight: 600;
        text-align: left;
        white-space: nowrap;
    }

    #business_location_table .business-location-menu-item:hover,
    #business_location_table .business-location-menu-item:focus {
        color: #fff !important;
        opacity: 0.88;
        outline: none;
    }

    #location_landmark_text {
        min-height: 70px;
        margin-bottom: 0;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .location_add_modal .select2-container,
    .location_edit_modal .select2-container {
        width: 100% !important;
    }

    @media (max-width: 1366px) {
        #business_location_table {
            font-size: 11px;
        }

        #business_location_table thead th,
        #business_location_table tbody td {
            padding-left: 3px;
            padding-right: 3px;
        }
    }
</style>
@endsection
@php
$business_or_entity = App\System::getProperty('business_or_entity');
@endphp
@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@if($business_or_entity == 'business'){{ __('business.business_locations') }} @elseif($business_or_entity == 'entity'){{ __('lang_v1.entity_locations') }} @else  {{ __('business.business_locations') }} @endif
        <small>@if($business_or_entity == 'business'){{ __('business.manage_your_business_locations') }} @elseif($business_or_entity == 'entity'){{ __('lang_v1.manage_your_entity_locations') }} @else {{ __('business.manage_your_business_locations') }} @endif</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    @if($business_or_entity == 'business')
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'business.all_your_business_locations' )])
    @elseif($business_or_entity == 'entity')
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_your_entity_locations' )])
    @else
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'business.all_your_business_locations' )])
    @endif
        @slot('tool')
            <div class="box-tools pull-right">
                <button type="button" class="btn  btn-primary btn-modal" 
                    data-href="{{action('BusinessLocationController@create')}}" 
                    data-container=".location_add_modal">
                    <i class="fa fa-plus"></i> @lang( 'messages.add' )</button>
            </div>
            <hr>
        @endslot
        <div class="table-responsive business-location-table-wrap">
            <table class="table table-bordered table-striped" id="business_location_table">
                <colgroup>
                    <col style="width: 18%;">
                    <col style="width: 8%;">
                    <col style="width: 14%;">
                    <col style="width: 14%;">
                    <col style="width: 8%;">
                    <col style="width: 11%;">
                    <col style="width: 11%;">
                    <col style="width: 16%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>@lang( 'invoice.name' )</th>
                        <th><span>@lang( 'lang_v1.location_id' )</span></th>
                        <th>@lang( 'business.landmark' )</th>
                        <th><span>@lang( 'lang_v1.price_group' )</span></th>
                        <th>@lang( 'invoice.currency' )</th>
                        <th><span class="two-line-heading">Invoice<br>Scheme</span></th>
                        <th><span class="two-line-heading">Invoice<br>Layouts</span></th>
                        <th>@lang( 'messages.action' )</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    <div class="modal fade location_add_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>
    <div class="modal fade location_edit_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade" id="location_landmark_modal" tabindex="-1" role="dialog"
        aria-labelledby="location_landmark_modal_label">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <h4 class="modal-title" id="location_landmark_modal_label">
                        <i class="fa fa-map-marker"></i> Landmark Details
                    </h4>
                </div>
                <div class="modal-body">
                    <div id="location_landmark_text" class="well well-sm"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        @lang('messages.close')
                    </button>
                </div>
            </div>
        </div>
    </div>

</section>
<!-- /.content -->

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).on('click', '.view-location-landmark', function () {
        var landmark = $(this).attr('data-landmark') || '';

        $('#location_landmark_text').text(landmark);
        $('#location_landmark_modal').modal('show');
    });

    $('#location_landmark_modal').on('hidden.bs.modal', function () {
        $('#location_landmark_text').empty();
    });

    /*
     * Open the Action menu upward for the last rows so the dropdown is not
     * clipped by the responsive table container.
     */
    $(document).on('show.bs.dropdown', '.business-location-action-menu', function () {
        var $group = $(this);
        var $wrapper = $group.closest('.table-responsive');

        $group.removeClass('dropup');

        if (!$wrapper.length) {
            return;
        }

        var groupRect = this.getBoundingClientRect();
        var wrapperRect = $wrapper[0].getBoundingClientRect();
        var spaceBelow = wrapperRect.bottom - groupRect.bottom;
        var spaceAbove = groupRect.top - wrapperRect.top;

        if (spaceBelow < 230 && spaceAbove > spaceBelow) {
            $group.addClass('dropup');
        }
    });

    $(document).on('hidden.bs.dropdown', '.business-location-action-menu', function () {
        $(this).removeClass('dropup');
    });

    /*
     * Delete eligibility is checked only when a row's Action menu is opened.
     * This keeps the DataTable request instant and still ensures Delete is
     * never displayed for a location already used by operational records.
     */
    $(document).on('show.bs.dropdown', '.business-location-action-menu', function () {
        var $menu = $(this);
        var $slot = $menu.find('.location-delete-slot');

        if (!$slot.length || $slot.data('loaded') || $slot.data('loading')) {
            return;
        }

        $slot.data('loading', true).html(
            '<span class="location-delete-checking"><i class="fa fa-spinner fa-spin"></i> Checking...</span>'
        );

        $.ajax({
            url: $slot.data('check-url'),
            method: 'GET',
            dataType: 'json',
            cache: false,
        })
            .done(function (result) {
                if (!result || result.success !== true || result.can_delete !== true) {
                    $slot.prev('.location-delete-divider').remove();
                    $slot.remove();
                    return;
                }

                var $button = $('<button>', {
                    type: 'button',
                    class: 'business-location-menu-item location-delete-menu-item delete-business-location',
                    'data-href': $slot.data('delete-url'),
                });

                $button.append($('<i>', { class: 'glyphicon glyphicon-trash' }));
                $button.append(document.createTextNode(' ' + @json(__('messages.delete'))));

                $slot.empty().append($button).data('loaded', true).removeData('loading');
            })
            .fail(function () {
                // Fail closed: do not display Delete when usage cannot be verified.
                $slot.prev('.location-delete-divider').remove();
                $slot.remove();
            });
    });
</script>
@endsection
