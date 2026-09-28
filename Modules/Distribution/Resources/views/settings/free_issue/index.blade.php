<!-- Main content -->
<section class="content">

<div class="row">
    <div class="col-md-12">
        @component('distribution::components.filters', ['title' => __('report.filters')])

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('filter_product', 'Product') !!}
                {!! Form::select(
                    'filter_product[]',
                    $products,
                    null,
                    ['class'=>'form-control select2','multiple input-sm','id'=>'filter_product', 'placeholder' => 'All',]
                ) !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('filter_category', 'Categories') !!}
                {!! Form::select(
                    'filter_category[]',
                    $categories,
                    null,
                    ['class'=>'form-control select2 input-sm','multiple','id'=>'filter_category', 'placeholder' => 'All',]
                ) !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('filter_subcategory', 'Sub Categories') !!}
                {!! Form::select(
                    'filter_subcategory[]',
                    [],
                    null,
                    ['class'=>'form-control select2 input-sm','multiple','id'=>'filter_subcategory', 'placeholder' => 'All',]
                ) !!}
            </div>
        </div>

        <div class="col-md-3">
            <div class="form-group">
                <label>&nbsp;</label>
                <button class="btn btn-primary btn-block" id="applyFilters">
                    Apply Filters
                </button>
            </div>
        </div>

        @endcomponent
    </div>
</div>

@component('distribution::components.widget', ['class' => 'box-primary', 'title' => 'Free Issue'])
@slot('tool')
<div class="box-tools">
    @can('add_free_issues')
    <button type="button" class="btn btn-primary btn-modal pull-right"
        data-href="{{ action('\Modules\Distribution\Http\Controllers\DistributionFreeIssueController@create') }}"
        data-container=".view_modal" onclick="if(window.openDistributionSettingsModal){return window.openDistributionSettingsModal(this,event);}">
        <i class="fa fa-plus"></i> @lang('messages.add')
    </button>
    @endcan
</div>
@endslot

<div class="table-responsive">
    <table class="table table-bordered table-striped" id="free_issue_table" style="width:100%;">

        <thead>
   <tr>
      <th>Action</th>
      <th>Date & Time</th>
      <th>Free Issue Form No</th>
      <th>Product</th>
      <th>Category</th>
      <th>Sub Category</th>
      <th>Unit</th>
      <th>Qty From</th>
      <th>Qty Till</th>
      <th>Free Qty</th>
      <th>Date Since</th>
      <th>Date Till</th>
   </tr>
</thead>

    </table>
</div>
@endcomponent

</section>

<script type="text/javascript">
    $(document).ready(function(){
        // DataTable
        window.free_issue_table = $('#free_issue_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/distribution/free-issues',
                data: function(d) {
                    d.products = $('#filter_product').val();
                    d.categories = $('#filter_category').val();
                    d.subcategories = $('#filter_subcategory').val();
                }
            },
            columns: [
                { data: 'action', name: 'action', searchable: false, orderable: false },
                { data: 'date_time', name: 'date_time' },
                { data: 'form_no', name: 'form_no' },
                { data: 'product_name_text', name: 'product_name_text', searchable: false, orderable: false },
                { data: 'product_category_text', name: 'product_category_text', searchable: false, orderable: false },
                { data: 'product_subcategory_text', name: 'product_subcategory_text', searchable: false, orderable: false },
                { data: 'unit_name', name: 'unit_name', searchable: false, orderable: false },
                { data: 'qty_from', name: 'qty_from' },
                { data: 'qty_till', name: 'qty_till' },
                { data: 'free_qty', name: 'free_qty' },
                { data: 'date_since', name: 'date_since' },
                { data: 'date_till', name: 'date_till' },
            ]
        });

        // Handle Edit button - Load modal for edit
        $(document).on('click', '.btn-modal', function(e) {
            e.preventDefault();
            
            var container = $(this).data('container');
            var url = $(this).data('href');
            
            console.log('Edit button clicked - URL:', url);
            
            if (url && container) {
                // Show loading state
                $(container).html('<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div></div></div>').modal('show');
                
                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(response) {
                        console.log('Modal loaded successfully');
                        $(container).html(response).modal('show');
                        
                        // Initialize select2 after modal loads
                        setTimeout(function() {
                            $(container).find('.select2').each(function() {
                                $(this).select2({
                                    width: '100%',
                                    dropdownParent: $(container).find('.modal-content')
                                });
                            });
                        }, 100);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error loading modal:', error);
                        console.log('Status:', status);
                        console.log('Response:', xhr.responseText);
                        
                        $(container).modal('hide');
                        toastr.error('Error loading edit form. Please check console for details.');
                    }
                });
            } else {
                console.error('Missing data-href or data-container');
            }
        });

