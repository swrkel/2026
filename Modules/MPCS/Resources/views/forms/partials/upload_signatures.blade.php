<!-- Upload Signatures Section -->
<div class="row">
    <div class="col-md-12">
        <div class="box box-solid">
            <div class="box-body">
                
                <!-- Upload Signature Form -->
                <div class="row">
                    <div class="col-md-12">
                        <h4><i class="fa fa-upload"></i> Add New Signature</h4>
                        <hr>
                        
                        <form id="upload_signature_form" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Date & Time -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Date & Time: *</label>
                                        <input type="text" id="signature_datetime" class="form-control" readonly>
                                    </div>
                                </div>
                                
                                <!-- User Dropdown -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>User: *</label>
                                        <div class="input-group">
                                            <select id="signature_user_id" class="form-control select2" required>
                                                <option value="">Select User</option>
                                            </select>
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default btn-sm" id="add_new_user_btn" title="Add New User" style="height: 34px;">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Designation Dropdown -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Designation: *</label>
                                        <div class="input-group">
                                            <select id="signature_designation_id" class="form-control select2" required>
                                                <option value="">Select Designation</option>
                                            </select>
                                            <span class="input-group-btn">
                                                <button type="button" class="btn btn-default btn-sm" id="add_new_designation_btn" title="Add New Designation" style="height: 34px;">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Upload Signature -->
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Upload Signature: *</label>
                                        <input type="file" id="signature_file" class="form-control" accept=".pdf,.jpeg,.jpg,.png" required>
                                        <small class="text-muted">PDF, JPEG, PNG allowed</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Signature Preview -->
                            <div class="row" id="signature_preview_row" style="display: none;">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label>Signature Preview:</label>
                                        <div id="signature_preview" style="border: 1px solid #ddd; padding: 10px; text-align: center; min-height: 100px;">
                                            <!-- Preview will be shown here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="row">
                                <div class="col-md-12 text-center">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-save"></i> Save Signature
                                    </button>
                                    <button type="button" class="btn btn-default" id="reset_signature_form">
                                        <i class="fa fa-refresh"></i> Reset
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <hr>
                
                <!-- Uploaded Signatures Table -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="signatures_table" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date & Time</th>
                                        <th>User</th>
                                        <th>Designation</th>
                                        <th>Signature</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="add_user_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Add New User</h4>
            </div>
            <div class="modal-body">
                <form id="add_user_form">
                    <div class="form-group">
                        <label>First Name: *</label>
                        <input type="text" id="new_user_first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name: *</label>
                        <input type="text" id="new_user_last_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email: *</label>
                        <input type="email" id="new_user_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username: *</label>
                        <input type="text" id="new_user_username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password: *</label>
                        <input type="password" id="new_user_password" class="form-control" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save_new_user">Save User</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Designation Modal -->
<div class="modal fade" id="add_designation_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Add New Designation</h4>
            </div>
            <div class="modal-body">
                <form id="add_designation_form">
                    <div class="form-group">
                        <label>Designation Name: *</label>
                        <input type="text" id="new_designation_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Description:</label>
                        <textarea id="new_designation_description" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save_new_designation">Save Designation</button>
            </div>
        </div>
    </div>
</div>

<style>
    .signature-preview {
        max-width: 200px;
        max-height: 100px;
        border: 1px solid #ddd;
        padding: 5px;
        background: #f9f9f9;
    }
    .signature-preview img {
        max-width: 100%;
        max-height: 90px;
        object-fit: contain;
    }
    .signature-preview embed {
        width: 180px;
    }

    .input-group .select2-container {
        width: 100% !important;
    }
    .input-group-btn .btn {
        height: 34px;
        padding: 6px 12px;
    }
    .input-group .form-control {
        height: 34px;
    }
    
    .table-responsive {
        width: 100% !important;
        overflow-x: auto;
    }
    #signatures_table {
        width: 100% !important;
        table-layout: fixed;
    }
    #signatures_table th,
    #signatures_table td {
        padding: 8px;
        vertical-align: middle;
    }
    #signatures_table th:nth-child(1),
    #signatures_table td:nth-child(1) {
        width: 50px; /* Serial number column */
    }
    #signatures_table th:nth-child(2),
    #signatures_table td:nth-child(2) {
        width: 150px; /* Date & Time column */
    }
    #signatures_table th:nth-child(3),
    #signatures_table td:nth-child(3) {
        width: 200px; /* User column */
    }
    #signatures_table th:nth-child(4),
    #signatures_table td:nth-child(4) {
        width: 150px; /* Designation column */
    }
    #signatures_table th:nth-child(5),
    #signatures_table td:nth-child(5) {
        width: 220px; /* Signature column */
    }

    /* Ensure Select2 dropdowns are consistent */
.select2-container .select2-selection--single {
    height: 34px !important;
    padding: 4px 0;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 32px !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 24px !important;
}

