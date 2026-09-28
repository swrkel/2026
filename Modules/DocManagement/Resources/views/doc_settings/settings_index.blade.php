@extends('layouts.app')
@section('title', 'Doc Management Settings')

@section('css')

    <?php
    
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'documentcategory';
    
    switch ($tab) {
        case 'department':
            $activeTab = 'department';
            break;
        case 'designation':
            $activeTab = 'designation';
            break;
        case 'documenttype':
            $activeTab = 'documenttype';
            break;
        case 'purpose':
            $activeTab = 'purpose';
            break;
        case 'forwardedwith':
            $activeTab = 'forwardedwith';
            break;
        case 'docstatus':
            $activeTab = 'docstatus';
            break;
        case 'mandatorysignatures':
            $activeTab = 'mandatorysignatures';
            break;
        case 'uploadsignature':
            $activeTab = 'uploadsignature';
            break;
        case 'referredto':
            $activeTab = 'referredto';
            break;
        case 'uploadlogo':
            $activeTab = 'uploadlogo';
            break;
        default:
            $activeTab = 'documentcategory';
    }
    ?>
    <style>
        #airport_table>tbody>tr>td {
            vertical-align: middle;
        }
    </style>
@endsection

@section('content')

    <div class="page-title-area">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <div class="breadcrumbs-area clearfix">
                    <h5 class="page-title pull-left">Doc Management Settings</h5>
                    <ul class="breadcrumbs pull-left" style="margin-top: 15px">
                        <li><a href="{{ action('\Modules\Airline\Http\Controllers\AirlineTicketingController@index') }}">Doc
                                Management </a></li>
                        <li><span>Settings</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content main-content-inner">
        <div class="settlement_tabs">
            <ul class="nav nav-tabs">
                <li class="<?php echo $activeTab === 'documentcategory' ? 'active' : ''; ?>">
                    <a href="#documentcategory" data-toggle="tab">
                        <strong>Document Category </strong>
                    </a>
                </li>

                <li class="<?php echo $activeTab === 'department' ? 'active' : ''; ?>">
                    <a href="#department" data-toggle="tab">
                        <strong>Department</strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'designation' ? 'active' : ''; ?>">
                    <a href="#designation" data-toggle="tab">
                        <strong>Designation </strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'documenttype' ? 'active' : ''; ?>">
                    <a href="#documenttype" data-toggle="tab">
                        <strong>Document Type </strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'purpose' ? 'active' : ''; ?>">
                    <a href="#purpose" data-toggle="tab">
                        <strong>Purpose </strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'forwardedwith' ? 'active' : ''; ?>">
                    <a href="#forwardedwith" data-toggle="tab">
                        <strong>Forwarded with </strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'docstatus' ? 'active' : ''; ?>">
                    <a href="#docstatus" data-toggle="tab">
                        <strong>Doc Status</strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'mandatorysignatures' ? 'active' : ''; ?>">
                    <a href="#mandatorysignatures" data-toggle="tab">
                        <strong>Mandatory Signatures </strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'uploadsignature' ? 'active' : ''; ?>">
                    <a href="#uploadsignature" data-toggle="tab">
                        <strong>Upload Signature </strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'referredto' ? 'active' : ''; ?>">
                    <a href="#referredto" data-toggle="tab">
                        <strong>Referred to</strong>
                    </a>
                </li>
                <li class="<?php echo $activeTab === 'uploadlogo' ? 'active' : ''; ?>">
                    <a href="#uploadlogo" data-toggle="tab">
                        <strong>Upload Logo</strong>
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane <?php echo $activeTab === 'documentcategory' ? 'active' : ''; ?>" id="documentcategory">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.documentcategory')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'department' ? 'active' : ''; ?>" id="department">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.department')
                        </div>
                    </div>
                </div>


                <div class="tab-pane <?php echo $activeTab === 'designation' ? 'active' : ''; ?>" id="designation">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.designation')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'documenttype' ? 'active' : ''; ?>" id="documenttype">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.documenttype')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'purpose' ? 'active' : ''; ?>" id="purpose">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.purpose')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'forwardedwith' ? 'active' : ''; ?>" id="forwardedwith">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.forwardedwith')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'docstatus' ? 'active' : ''; ?>" id="docstatus">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.docstatus')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'mandatorysignatures' ? 'active' : ''; ?>" id="mandatorysignatures">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.mandatorysignatures')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'uploadsignature' ? 'active' : ''; ?>" id="uploadsignature">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.uploadsignature')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'referredto' ? 'active' : ''; ?>" id="referredto">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.referredto')
                        </div>
                    </div>
                </div>
                <div class="tab-pane <?php echo $activeTab === 'uploadlogo' ? 'active' : ''; ?>" id="uploadlogo">
                    <div class="row">
                        <div class="col-md-12">
                            @include('docmanagement::doc_settings.partials.uploadlogo')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
    <style>
        .nav-tabs-custom>.nav-tabs>li.active a {
            color: #3c8dbc;
        }

        .nav-tabs-custom>.nav-tabs>li.active a:hover {
            color: #3c8dbc;
        }
    </style>
