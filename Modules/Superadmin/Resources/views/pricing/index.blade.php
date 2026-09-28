@extends('layouts.auth')
@section('title', __('superadmin::lang.pricing'))

@section('content')
<style>
    .modal {
        text-align: center;
    }
</style>
<div class="container">
    @include('superadmin::layouts.partials.currency')
    @include('layouts.partials.logo')
    <div class="row">
        <div class="box">
            <div class="box-header">
                <h3 class="box-title text-center">@lang('superadmin::lang.packages')</h3>
            </div>

            <div class="box-body">
                @include('superadmin::subscription.partials.packages', ['action_type' => auth()->check() ? 'subscribe' : 'register'])
            </div>
        </div>
    </div>
</div>
<div class="modal fade package_veriables_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal" tabindex="-1" role="dialog" id="subscription_success_modal">
    <div class="modal-dialog" role="document" style="width: 40% !important;">
        <div class="modal-content">
            <div class="modal-body text-center">
                <i class="fa fa-check fa-lg"
                    style="font-size: 50px; margin-top: 20px; border: 1px solid #4BB543; color: #4BB543; padding:15px 10px 15px 10px; border-radius: 50%;"></i>
                <h2>{!!session('subscription.title')!!}</h2>
                <div class="clearfix"></div>
                <div class="col-md-12">
                    {!!session('subscription.msg')!!}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        @if (session('subscription'))
            $('#subscription_success_modal').modal('show');
        @endif
        $('#change_lang').change( function(){
            window.location = "{{ route('pricing') }}?lang=" + $(this).val();
        });
    })

    $('.register_form_modal').click(function(){
        let this_package_id = $(this).attr('id');
        let is_visitor_pacakge = $(this).data('is_visitor_pacakge');
        console.log(is_visitor_pacakge);
        if(is_visitor_pacakge){
            $('.not_visitor_register_field').addClass('hide');
        }else{
            $('.not_visitor_register_field').removeClass('hide');
        }
        $('.package_id').val(this_package_id);
    })
</script>
@endsection