/* Make sure search box appears in dropdown */
.select2-container--default .select2-search--dropdown .select2-search__field {
    width: 100% !important;
    padding: 6px 12px !important;
    margin: 5px 0 !important;
    border: 1px solid #ccc !important;
    border-radius: 4px !important;
}

.select2-dropdown {
    z-index: 1050 !important;
}
</style>

<script>
$(document).ready(function() {
    // Set current date and time
    var now = new Date();
    $('#signature_datetime').val(now.toLocaleString());
    
    // Initialize Select2 with proper configuration
    function initSelect2() {
        $('.select2').each(function() {
            // Destroy existing Select2 instance if it exists
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            
            // Reinitialize Select2
            $(this).select2({
                width: '100%',
                allowClear: true,
                placeholder: $(this).attr('placeholder') || 'Select option',
                minimumResultsForSearch: 1, // Always show search for more than 1 result
                language: {
                    noResults: function() {
                        return "No results found";
                    },
                    searching: function() {
                        return "Searching...";
                    }
                }
            });
        });
    }
    
    // Load users with proper Select2 reinitialization
    function loadUsers() {
        $.ajax({
            url: '/mpcs/F10/signatures/users',
            type: 'GET',
            success: function(response) {
                var select = $('#signature_user_id');
                var currentValue = select.val();
                
                select.empty().append('<option value="">Select User</option>');
                
                if (response.success && response.data) {
                    $.each(response.data, function(key, user) {
                        select.append('<option value="' + user.id + '">' + user.first_name + ' ' + user.last_name + ' (' + user.username + ')</option>');
                    });
                }
                
                // Reinitialize Select2 for this dropdown
                if (select.hasClass('select2-hidden-accessible')) {
                    select.select2('destroy');
                }
                select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: 'Select User',
                    minimumResultsForSearch: 1
                });
                
                // Restore previous value if it exists in new options
                if (currentValue && select.find('option[value="' + currentValue + '"]').length) {
                    select.val(currentValue).trigger('change');
                } else {
                    select.val('').trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Error loading users:', xhr);
                toastr.error('Error loading users');
            }
        });
    }
    
    // Load designations with proper Select2 reinitialization
    function loadDesignations() {
        $.ajax({
            url: '/mpcs/F10/signatures/designations',
            type: 'GET',
            success: function(response) {
                var select = $('#signature_designation_id');
                var currentValue = select.val();
                
                select.empty().append('<option value="">Select Designation</option>');
                
                if (response.success && response.data) {
                    $.each(response.data, function(key, designation) {
                        select.append('<option value="' + designation.id + '">' + designation.name + '</option>');
                    });
                }
                
                // Reinitialize Select2 for this dropdown
                if (select.hasClass('select2-hidden-accessible')) {
                    select.select2('destroy');
                }
                select.select2({
                    width: '100%',
                    allowClear: true,
                    placeholder: 'Select Designation',
                    minimumResultsForSearch: 1
                });
                
                // Restore previous value if it exists in new options
                if (currentValue && select.find('option[value="' + currentValue + '"]').length) {
                    select.val(currentValue).trigger('change');
                } else {
                    select.val('').trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Error loading designations:', xhr);
                toastr.error('Error loading designations');
            }
        });
    }
    
    // Load signatures table
    loadSignaturesTable();
    
    // User selection change event
    $('#signature_user_id').on('change', function() {
        var userId = $(this).val();
        if (userId) {
            getUserDesignation(userId);
        } else {
            // Clear designation dropdown when user is cleared
            var designationSelect = $('#signature_designation_id');
            designationSelect.val('').trigger('change');
        }
    });
    
    // File preview
    $('#signature_file').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = $('#signature_preview');
                var fileType = file.type;
                
                if (fileType === 'application/pdf') {
                    preview.html('<embed src="' + e.target.result + '" type="application/pdf" style="max-width: 100%; max-height: 200px;" />');
                } else if (fileType.startsWith('image/')) {
                    preview.html('<img src="' + e.target.result + '" alt="Signature Preview" class="signature-preview" style="max-width: 200px; max-height: 100px;" />');
                }
                
                $('#signature_preview_row').show();
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Form submission
    $('#upload_signature_form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData();
        formData.append('user_id', $('#signature_user_id').val());
        formData.append('designation_id', $('#signature_designation_id').val());
        formData.append('signature_file', $('#signature_file')[0].files[0]);
        formData.append('datetime', $('#signature_datetime').val());
        
        $.ajax({
            url: '/mpcs/F10/signatures/upload',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Signature uploaded successfully!');
                    resetSignatureForm();
                    loadSignaturesTable();
                } else {
                    toastr.error(response.message || 'Error uploading signature');
                }
            },
            error: function(xhr) {
                toastr.error('Error uploading signature. Please try again.');
            }
        });
    });
    
    // Reset form
    $('#reset_signature_form').on('click', function() {
        resetSignatureForm();
    });
    
    // Add user modal
    $('#add_new_user_btn').on('click', function() {
        $('#add_user_modal').modal('show');
    });
    
    $('#save_new_user').on('click', function() {
        var formData = {
            first_name: $('#new_user_first_name').val(),
            last_name: $('#new_user_last_name').val(),
            email: $('#new_user_email').val(),
            username: $('#new_user_username').val(),
            password: $('#new_user_password').val()
        };
        
        $.ajax({
            url: '/mpcs/F10/signatures/add-user',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success('User added successfully!');
                    $('#add_user_modal').modal('hide');
                    $('#add_user_form')[0].reset();
                    loadUsers();
                } else {
                    toastr.error(response.message || 'Error adding user');
                }
            },
            error: function(xhr) {
                toastr.error('Error adding user. Please try again.');
            }
        });
    });
    
    // Add designation modal
    $('#add_new_designation_btn').on('click', function() {
        $('#add_designation_modal').modal('show');
    });
    
    $('#save_new_designation').on('click', function() {
        var formData = {
            name: $('#new_designation_name').val(),
            description: $('#new_designation_description').val()
        };
        
        $.ajax({
            url: '/mpcs/F10/signatures/add-designation',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success('Designation added successfully!');
                    $('#add_designation_modal').modal('hide');
                    $('#add_designation_form')[0].reset();
                    loadDesignations();
                } else {
                    toastr.error(response.message || 'Error adding designation');
                }
            },
            error: function(xhr) {
                toastr.error('Error adding designation. Please try again.');
            }
        });
    });
    
    // Initial load of users and designations
    loadUsers();
    loadDesignations();
});

