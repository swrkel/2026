<section class="content">
    <div class="row">
        <div class="col-md-12">

            @component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Prefix & Starting Numbers'])
                @slot('tool')
                    <div class="box-tools">
                        <button type="button"
                            class="btn btn-primary btn-modal pull-right"
                            data-href="{{ action('\Modules\Distribution\Http\Controllers\DistributionNumberingPrefixController@create') }}"
                            data-container="#prefixModal" onclick="if(window.openDistributionSettingsModal){return window.openDistributionSettingsModal(this,event);}">
                            <i class="fa fa-plus"></i> @lang('messages.add')
                        </button>
                    </div>
                @endslot

                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="prefix_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Numbering Type</th>
                                <th>Prefix</th>
                                <th>Starting Number</th>
                                <th>Current Number</th>
                                <th>Added By</th>
                                <th class="notexport">@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent

        </div>
    </div>

    {{-- Modal container --}}
    <div class="modal fade" id="prefixModal" tabindex="-1" role="dialog"></div>
</section>
