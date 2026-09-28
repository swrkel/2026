 <!-- Main content -->
 <section class="content">
     @component('components.widget', [
         'class' => 'box-primary',
         'title' => __('subscription::lang.payment_terms'),
     ])
     @endcomponent
     @component('components.widget', ['class' => 'box-primary'])
         @slot('tool')
             <div class="box-tools pull-right">
                 <button class="btn btn-primary add-payment-term"
                     data-href="{{ action('\Modules\Subscription\Http\Controllers\SubscriptionPaymentTermController@create') }}">
                     <i class="fa fa-plus"></i> @lang('messages.add')
                 </button>
             </div>
         @endslot

         <table class="table table-bordered" id="subscription_payment_terms_table" style="width:100%;">
             <thead>
                 <tr>
                     <th>@lang('messages.action')</th>
                     <th>@lang('subscription::lang.date')</th>
                     <th>@lang('subscription::lang.payment_term_name')</th>
                     <th>@lang('subscription::lang.payment_terms')</th>
                     <th>@lang('subscription::lang.status')</th>
                     <th>@lang('subscription::lang.created_by')</th>
                 </tr>
             </thead>
         </table>
     @endcomponent
 </section>