function loadSignaturesTable() {
    // Destroy existing DataTable if it exists
    if ($.fn.DataTable.isDataTable('#signatures_table')) {
        $('#signatures_table').DataTable().destroy();
    }
    
    var table = $('#signatures_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/mpcs/F10/signatures/list',
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.log('Ajax error:', xhr, error, thrown);
                console.log('Response text:', xhr.responseText);
                var errorMessage = 'Error loading data. Please refresh the page.';
                
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    errorMessage = xhr.responseJSON.error;
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error occurred. Please check logs.';
                } else if (xhr.status === 404) {
                    errorMessage = 'Endpoint not found.';
                }
                
                $('#signatures_table tbody').html('<tr><td colspan="5" class="text-center text-danger">' + errorMessage + '</td></tr>');
            }
        },
        columns: [
            { 
                data: 'DT_RowIndex', 
                name: 'DT_RowIndex', 
                orderable: false, 
                searchable: false,
                render: function(data, type, row, meta) {
                    return meta.row + meta.settings._iDisplayStart + 1;
                }
            },
            { data: 'datetime', name: 'datetime' },
            { data: 'user_name', name: 'user_name' },
            { data: 'designation_name', name: 'designation_name' },
            { 
                data: 'signature_preview', 
                name: 'signature_preview',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (data) {
                        if (row.signature_type === 'pdf') {
                            return '<embed src="' + data + '" type="application/pdf" class="signature-preview" style="max-width: 200px; max-height: 100px;" />';
                        } else {
                            return '<img src="' + data + '" alt="Signature" class="signature-preview" style="max-width: 200px; max-height: 100px;" />';
                        }
                    }
                    return 'No signature';
                }
            }
        ]
    });
}

function resetSignatureForm() {
    $('#upload_signature_form')[0].reset();
    $('#signature_preview').empty();
    $('#signature_preview_row').hide();
    
    // Reset date and time
    var now = new Date();
    $('#signature_datetime').val(now.toLocaleString());
    
    // Reset dropdowns
    $('#signature_user_id').val('').trigger('change');
    $('#signature_designation_id').val('').trigger('change');
}

function getUserDesignation(userId) {
    $.ajax({
        url: '/mpcs/F10/signatures/user-designation',
        type: 'GET',
        data: { user_id: userId },
        success: function(response) {
            if (response.success && response.data) {
                // Set the designation dropdown
                var designationSelect = $('#signature_designation_id');
                if (designationSelect.find('option[value="' + response.data.id + '"]').length) {
                    designationSelect.val(response.data.id).trigger('change');
                } else {
                    // If designation not found in dropdown, reload designations
                    loadDesignations();
                    // Set after a short delay to allow loading
                    setTimeout(function() {
                        $('#signature_designation_id').val(response.data.id).trigger('change');
                    }, 500);
                }
            } else {
                // Clear designation if no designation found for user
                $('#signature_designation_id').val('').trigger('change');
            }
        },
        error: function(xhr) {
            console.error('Error fetching user designation:', xhr);
            // Clear designation on error
            $('#signature_designation_id').val('').trigger('change');
        }
    });
}
</script>
