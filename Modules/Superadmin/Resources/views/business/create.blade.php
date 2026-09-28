@extends('layouts.app')
@section('title', __('superadmin::lang.superadmin') . ' | Business')

@section('content')
    <!-- Main content -->
    <section class="content">

        <div class="box">
            <div class="box-header">
                <h3 class="box-title">@lang('superadmin::lang.add_new_business') <small>(@lang('superadmin::lang.add_business_help'))</small></h3>
            </div>

            <div class="box-body">
                <div class="col-md-12">
                    {!! Form::open([
                        'url' => action('\Modules\Superadmin\Http\Controllers\BusinessController@store'),
                        'method' => 'post',
                        'id' => 'business_register_form',
                        'files' => true,
                    ]) !!}
                    @include('business.partials.register_form')


                    {!! Form::close() !!}
                </div>
            </div>
        </div>

        <div class="modal fade brands_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

    </section>
    <!-- /.content -->
@endsection


@section('javascript')
    <script type="text/javascript">
        $(document).ready(function() {
            // Show validation errors in modal
            @if ($errors->has('username') || $errors->has('email') || $errors->has('mobile'))
                var errorMessage = '';
                @if ($errors->has('username'))
                    errorMessage = '{{ $errors->first('username') }}';
                @elseif ($errors->has('email'))
                    errorMessage = '{{ $errors->first('email') }}';
                @elseif ($errors->has('mobile'))
                    errorMessage = '{{ $errors->first('mobile') }}';
                @endif

                swal({
                    title: "{{ __('lang_v1.validation_error') }}",
                    text: errorMessage,
                    icon: "warning",
                    buttons: {
                        confirm: {
                            text: "OK",
                            value: true,
                            visible: true,
                            className: "btn btn-primary"
                        }
                    }
                });
            @endif

            $('.select2_register').select2({
                width: '100%'
            });
            $("form#business_register_form").validate({
                errorPlacement: function(error, element) {
                    if (element.parent('.input-group').length) {
                        error.insertAfter(element.parent());
                    } else {
                        error.insertAfter(element);
                    }
                },
                rules: {
                    name: "required",
                    email: {
                        email: true
                    },
                    password: {
                        required: true,
                        minlength: 5
                    },
                    confirm_password: {
                        equalTo: "#b_password"
                    },
                    username: {
                        required: true,
                        minlength: 4
                    }
                },
                messages: {
                    name: LANG.specify_business_name,
                    password: {
                        minlength: LANG.password_min_length,
                    },
                    confirm_password: {
                        equalTo: LANG.password_mismatch
                    }
                }
            });

            $("#business_logo").fileinput({
                'showUpload': false,
                'showPreview': false,
                'browseLabel': LANG.file_browse_label,
                'removeLabel': LANG.remove
            });

            function toggleMyAutoMode() {
                var isMyAuto = $('#is_my_auto').is(':checked');

                $('.js-business-name-label').text(isMyAuto ? 'My Auto Number:' : '{{ __('business.business_name') }}:');
                $('.js-mobile-label').text(isMyAuto ? 'Mobile No:*' : '{{ __('lang_v1.business_telephone') }}:*');
                $('.js-alternate-label').text(isMyAuto ? 'Other Mobile Nos:' : '{{ __('business.alternate_number') }}:');
                $('.js-alternate-help').toggleClass('my-auto-hidden', !isMyAuto);

                $('.js-currency-wrap, .js-website-wrap, .js-show-for-customers-wrap, .js-business-categories-wrap, .js-business-type-wrap, .js-email-wrap')
                    .toggleClass('my-auto-hidden', isMyAuto);

                $('[name="currency_id"], [name="website"], [name="show_for_customers"], [name="business_categories[]"], [name="business_type_id"], #b_email')
                    .prop('disabled', isMyAuto);

                if (isMyAuto) {
                    $('#show_for_customers').prop('checked', false);
                    $('.business_categories_div').addClass('hide');
                    $('#b_email').val('');
                }
            }

            $('#is_my_auto').on('change', toggleMyAutoMode);
            toggleMyAutoMode();
        });

        $('#show_for_customers').on('ifChecked', function(event) {
            $('.business_categories_div').removeClass('hide');
        });
        $('#show_for_customers').on('ifUnChecked', function(event) {
            $('.business_categories_div').addClass('hide');
        });

        $('#business_categories').select2();
    </script>
@endsection
