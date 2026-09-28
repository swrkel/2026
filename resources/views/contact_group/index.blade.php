@extends('layouts.app')
@section('title', __( 'lang_v1.contact_groups' ))

@section('content')

<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h5 class="page-title pull-left">Contact Groups</h5>
                <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                    <li><a href="#">Contact</a></li>
                    <li><span>Contact Groups</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Main content -->
<section class="content main-content-inner">

    <div class="settlement_tabs">
        <ul class="nav nav-tabs">
            @if($contact_customer)
            <li class="active">
                <a href="#customer" data-toggle="tab">
                    <i class="fa fa-address-book"></i> <strong>@lang('lang_v1.customer')</strong>
                </a>
            </li>
            @endif
            @if($contact_supplier)
            <li class=" @if(!$contact_customer) active @endif">
                <a href="#supplier" data-toggle="tab">
                    <i class="fa fa-address-book"></i> <strong>
                        @lang('lang_v1.supplier') </strong>
                </a>
            </li>
            @endif
        </ul>
        <div class="tab-content">
            @if($contact_customer)
            <div class="tab-pane active" id="customer">
                <div class="row">
                    <div class="col-md-12">
                        @include('contact_group.partials.customer_group')
                    </div>
                </div>
            </div>
            @endif
            @if($contact_supplier)
            <div class="tab-pane @if(!$contact_customer) active @endif" id="supplier">
                <div class="row">
                    <div class="col-md-12">
                        @include('contact_group.partials.supplier_group')
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>

    <div class="modal fade contact_groups_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>

</section>
<!-- /.content -->
<style>
  .nav-tabs-custom>.nav-tabs>li.active a{
    color:#3c8dbc;
  }
  .nav-tabs-custom>.nav-tabs>li.active a:hover{
    color:#3c8dbc;
  }
</style>
@endsection

@section('javascript')
<script type="text/javascript">
    /*
     * CG-002 Contact Groups Add Modal Fix
     * The standard .btn-modal handler was not firing reliably on this tab page.
     * This local delegated handler loads the create form into .contact_groups_modal
     * for both Customer Group and Supplier Group Add buttons.
     */
    $(document).off('click.cgAddGroup', '.contact-group-add-btn').on('click.cgAddGroup', '.contact-group-add-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var $btn = $(this);
        var url = $btn.data('href') || $btn.attr('href');
        var container = $btn.data('container') || '.contact_groups_modal';
        var $modal = $(container).first();

        if (!url) {
            if (typeof toastr !== 'undefined') {
                toastr.error('Unable to open Contact Group form. Add URL not found.');
            } else {
                alert('Unable to open Contact Group form. Add URL not found.');
            }
            return false;
        }

        if (!$modal.length) {
            $('body').append('<div class="modal fade contact_groups_modal" tabindex="-1" role="dialog" aria-hidden="true"></div>');
            $modal = $('.contact_groups_modal').first();
        }

        $modal.appendTo('body');
        $modal.html(
            '<div class="modal-dialog" role="document">' +
                '<div class="modal-content">' +
                    '<div class="modal-body text-center" style="padding:30px;">' +
                        '<i class="fa fa-spinner fa-spin"></i> Loading...' +
                    '</div>' +
                '</div>' +
            '</div>'
        );

        $modal.modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'html',
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function (html) {
                $modal.html(html).appendTo('body');
                $modal.modal({
                    backdrop: 'static',
                    keyboard: false,
                    show: true
                });

                setTimeout(function () {
                    if ($.fn.select2) {
                        $modal.find('.select2').each(function () {
                            var $select = $(this);
                            try {
                                if ($select.hasClass('select2-hidden-accessible')) {
                                    $select.select2('destroy');
                                }
                            } catch (ignore) {}
                            $select.select2({
                                width: '100%',
                                dropdownParent: $modal.find('.modal-content').first()
                            });
                        });
                    }
                }, 100);
            },
            error: function (xhr) {
                $modal.modal('hide');
                var msg = 'Unable to open Contact Group form.';
                if (xhr.status) {
                    msg += ' Server status: ' + xhr.status;
                }
                if (typeof toastr !== 'undefined') {
                    toastr.error(msg);
                } else {
                    alert(msg);
                }
            }
        });

        return false;
    });

    $(document).on('change', '#price_calculation_type', function () {
        var price_calculation_type = $(this).val();

        if (price_calculation_type == 'percentage') {
            $('.percentage-field').removeClass('hide');
            $('.selling_price_group-field').addClass('hide');
        } else {
            $('.percentage-field').addClass('hide');
            $('.selling_price_group-field').removeClass('hide');
        }
    });
</script>
@endsection
