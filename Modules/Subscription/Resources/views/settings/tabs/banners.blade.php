 <!-- Main content -->
 <section class="content">
     @component('components.widget', [
         'class' => 'box-primary',
         'title' => __('subscription::lang.banners'),
     ])
     @endcomponent
     @component('components.widget', ['class' => 'box-primary'])
         @slot('tool')
             <div class="box-tools pull-right">
                 <button class="btn btn-primary add-banner"
                     data-href="{{ action('\Modules\Subscription\Http\Controllers\SubscriptionBannerController@create') }}">
                     <i class="fa fa-plus"></i> @lang('messages.add')
                 </button>
             </div>
         @endslot

         <table class="table table-bordered" id="subscription_banners_table"  style="width:100%;">
             <thead>
                <tr>
                    <th>@lang('messages.action')</th>
                    <th>@lang('subscription::lang.logo')</th>
                    <th>@lang('subscription::lang.width')</th>
                    <th>@lang('subscription::lang.height')</th>
                    <th>@lang('subscription::lang.date')</th>
                    <th>@lang('subscription::lang.created_by')</th>
                </tr>
            </thead>
         </table>
     @endcomponent
 </section>