@endsection

@section('javascript')
    <script>
        $(document).ready(function () {
            function loadDepartments() {
                $.get('/DocManagement/document_department_gets', function(data) {
                    let dropdown = $('#designation_department_id');
                    dropdown.empty().append('<option value="">Select Department</option>');

                    data.forEach(function(item) {
                        dropdown.append(`<option value="${item.department}">${item.department}</option>`);
                    });
                });
            }

            loadDepartments();

            // function() {
                /**
                 * ==========================================
                 * DEPARTMENT TABLE
                 * ==========================================
                 */
                let departmentTable = $('#department_table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_department_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [
                        {
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        },
                        {
                            data: 'department',
                            defaultContent: ''
                        },
                        {
                            data: 'description',
                            defaultContent: ''
                        },
                        {
                            data: 'added_by', // ✅ NEW COLUMN
                            defaultContent: ''
                        }
                    ],
                    paging: true,
                    searching: true,
                    pageLength: 10,
                    lengthMenu: [
                        [5, 10, 25, -1],
                        [5, 10, 25, "All"]
                    ],
                    responsive: true,
                    autoWidth: false
                });


                /**
                 * ==========================================
                 * SAVE DEPARTMENT (FIXED)
                 * ==========================================
                 */
                $('#save_department').on('click', function() {

                    let $button = $(this);
                    let department = $.trim($('#department_name').val());
                    let description = $.trim($('#department_description').val());

                    if (!department) {
                        toastr.error('Department is required');
                        return;
                    }

                    console.log('Saving department:', department, description);

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_department',
                        method: 'POST', //  FIXED
                        data: {
                            department: department,
                            description: description,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            console.log('Save response:', response);

                            if (response.success) {
                                $('#department_name').val('');
                                $('#department_description').val('');

                                toastr.success(response.msg || 'Department saved successfully');

                                departmentTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'Department already exists');
                            }
                        },
                        error: function() {
                            toastr.error('Failed to save Department');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });


                /**
                 * ==========================================
                 * DESIGNATION TABLE (FIXED )
                 * ==========================================
                 */
                let designationTable = $('#designation_table').DataTable({
                    processing: true,
                    serverSide: false,
                    destroy: true,
                    ajax: {
                        url: '/DocManagement/document_designation_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [
                        {
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        },
                        {
                            data: 'department'
                        },
                        {
                            data: 'designation'
                        },
                        {
                            data: 'description',
                            defaultContent: ''
                        },
                        {
                            data: 'user'
                        }
                    ],
                    paging: true,
                    searching: true,
                    pageLength: 10,
                    lengthMenu: [
                        [5, 10, 25, -1],
                        [5, 10, 25, "All"]
                    ],
                    responsive: true,
                    autoWidth: false
                });

                $('#save_designation').on('click', function() {

                    let $btn = $(this);

                    let department_id = $('#designation_department_id').val();
                    let designation = $.trim($('#designation_name').val());
                    let description = $.trim($('#designation_description').val());

                    if (!department_id) {
                        toastr.error('Department is required');
                        return;
                    }

                    if (!designation) {
                        toastr.error('Designation is required');
                        return;
                    }

                    $btn.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_designation',
                        method: 'GET',
                        data: {
                            department_id: department_id,
                            designation_name: designation,
                            description: description
                        },
                        success: function(res) {

                            if (res.success) {
                                toastr.success(res.msg || 'Saved successfully');

                                $('#designation_name').val('');
                                $('#designation_description').val('');
                                $('#designation_department_id').val('').trigger('change');

                                designationTable.ajax.reload(null, false);
                            } else {
                                toastr.error(res.msg || 'Already exists');
                            }
                        },
                        error: function() {
                            toastr.error('Failed to save designation');
                        },
                        complete: function() {
                            $btn.prop('disabled', false);
                        }
                    });
                });

                // ======================
                // DOC STATUS TABLE
                // ======================
                var docStatusTable = $('#doc_status_table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_status_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [{
                            data: 'date_time',
                            defaultContent: ''
                        },
                        {
                            data: 'status',
                            defaultContent: ''
                        },
                        {
                            data: 'user',
                            defaultContent: ''
                        }
                    ],
                    responsive: true,
                    autoWidth: false
                });

                // ======================
                // SAVE DOC STATUS
                // ======================
                $('#save_doc_status').on('click', function() {

                    var $button = $(this);
                    var statusName = $.trim($('#doc_status_name').val());

                    if (!statusName) {
                        toastr.error('Status is required');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_doc_status',
                        method: 'GET', // ⚠️ should be POST ideally
                        data: {
                            status_name: statusName
                        },
                        success: function(response) {

                            if (response.success) {
                                $('#doc_status_name').val('');
                                toastr.success(response.msg || 'Doc Status saved successfully');

                                // reload table without resetting pagination
                                docStatusTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'Failed to save Doc Status');
                            }
                        },
                        error: function() {
                            toastr.error('Failed to save Doc Status');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });

                // ======================
                // DOCUMENT CATEGORY TABLE
                // ======================
                var documentCategoryTable = $('#document_category_tables').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_category_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [{
                            data: null,
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: 'document_category',
                            defaultContent: ''
                        },
                        {
                            data: 'user',
                            defaultContent: ''
                        },
                        {
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        }
                    ],
                    paging: true, // pagination enabled
                    searching: true,
                    ordering: false,
                    info: true,
                    pageLength: 10,
                    lengthMenu: [
                        [5, 10, 25, -1],
                        [5, 10, 25, "All"]
                    ],
                    responsive: true,
                    autoWidth: false
                });

                // ======================
                // SAVE DOCUMENT CATEGORY
                // ======================
                $('#save_category').on('click', function() {

                    var $button = $(this);
                    var categoryType = $.trim($('#document_category').val());

                    if (!categoryType) {
                        toastr.error('Document Category is required');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_category_type',
                        method: 'GET', // ⚠️ should be POST ideally
                        data: {
                            categoryType: categoryType
                        },
                        success: function(response) {

                            if (response.success) {
                                $('#document_category').val('');
                                toastr.success(response.msg ||
                                    'Document Category saved successfully');

                                // reload table
                                documentCategoryTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'Document Category already exists');
                            }
                        },
                        error: function() {
                            toastr.error('Failed to save Document Category');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });

                // ======================
                // DOCUMENT TYPE TABLE
                // ======================
                let documentTypeTable = $('#document_type_tables').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_type_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [{
                            data: null,
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: 'type'
                        },
                        {
                            data: 'added_by'
                        },
                        {
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        }
                    ],
                    paging: true,
                    searching: true,
                    pageLength: 10,
                    lengthMenu: [
                        [5, 10, 25, -1],
                        [5, 10, 25, "All"]
                    ],
                    responsive: true,
                    autoWidth: false
                });

                // ======================
                // SAVE DOCUMENT TYPE
                // ======================
                $('#save_type').on('click', function() {

                    let $button = $(this);
                    let documentType = $.trim($('#document_type').val());

                    if (!documentType) {
                        toastr.error('Document Type is required');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_type',
                        method: 'POST', // ✅ FIXED
                        data: {
                            documentType: documentType,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {

                            if (response.success) {
                                $('#document_type').val('');
                                toastr.success(response.msg || 'Saved successfully');

                                documentTypeTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'Already exists');
                            }
                        },
                        error: function() {
                            toastr.error('Failed to save Document Type');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });

                // ======================
                // DOCUMENT FORWARDED WITH TABLE
                // ======================
                var documentForwardwithTable = $('#document_forwardwith_tables').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_forwardwith_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [{
                            data: null,
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: 'forwarded_with',
                            defaultContent: ''
                        },
                        {
                            data: 'user',
                            defaultContent: ''
                        },
                        {
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        }
                    ],
                    responsive: true,
                    autoWidth: false
                });

                // ======================
                // SAVE DOCUMENT FORWARDED WITH
                // ======================
                $('#save_forwardwith').on('click', function() {

                    var $button = $(this);
                    var forwardwith = $.trim($('#document_forwardwith').val());

                    if (!forwardwith) {
                        toastr.error('Forwarded with is required');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_forwardwith',
                        method: 'GET', // ⚠️ should be POST ideally
                        data: {
                            fowardwith: forwardwith
                        },
                        success: function(response) {

                            $('#document_forwardwith').val('');
                            toastr.success(response.msg || 'Data saved successfully');

                            documentForwardwithTable.ajax.reload(null, false);
                        },
                        error: function() {
                            toastr.error('Failed to save data');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });

                // ======================
                // MANDATORY SIGNATURE TABLE
                // ======================
                var mandatoryTable = $('#document_mandatory_tables').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_mandatorysignature_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [{
                            data: null,
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: 'no_of_mandatory',
                            defaultContent: ''
                        },
                        {
                            data: 'signature_level',
                            defaultContent: ''
                        },
                        {
                            data: 'user',
                            defaultContent: ''
                        },
                        {
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        }
                    ],
                    responsive: true,
                    autoWidth: false
                });

                // ======================
                // SAVE MANDATORY SIGNATURE
                // ======================
                $('#save_mandatory').on('click', function() {

                    var $button = $(this);
                    var number = $.trim($('#no_signature').val());
                    var mandatory = $.trim($('#document_signature').val());

                    if (!number || !mandatory) {
                        toastr.error('All fields are required');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_mandatorySignature',
                        method: 'GET',
                        data: {
                            number: number,
                            mandatory: mandatory
                        },
                        success: function(response) {

                            $('#no_signature').val('');
                            $('#document_signature').val('');

                            toastr.success(response.msg || 'Saved successfully');

                            mandatoryTable.ajax.reload(null, false);
                        },
                        error: function() {
                            toastr.error('Failed to save data');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });

                // ======================
                // DOCUMENT PURPOSE TABLE
                // ======================
                var purposeTable = $('#document_purposes_tables').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_purpose_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [
                        {
                            data: null,
                            render: function (data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        { data: 'purpose_type' },
                        { data: 'user' },
                        {
                            data: 'created_at',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        }
                    ],
                    paging: true,
                    searching: true,
                    pageLength: 10,
                    lengthMenu: [[5, 10, 25, -1], [5, 10, 25, "All"]],
                    responsive: true,
                    autoWidth: false
                });

                // ======================
                // SAVE PURPOSE
                // ======================
                $('#save_purpose').on('click', function () {

                    let $button = $(this);
                    let purpose = $.trim($('#document_purpose').val());

                    if (!purpose) {
                        toastr.error('Document Purpose is required');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_purpose',
                        method: 'POST', // FIXED
                        data: {
                            purpose: purpose,
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function (response) {

                            if (response.success) {
                                $('#document_purpose').val('');
                                toastr.success(response.msg || 'Saved successfully');

                                purposeTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'Already exists');
                            }
                        },
                        error: function () {
                            toastr.error('Failed to save data');
                        },
                        complete: function () {
                            $button.prop('disabled', false);
                        }
                    });
                });

                // ======================
                // REFERRED TO TABLE
                // ======================
                let selectedReferredToId = null;

                function loadReferredToDesignationOptions(targetSelector, department, selectedValue = '') {
                    const $target = $(targetSelector);

                    $.ajax({
                        url: '/DocManagement/document_designations_by_department',
                        method: 'GET',
                        data: {
                            department: department || ''
                        },
                        success: function(response) {
                            $target.empty().append('<option value="">' + (targetSelector === '#referred_to_filter_designation' ? 'All' : 'Select Designation') + '</option>');

                            $.each(response || [], function(index, designation) {
                                $target.append('<option value="' + designation + '">' + designation + '</option>');
                            });

                            if (selectedValue) {
                                $target.val(selectedValue);
                            }

                            $target.trigger('change.select2');
                        },
                        error: function() {
                            $target.empty().append('<option value="">' + (targetSelector === '#referred_to_filter_designation' ? 'All' : 'Select Designation') + '</option>').trigger('change.select2');
                        }
                    });
                }

                var referredToTable = $('#document_referred_to_table').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_referred_to_gets',
                        type: 'GET',
                        data: function(d) {
                            d.department = $('#referred_to_filter_department').val();
                            d.designation = $('#referred_to_filter_designation').val();
                            d.status = $('#referred_to_filter_status').val();
                        },
                        dataSrc: ''
                    },
                    columns: [{
                        data: null,
                        render: function(data) {
                                return `<button class="btn btn-xs btn-primary open-status-modal"
                        data-id="${data.id}"
                        data-department="${data.department || ''}"
                        data-designation="${data.designation || ''}"
                        data-officer-name="${data.officer_name || ''}"
                        data-status="${data.status || 'Active'}">
                        Change Status
                    </button>`;
                            }
                        },
                        {
                            data: 'date_time',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        },
                        {
                            data: 'department',
                            defaultContent: ''
                        },
                        {
                            data: 'designation',
                            defaultContent: ''
                        },
                        {
                            data: 'officer_name',
                            defaultContent: ''
                        },
                        {
                            data: 'status',
                            defaultContent: ''
                        },
                        {
                            data: 'user',
                            defaultContent: ''
                        }
                    ],
                    responsive: true,
                    autoWidth: false
                });

                function resetReferredToForm() {
                    $('#referred_to_department').val('').trigger('change');
                    $('#referred_to_designation').empty().append('<option value="">Select Designation</option>').trigger('change');
                    $('#referred_to_officer_name').val('');
                    $('#referred_to_date_time').val('{{ now()->format('m/d/Y h:i A') }}');
                    $('#referred_to_status').val('Active');
                }

                function loadReferredToStatusHistory(id) {
                    $.ajax({
                        url: '/DocManagement/document_referred_to_status_history_gets',
                        method: 'GET',
                        data: {
                            id: id
                        },
                        success: function(response) {
                            var tableBody = $('#referred_to_status_history_table tbody');
                            tableBody.empty();

                            $.each(response || [], function(index, item) {
                                tableBody.append(
                                    '<tr>' +
                                        '<td>' + (item.date_time || '') + '</td>' +
                                        '<td>' + (item.changed_by || '') + '</td>' +
                                        '<td>' + (item.status_from || '') + '</td>' +
                                        '<td>' + (item.status_to || '') + '</td>' +
                                    '</tr>'
                                );
                            });
                        },
                        error: function() {
                            $('#referred_to_status_history_table tbody').empty();
                        }
                    });
                }

                $('#referred_to_department').on('change', function() {
                    loadReferredToDesignationOptions('#referred_to_designation', $(this).val());
                });

                $('#referred_to_filter_department').on('change', function() {
                    loadReferredToDesignationOptions('#referred_to_filter_designation', $(this).val());
                    referredToTable.ajax.reload(null, false);
                });

                $('#referred_to_filter_designation, #referred_to_filter_status').on('change', function() {
                    referredToTable.ajax.reload(null, false);
                });

                $('#save_referred_to').on('click', function() {
                    const $button = $(this);
                    const department = $('#referred_to_department').val();
                    const designation = $('#referred_to_designation').val();
                    const officerName = $.trim($('#referred_to_officer_name').val());

                    if (!department || !designation || !officerName) {
                        toastr.error('Department, Designation and Officer Name are required');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/store_referred_to',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            date_time: $('#referred_to_date_time').val(),
                            department: department,
                            designation: designation,
                            officer_name: officerName,
                            status: $('#referred_to_status').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg || 'Saved successfully');
                                resetReferredToForm();
                                referredToTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'Failed to save referred to');
                            }
                        },
                        error: function() {
                            toastr.error('Failed to save referred to');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });

                $(document).on('click', '.open-status-modal', function() {
                    selectedReferredToId = $(this).data('id');
                    $('#status_change_department').val($(this).data('department') || '');
                    $('#status_change_designation').val($(this).data('designation') || '');
                    $('#status_change_officer_name').val($(this).data('officer-name') || '');
                    $('#status_change_current').val($(this).data('status') || 'Active');
                    $('#status_change_to').val($(this).data('status') || 'Active').trigger('change.select2');
                    loadReferredToStatusHistory(selectedReferredToId);
                    $('#referred_to_status_modal').modal('show');
                });

                $('#save_referred_to_status_change').on('click', function() {
                    const $button = $(this);

                    if (!selectedReferredToId) {
                        toastr.error('No referred to record selected');
                        return;
                    }

                    $button.prop('disabled', true);

                    $.ajax({
                        url: '/DocManagement/update_referred_to_status',
                        method: 'GET',
                        data: {
                            id: selectedReferredToId,
                            status: $('#status_change_to').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.msg || 'Status changed successfully');
                                $('#status_change_current').val($('#status_change_to').val());
                                loadReferredToStatusHistory(selectedReferredToId);
                                referredToTable.ajax.reload(null, false);
                            } else {
                                toastr.error(response.msg || 'Failed to change status');
                            }
                        },
                        error: function() {
                            toastr.error('Failed to change status');
                        },
                        complete: function() {
                            $button.prop('disabled', false);
                        }
                    });
                });

                // -----------------------------
                // SHOW MODAL ON ADD BUTTON CLICK
                // -----------------------------
                $('#create_airports').click(function() {
                    $('#airport_form_modals').modal('show');
                });

                // -----------------------------
                // SUBMIT LOGO FORM VIA AJAX
                // -----------------------------
                $('#logo').submit(function(event) {
                    event.preventDefault();

                    var $form = $(this);

                    $.ajax({
                        url: $form.attr('action'),
                        method: $form.attr('method'),
                        data: new FormData(this),
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            toastr.success('Logo uploaded successfully!', 'Success');
                            $form[0].reset();
                            $('#airport_form_modals').modal('hide');
                            uploadLogoTable.ajax.reload();
                        },
                        error: function(xhr, status, error) {
                            toastr.error('Failed to upload logo!', 'Error');
                        }
                    });
                });

                // -----------------------------
                // INITIALIZE UPLOAD LOGO DATA TABLE
                // -----------------------------
                var uploadLogoTable = $('#document_logo_tables').DataTable({
                    processing: true,
                    serverSide: false,
                    ajax: {
                        url: '/DocManagement/document_uploadlogo_gets',
                        type: 'GET',
                        dataSrc: ''
                    },
                    columns: [{
                            data: 'created_at',
                            title: 'date',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        },
                        {
                            data: 'upload_logo',
                            title: 'Upload Logo'
                        },
                        {
                            data: 'position',
                            title: 'Logo Position'
                        },
                        {
                            data: 'enable_button',
                            title: 'Enable Button'
                        },
                        {
                            data: 'username',
                            title: 'Username'
                        }
                    ],
                    responsive: true,
                    autoWidth: false
                });

                function toggleEnableDisableButtons(disabled) {
                    $('#enable_button').toggle(disabled);
                    $('#disable_button').toggle(!disabled);
                }

                // Initially hide "Disable" button
                toggleEnableDisableButtons(true);

                // Example usage
                $('#enable_button').click(() => toggleEnableDisableButtons(false));
                $('#disable_button').click(() => toggleEnableDisableButtons(true));


            }
        );
    </script>

    <script>
        let upload_table;

        $(document).ready(function() {

            /**
             * ==========================================
             * DOCUMENT UPLOAD TABLE (DataTable INIT)
             * ==========================================
             */
            upload_table = $('#document_upload_tables').DataTable({
                processing: true,
                serverSide: false, // change to true if backend supports DataTables server-side
                ajax: {
                    url: '/DocManagement/document_upload_gets',
                    type: 'GET',
                    dataSrc: '' // because your API returns a plain array
                },
                columns: [{
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + 1; // Serial number
                        }
                    },
                    {
                            data: 'date',
                            render: function(data) {
                                if (!data) return '';

                                // Clean + format date properly
                                let date = new Date(data);

                                if (isNaN(date)) return '';

                                let options = {
                                    year: 'numeric',
                                    month: 'short',
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: true // AM/PM
                                };

                                return date.toLocaleString('en-US', options);
                            }
                        },
                    {
                        data: 'location'
                    },
                    {
                        data: 'user'
                    },
                    {
                        data: 'designations'
                    },
                    {
                        data: 'upload_signature'
                    },
                    {
                        data: 'signature_levels'
                    }
                ],
                responsive: true,
                autoWidth: false
            });

            /**
             * ==========================================
             * FORM SUBMIT (AJAX)
             * ==========================================
             */
            $(document)
                .off('submit.signatureForm')
                .on('submit.signatureForm', '#sinature', function(e) {
                    e.preventDefault();

                    let form = $(this);

                    $.ajax({
                        url: form.attr('action'),
                        method: form.attr('method'),
                        data: new FormData(this),
                        processData: false,
                        contentType: false,

                        success: function(response) {
                            toastr.success('Data saved successfully!', 'Success');

                            // Reload DataTable instead of full page refresh
                            upload_table.ajax.reload(null, false);
                        },
                        error: function() {
                            toastr.error('Failed to save data!', 'Error');
                        }
                    });
                });

            /**
             * ==========================================
             * INIT AIRPORT MODULE
             * ==========================================
             */
            airport_module.init();

        });


        /**
         * ==========================================
         * AIRPORT MODULE (CLEANED)
         * ==========================================
         */
        const airport_module = {
            edit_id: null,
            delete_id: null,
            airport_table: null,

            init: function() {
                this.initTable();
                this.bindFilters();
                this.populate_countries();
                this.listener();
            },

            initTable: function() {
                this.airport_table = $('#airport_table').DataTable({
                    paging: true,
                    searching: true,
                    ordering: false,
                    info: false,
                    pageLength: 10,
                    processing: true,
                    serverSide: true,

                    ajax: {
                        url: "{{ action('\Modules\Airline\Http\Controllers\AirlineSettingController@get_airport_table') }}",
                        data: function(d) {

                            if ($('#airports_filter_date_range').val()) {
                                let picker = $('#airports_filter_date_range').data('daterangepicker');
                                d.start_date = picker.startDate.format('YYYY-MM-DD');
                                d.end_date = picker.endDate.format('YYYY-MM-DD');
                            }

                            d.country = $('#airports_filter_country_select').val();
                        }
                    },

                    columns: [{
                            data: 'date_added',
                            name: 'date_added'
                        },
                        {
                            data: 'country',
                            name: 'country'
                        },
                        {
                            data: 'province',
                            name: 'province'
                        },
                        {
                            data: 'airport_name',
                            name: 'airport_name'
                        },
                        {
                            data: 'username',
                            name: 'username'
                        },
                        {
                            data: 'airport_status',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ]
                });
            },

            bindFilters: function() {
                let self = this;

                $('#airports_filter_country_select').on('change', function() {
                    self.applyFilters();
                });

                $('#airports_filter_province_select').on('keyup', function() {
                    self.airport_table
                        .column('province:name')
                        .search(this.value)
                        .draw();
                });

                $('#airports_filter_date_range').daterangepicker(
                    dateRangeSettings,
                    function(start, end) {
                        $('#airports_filter_date_range').val(
                            start.format(moment_date_format) + ' ~ ' +
                            end.format(moment_date_format)
                        );
                        self.airport_table.ajax.reload();
                    }
                );

                $('#airports_filter_date_range').on('cancel.daterangepicker', function() {
                    $(this).val('');
                    self.airport_table.ajax.reload();
                });
            },

            applyFilters: function() {
                let self = this;

                let selectedCountry = $('#airports_filter_country_select').val();
                let airportName = $('#airports_filter_airport_name').val();

                $.ajax({
                    url: '{{ route('airports') }}',
                    method: 'GET',
                    data: {
                        country: selectedCountry
                    },

                    success: function(data) {
                        populateAirportDropdown(data);
                        populateProvinceDropdown(data);

                        $('#airports_filter_airport_name')
                            .off()
                            .select2({
                                width: '100%',
                                allowClear: true
                            })
                            .on('change', function() {
                                self.airport_table
                                    .column('airport_name:name')
                                    .search($(this).val())
                                    .draw();
                            });
                    }
                });

                function populateAirportDropdown(data) {
                    let el = $('#airports_filter_airport_name').empty();
                    Object.values(data).forEach(a => {
                        el.append(new Option(a.airport_name, a.airport_name));
                    });
                }

                function populateProvinceDropdown(data) {
                    let el = $('#airports_filter_province_select').empty();

                    let provinces = [...new Set(Object.values(data).map(a => a.province))];

                    provinces.forEach(p => {
                        el.append(new Option(p, p));
                    });
                }

                this.airport_table
                    .column('country:name')
                    .search(selectedCountry)
                    .draw();

                this.airport_table
                    .column('airport_name:name')
                    .search(airportName)
                    .draw();
            },

            populate_countries: function() {
                fetch("https://restcountries.com/v2/all")
                    .then(res => res.json())
                    .then(data => {
                        let countrySelect = $("#country_select");
                        let filterSelect = $("#airports_filter_country_select");

                        data.forEach(c => {
                            let opt = new Option(c.name, c.name);
                            countrySelect.append(opt.cloneNode(true));
                            filterSelect.append(opt);
                        });

                        countrySelect.select2({
                            width: '100%'
                        });
                        filterSelect.select2({
                            width: '100%'
                        });
                    });
            },

            update_status: function(data) {
                $.ajax({
                    url: '/airline/update_status_airport',
                    method: 'PATCH',
                    data: data,
                    success: (res) => {
                        toastr.success(res.message);
                        this.airport_table.ajax.reload();
                    }
                });
            },

            delete: function(data) {
                $.ajax({
                    url: '/airline/delete_airport',
                    method: 'DELETE',
                    data: data,
                    success: (res) => {
                        toastr.success(res.message);
                        this.airport_table.ajax.reload();
                    }
                });
            },

            listener: function() {
                let self = this;

                $('#airport_table').on("click", ".enable, .disable", function() {
                    let data = $(this).data();
                    self.update_status(data);
                });

                $('#delete_airport').click(function() {
                    if (self.delete_id) {
                        self.delete({
                            id: self.delete_id
                        });
                    }
                });
            }
        };
    </script>
@endsection
