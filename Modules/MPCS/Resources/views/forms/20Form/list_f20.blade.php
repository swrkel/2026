<!-- Main content -->
<style>
    /* IS2286: compact the settings rows so the complete section stays visible. */
    #form_21c_settings_table {
        width: 100% !important;
        table-layout: fixed;
    }

    #form_21c_settings_table th,
    #form_21c_settings_table td {
        padding: 5px 4px !important;
        font-size: 11px;
        line-height: 1.2;
        vertical-align: middle !important;
        white-space: normal !important;
        overflow-wrap: anywhere;
    }

    #form_21c_settings_table th {
        height: 42px;
        text-align: center;
    }

    #form_21c_settings_table th:first-child { width: 8% !important; }
    #form_21c_settings_table th:nth-child(2) { width: 12% !important; }

    .f20-settings-items {
        display: flex;
        flex-wrap: wrap;
        align-content: flex-start;
        gap: 2px;
        max-height: 54px;
        overflow-y: auto;
    }

    .f20-settings-items .badge {
        margin: 0;
        font-size: 10px;
        line-height: 1.2;
    }

    /* IS2295 #3: keep the full settings table within the viewport. */
    #form_21c_settings_table_wrapper,
    #form_21c_settings_table_wrapper .dataTables_scroll,
    #form_21c_settings_table_wrapper .dataTables_scrollHead,
    #form_21c_settings_table_wrapper .dataTables_scrollBody {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: visible !important;
    }

    #form_21c_settings_table .f20-starting-no-head {
        width: 10% !important;
        min-width: 0 !important;
        max-width: 10% !important;
        white-space: normal !important;
        text-align: center;
        line-height: 1.05;
    }

    #form_21c_settings_table .f20-starting-no-head .f20-head-line {
        display: block;
        white-space: nowrap;
    }

    #form_21c_settings_table .f20-starting-no-head .f20-head-subline {
        display: block;
        white-space: nowrap;
        font-size: 10px;
        font-weight: 600;
        margin-top: 2px;
    }

    #form_21c_settings_table td:nth-child(3),
    #form_21c_settings_table td:nth-child(4),
    #form_21c_settings_table td:nth-child(5) {
        width: 10% !important;
        max-width: 10% !important;
        text-align: center;
        white-space: nowrap !important;
    }

    #form_21c_settings_table th:nth-child(6),
    #form_21c_settings_table td:nth-child(6) { width: 20% !important; max-width: 20% !important; }
    #form_21c_settings_table th:nth-child(7),
    #form_21c_settings_table td:nth-child(7) { width: 30% !important; max-width: 30% !important; }

    #form_21c_settings_table td:nth-child(6),
    #form_21c_settings_table td:nth-child(7) {
        overflow: hidden;
        overflow-wrap: anywhere;
    }

</style>
<section class="content">
<div class="row">
        <div class="box-tools pull-right">
            <button type="button" class="btn btn-primary btn-modal" data-href="{{action('\Modules\MPCS\Http\Controllers\F20FormController@get20FormSettings')}}" data-container=".form_16_a_settings_modal">
                <i class="fa fa-plus"></i> Add 20 Form Settings</button>
        </div>
    </div>


<div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="col-md-12">
                <div class="box-body" style="margin-top: 20px;">
                    <div class="row">
                        <div class="col-md-12">
                            
                            <div id="msg"></div>

                            <table id="form_21c_settings_table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                                <colgroup>
                                    <col style="width:8%">
                                    <col style="width:12%">
                                    <col style="width:10%">
                                    <col style="width:10%">
                                    <col style="width:10%">
                                    <col style="width:20%">
                                    <col style="width:30%">
                                </colgroup>
                                <thead>
                                    <tr>
                                        <th>@lang('mpcs::lang.action')</th>
                                        <th>@lang('mpcs::lang.date_and_time')</th>
                                        <th class="f20-starting-no-head"><span class="f20-head-line">Starting No</span><span class="f20-head-subline">(Total Sale)</span></th>
                                        <th class="f20-starting-no-head"><span class="f20-head-line">Starting No</span><span class="f20-head-subline">(Cash Sale)</span></th>
                                        <th class="f20-starting-no-head"><span class="f20-head-line">Starting No</span><span class="f20-head-subline">(Credit Sale)</span></th>
                                        <th>Category</th>
                                        <th>@lang('mpcs::lang.product')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
            @endcomponent
        </div>
    </div>

  
    <div class="modal fade form_16_a_settings_modal" id="form_16_a_settings_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    <div class="modal fade update_form_16_a_settings_modal" id="update_form_16_a_settings_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>


</section>
<!-- /.content -->

<script type="text/javascript">

 $(document).ready(function(){

 form_21c_settings_table = $('#form_21c_settings_table').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    scrollX: false,
    ajax: {
        url: '/mpcs/20formsettings',
        type: 'GET',
        dataSrc: function(json) {
            var newData = [];

            json.data.forEach(function(item) {
                // First row (General details, empty Pump columns)
                newData.push({
                    action: item.action,
                    opening_date: item.opening_date,
                    // starting_number: item.starting_number,
                    total_sale: item.total_sale,
                    cash_sale: item.cash_sale,
                    credit_sale: item.credit_sale,
                    category: item.category,
                    product: item.product,
                });
            });

            return newData;
        }
    },
    columns: [
        { data: 'action', name: 'action', orderable: false, searchable: false, defaultContent: '' },
        { data: 'opening_date', name: 'opening_date' },
        // { data: 'starting_number', name: 'starting_number' },
        { data: 'total_sale', name: 'total_sale' },
        { data: 'cash_sale', name: 'cash_sale' },
        { data: 'credit_sale', name: 'credit_sale' },
        { data: 'category', name: 'category' },
        { data: 'product', name: 'product' },
    ],
    columnDefs: [
        { targets: 0, width: '8%' },
        { targets: 1, width: '12%' },
        { targets: [2, 3, 4], width: '10%' },
        { targets: 5, width: '20%' },
        { targets: 6, width: '30%' }
    ],
    drawCallback: function() {
        this.api().columns.adjust();
    }
    });

    
    });

</script>