// Handle View Logs button
$(document).on('click', '.view-logs', function() {
    var issueId = $(this).data('id');
    var formNo = $(this).closest('tr').find('td:eq(2)').text(); // Get form number from table
    
    console.log('Loading logs for issue ID:', issueId);
    
    // Create a modal for viewing logs
    var modalHtml = `
        <div class="modal fade" id="logsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">
                            Free Issue Logs 
                            <small>Form No: ${formNo}</small>
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p>Loading logs...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#logsModal').remove();
    
    // Add modal to body
    $('body').append(modalHtml);
    
    // Show modal
    $('#logsModal').modal('show');
    
    // Load logs via AJAX
    $.ajax({
        url: '/distribution/free-issues/logs/' + issueId,
        type: 'GET',
        success: function(logs) {
            console.log('Logs received:', logs);
            
            if (logs && logs.length > 0) {
                var logsHtml = `
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                `;
                
                $.each(logs, function(index, log) {
                    var userName = 'System';
                    if (log.user) {
                        userName = log.user.username;
                        if (log.user.first_name || log.user.last_name) {
                            userName = (log.user.first_name || '') + ' ' + (log.user.last_name || '');
                        }
                    }
                    
                    var actionClass = '';
                    if (log.action === 'created') actionClass = 'label label-success';
                    else if (log.action === 'updated') actionClass = 'label label-info';
                    else if (log.action === 'enabled') actionClass = 'label label-primary';
                    else if (log.action === 'disabled') actionClass = 'label label-danger';
                    else actionClass = 'label label-default';
                    
                    logsHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${userName}</strong></td>
                            <td><span class="${actionClass}">${log.action.toUpperCase()}</span></td>
                            <td>${new Date(log.created_at).toLocaleString()}</td>
                        </tr>
                    `;
                });
                
                logsHtml += `
                            </tbody>
                        </table>
                    </div>
                `;
                
                $('#logsModal .modal-body').html(logsHtml);
            } else {
                $('#logsModal .modal-body').html(`
                    <div class="alert alert-info text-center">
                        <i class="fa fa-info-circle fa-2x"></i>
                        <p>No logs found for this free issue.</p>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading logs:', error);
            $('#logsModal .modal-body').html(`
                <div class="alert alert-danger text-center">
                    <i class="fa fa-exclamation-triangle fa-2x"></i>
                    <p>Error loading logs. Please try again.</p>
                    <small>${xhr.responseText}</small>
                </div>
            `);
        }
    });
});


// Handle User Details button
$(document).on('click', '.user-logs', function() {
    var issueId = $(this).data('id');
    var $row = $(this).closest('tr');
    var formNo = $row.find('td:eq(2)').text();
    
    console.log('Loading user details for issue ID:', issueId);
    
    // Create a modal for user details
    var modalHtml = `
        <div class="modal fade" id="userDetailsModal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">
                            Free Issue Details 
                            <small>Form No: ${formNo}</small>
                        </h4>
                    </div>
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-2x"></i>
                            <p>Loading details...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#userDetailsModal').remove();
    
    // Add modal to body
    $('body').append(modalHtml);
    
    // Show modal
    $('#userDetailsModal').modal('show');
    
    // Load user details - you need to create this endpoint
    $.ajax({
        url: '/distribution/free-issues/user-details/' + issueId,
        type: 'GET',
        success: function(data) {
            var html = `
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 40%">Form Number:</th>
                            <td>${data.form_no || '-'}</td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td>${data.created_by_user ? data.created_by_user.username : 'Unknown'}</td>
                        </tr>
                        <tr>
                            <th>Created At:</th>
                            <td>${data.created_at ? new Date(data.created_at).toLocaleString() : '-'}</td>
                        </tr>
                        <tr>
                            <th>Last Updated By:</th>
                            <td>${data.updated_by_user ? data.updated_by_user.username : 'Not updated'}</td>
                        </tr>
                        <tr>
                            <th>Last Updated At:</th>
                            <td>${data.updated_at ? new Date(data.updated_at).toLocaleString() : '-'}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>${data.status == 1 ? '<span class="label label-success">Active</span>' : '<span class="label label-danger">Inactive</span>'}</td>
                        </tr>
                    </table>
                </div>
            `;
            $('#userDetailsModal .modal-body').html(html);
        },
        error: function() {
            $('#userDetailsModal .modal-body').html(`
                <div class="alert alert-warning text-center">
                    <i class="fa fa-exclamation-triangle"></i>
                    <p>User details endpoint not implemented yet.</p>
                    <hr>
                    <strong>Free Issue ID:</strong> ${issueId}<br>
                    <strong>Form No:</strong> ${formNo}
                </div>
            `);
        }
    });
});

        // Enable / Disable status
        $(document).on('change', '#free_issue_table .toggle-status', function() {
            var issue_id = $(this).data('id');
            var is_checked = $(this).prop('checked');
            $.ajax({
                url: '/distribution/free-issues/toggle/' + issue_id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(result) {
                    if (result.success == true) {
                        toastr.success("Status Updated");
                    } else {
                        toastr.error("Failed to update status");
                    }
                },
                error: function() {
                    toastr.error("Error updating status");
                }
            });
        });
        
        // Apply filters button
        $('#applyFilters').on('click', function() {
            window.free_issue_table.ajax.reload();
        });
        
        // Category change to load subcategories for filters
        $('#filter_category').on('change', function() {
            var categoryIds = $(this).val();
            var subcategorySelect = $('#filter_subcategory');
            
            subcategorySelect.empty();
            subcategorySelect.append('<option value="">All</option>');
            
            if (categoryIds && categoryIds.length > 0) {
                $.ajax({
                    url: '/distribution/subcategories/' + categoryIds.join(','),
                    type: 'GET',
                    success: function(data) {
                        $.each(data, function(id, name) {
                            subcategorySelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                        subcategorySelect.trigger('change');
                    }
                });
            }
        });
        
        // Initialize select2 on filter elements
        $('#filter_product, #filter_category, #filter_subcategory').select2({
            width: '100%'
        });
        
        // Make sure the modal container exists
        if ($('.view_modal').length === 0) {
            $('body').append('<div class="modal fade view_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"><div class="modal-dialog modal-lg" style="width: 80%;" role="document"><div class="modal-content"></div></div></div>');
        }
    });
</script>
