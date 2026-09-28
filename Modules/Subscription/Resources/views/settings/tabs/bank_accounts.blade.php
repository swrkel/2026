 <!-- Main content -->
 <section class="content">
     @component('components.widget', [
         'class' => 'box-primary',
         'title' => __('subscription::lang.bank_accounts'),
     ])
     @endcomponent
     @component('components.widget', ['class' => 'box-primary'])
         @slot('tool')
             <div class="box-tools pull-right">
                 <button class="btn btn-primary add-bank-account"
                     data-href="{{ action('\Modules\Subscription\Http\Controllers\SubscriptionBankAccountController@create') }}">
                     <i class="fa fa-plus"></i> @lang('messages.add')
                 </button>
             </div>
         @endslot

         <table class="table table-bordered" id="subscription_bank_accounts_table"  style="width:100%;">
             <thead>
                 <tr>
                    <th>@lang('messages.action')</th>
                     <th>@lang('subscription::lang.date')</th>
                     <th>@lang('subscription::lang.template_name')</th>
                     <th>@lang('subscription::lang.ac_name')</th>
                     <th>@lang('subscription::lang.ac_no')
                     <th>@lang('subscription::lang.bank')</th>
                     <th>@lang('subscription::lang.branch')</th>
                     <th>@lang('subscription::lang.status')</th>
                     <th>@lang('subscription::lang.created_by')</th>
                 </tr>
             </thead>
         </table>
     @endcomponent
 </section>
