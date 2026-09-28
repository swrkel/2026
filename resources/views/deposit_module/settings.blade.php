@extends('layouts.app')
@section('title', 'Deposit Settings')

@section('content')
<!-- Custom premium style layer -->
<style>
    :root {
        --primary: hsl(220, 90%, 56%);
        --primary-hover: hsl(220, 90%, 46%);
        --bg-glass: rgba(255, 255, 255, 0.85);
        --border-glass: rgba(220, 225, 235, 0.6);
        --text-dark: hsl(220, 20%, 20%);
        --text-muted: hsl(220, 10%, 45%);
    }

    .premium-card {
        background: var(--bg-glass);
        backdrop-filter: blur(10px);
        border: 1px solid var(--border-glass);
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.08);
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 24px;
        margin-bottom: 24px;
    }

    .premium-card:hover {
        box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.12);
        transform: translateY(-2px);
    }

    .settings-tab-btn {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: var(--text-muted);
        padding: 12px 24px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.2s ease;
        margin-right: 12px;
    }

    .settings-tab-btn.active {
        background: var(--primary);
        color: #fff;
        border-color: var(--primary);
        box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
    }

    .toggle-switch {
        position: relative;
        display: inline-block;
        width: 48px;
        height: 24px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 24px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: var(--primary);
    }

    input:checked + .slider:before {
        transform: translateX(24px);
    }

    .badge-premium {
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 12px;
    }

    .badge-active {
        background-color: #dcfce7;
        color: #166534;
    }

    .badge-inactive {
        background-color: #fee2e2;
        color: #991b1b;
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animated-tab {
        animation: fadeIn 0.4s ease forwards;
    }
</style>

<section class="content-header">
    <h1>Deposit Settings
        <small>Configure deposit options & metadata</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    
    <!-- Tab Controls -->
    <div style="margin-bottom: 24px;">
        <button class="settings-tab-btn active" onclick="switchTab('deposit-types-tab', this)">
            <i class="fa fa-list"></i> Deposit Types
        </button>
        <button class="settings-tab-btn" onclick="switchTab('prefix-number-tab', this)">
            <i class="fa fa-cogs"></i> Prefix & Starting Numbers
        </button>
    </div>

    <!-- TAB 1: Deposit Types -->
    <div id="deposit-types-tab" class="premium-card animated-tab">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
            <div style="position: relative; width: 300px;">
                <i class="fa fa-search" style="position: absolute; left: 12px; top: 12px; color: #94a3b8;"></i>
                <input type="text" id="type-search" class="form-control" placeholder="Search Deposit Types..." style="padding-left: 36px; border-radius: 8px;">
            </div>
            
            <button class="btn btn-primary" data-toggle="modal" data-target="#addTypeModal" style="border-radius: 8px; font-weight: 600; padding: 10px 20px;">
                <i class="fa fa-plus"></i> Add Deposit Type
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover" id="types-table" style="border-radius: 8px; overflow: hidden;">
                <thead>
                    <tr style="background-color: #f8fafc; color: #475569; font-weight: 700;">
                        <th>Action (Status Toggle)</th>
                        <th>Date & Time</th>
                        <th>Deposit Type</th>
                        <th>Deposit Period</th>
                        <th>Deposit Period Value</th>
                        <th>Added By</th>
                        <th>Edited By</th>
                        <th>Activities History</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deposit_types as $type)
                        <tr class="type-row">
                            <td style="vertical-align: middle;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <label class="toggle-switch">
                                        <input type="checkbox" class="status-toggle" data-id="{{ $type->id }}" {{ $type->status === 'Active' ? 'checked' : '' }}>
                                        <span class="slider"></span>
                                    </label>
                                    <span class="status-label badge-premium {{ $type->status === 'Active' ? 'badge-active' : 'badge-inactive' }}">
                                        {{ $type->status }}
                                    </span>
                                </div>
                            </td>
                            <td style="vertical-align: middle;">{{ $type->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="type-name" style="vertical-align: middle; font-weight: 600; color: #1e293b;">{{ $type->name }}</td>
                            <td style="vertical-align: middle;">{{ $type->period }}</td>
                            <td style="vertical-align: middle; font-weight: 700;">{{ $type->period_value }}</td>
                            <td style="vertical-align: middle;">{{ $type->creator->username ?? 'System' }}</td>
                            <td style="vertical-align: middle;">{{ $type->editor->username ?? '-' }}</td>
                            <td style="vertical-align: middle;">
                                <button class="btn btn-sm btn-info view-activities" data-id="{{ $type->id }}" style="border-radius: 6px;">
                                    <i class="fa fa-history"></i> Log History
                                </button>
                            </td>
                        </tr>
                    @empty
                        <!-- Default Seed Data if Empty -->
                        <tr class="type-row">
                            <td style="vertical-align: middle;"><span class="badge-premium badge-active">Active</span></td>
                            <td style="vertical-align: middle;">{{ now()->format('Y-m-d H:i:s') }}</td>
                            <td class="type-name" style="vertical-align: middle; font-weight: 600;">Saving Deposits</td>
                            <td style="vertical-align: middle;">Monthly</td>
                            <td style="vertical-align: middle; font-weight: 700;">12</td>
                            <td style="vertical-align: middle;">Admin</td>
                            <td style="vertical-align: middle;">-</td>
                            <td style="vertical-align: middle;"><button class="btn btn-sm btn-info disabled" style="border-radius: 6px;"><i class="fa fa-history"></i> Log History</button></td>
                        </tr>
                        <tr class="type-row">
                            <td style="vertical-align: middle;"><span class="badge-premium badge-active">Active</span></td>
                            <td style="vertical-align: middle;">{{ now()->format('Y-m-d H:i:s') }}</td>
                            <td class="type-name" style="vertical-align: middle; font-weight: 600;">Fix Deposits</td>
                            <td style="vertical-align: middle;">Yearly</td>
                            <td style="vertical-align: middle; font-weight: 700;">2</td>
                            <td style="vertical-align: middle;">Admin</td>
                            <td style="vertical-align: middle;">-</td>
                            <td style="vertical-align: middle;"><button class="btn btn-sm btn-info disabled" style="border-radius: 6px;"><i class="fa fa-history"></i> Log History</button></td>
                        </tr>
                        <tr class="type-row">
                            <td style="vertical-align: middle;"><span class="badge-premium badge-active">Active</span></td>
                            <td style="vertical-align: middle;">{{ now()->format('Y-m-d H:i:s') }}</td>
                            <td class="type-name" style="vertical-align: middle; font-weight: 600;">Demand Deposits</td>
                            <td style="vertical-align: middle;">Daily</td>
                            <td style="vertical-align: middle; font-weight: 700;">30</td>
                            <td style="vertical-align: middle;">Admin</td>
                            <td style="vertical-align: middle;">-</td>
                            <td style="vertical-align: middle;"><button class="btn btn-sm btn-info disabled" style="border-radius: 6px;"><i class="fa fa-history"></i> Log History</button></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: Prefix & Starting Numbers -->
    <div id="prefix-number-tab" class="premium-card animated-tab" style="display: none;">
        <form action="{{ route('deposit-module.save-settings') }}" method="POST">
            @csrf
            
            <div class="row">
                <div class="col-md-6 form-group">
                    <label style="font-weight: 600; color: var(--text-dark);">Deposit Prefix</label>
                    <input type="text" name="prefix" class="form-control" value="{{ $prefix }}" required style="border-radius: 8px; padding: 10px;">
                    <small class="text-muted">Example: DEP, SAV, TRNS</small>
                </div>

                <div class="col-md-6 form-group">
                    <label style="font-weight: 600; color: var(--text-dark);">Starting Number</label>
                    <input type="number" name="starting_number" class="form-control" value="{{ $starting_number }}" min="1" required style="border-radius: 8px; padding: 10px;">
                    <small class="text-muted">Next deposit number will automatically increment from this.</small>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label style="font-weight: 600; color: var(--text-dark); display: block; margin-bottom: 12px;">Allowed Currencies (Multi-Currency Support)</label>
                <div style="display: flex; flex-wrap: wrap; gap: 16px; background-color: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid var(--border-glass);">
                    @foreach($system_currencies as $code)
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: #334155;">
                            <input type="checkbox" name="currencies[]" value="{{ $code }}" {{ in_array($code, $selected_currencies) ? 'checked' : '' }} style="width: 18px; height: 18px; accent-color: var(--primary);">
                            <span>{{ $code }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary" style="font-weight: 600; padding: 12px 30px; border-radius: 8px; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);">
                    <i class="fa fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>

</section>

<!-- MODAL: Add Deposit Type -->
<div class="modal fade" id="addTypeModal" tabindex="-1" role="dialog" aria-labelledby="addTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 20px 50px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background-color: var(--primary); color: #fff; padding: 20px;">
                <h5 class="modal-title" id="addTypeModalLabel" style="font-weight: 700; font-size: 18px;">Add Deposit Type</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('deposit-module.add-deposit-type') }}" method="POST">
                @csrf
                <div class="modal-body" style="padding: 24px;">
                    <div class="form-group">
                        <label style="font-weight: 600;">Deposit Type Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Saving Deposits" style="border-radius: 8px; padding: 10px;">
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Deposit Period</label>
                        <select name="period" class="form-control" required style="border-radius: 8px; height: 40px;">
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly" selected>Monthly</option>
                            <option value="Yearly">Yearly</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label style="font-weight: 600;">Deposit Period Value (Integers Only)</label>
                        <input type="number" name="period_value" class="form-control" required min="1" step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57" placeholder="e.g. 12" style="border-radius: 8px; padding: 10px;">
                        <small class="text-muted">Only whole numbers are allowed.</small>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8fafc; padding: 20px;">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="border-radius: 8px; font-weight: 600;">Save Deposit Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Activity Logs History -->
<div class="modal fade" id="activitiesModal" tabindex="-1" role="dialog" aria-labelledby="activitiesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none;">
            <div class="modal-header" style="background-color: #334155; color: #fff; padding: 20px;">
                <h5 class="modal-title" id="activitiesModalLabel" style="font-weight: 700; font-size: 18px;">Activities Log History</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #fff; opacity: 0.8;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 24px; max-height: 400px; overflow-y: auto;">
                <ul class="timeline" id="activities-list">
                    <!-- Loaded dynamically via Ajax -->
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" style="border-radius: 8px;">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
    // Tab switching logic
    function switchTab(tabId, btn) {
        // Toggle tabs visibility
        $('#deposit-types-tab').hide();
        $('#prefix-number-tab').hide();
        $('#' + tabId).fadeIn(300);

        // Toggle active button styling
        $('.settings-tab-btn').removeClass('active');
        $(btn).addClass('active');
    }

    $(document).ready(function() {
        // 1. Universal Search Box inside Tab 1
        $('#type-search').on('keyup', function() {
            var val = $(this).val().toLowerCase();
            $('.type-row').each(function() {
                var text = $(this).find('.type-name').text().toLowerCase();
                $(this).toggle(text.indexOf(val) > -1);
            });
        });

        // 2. Status Active/Inactive Toggle with Ajax
        $('.status-toggle').on('change', function() {
            var id = $(this).data('id');
            var label = $(this).closest('td').find('.status-label');
            
            $.ajax({
                url: '/deposit-module/toggle-deposit-type-status/' + id,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.msg);
                        // Toggle visual indicator
                        if (label.hasClass('badge-active')) {
                            label.removeClass('badge-active').addClass('badge-inactive').text('Inactive');
                        } else {
                            label.removeClass('badge-inactive').addClass('badge-active').text('Active');
                        }
                    } else {
                        toastr.error(res.msg);
                    }
                },
                error: function() {
                    toastr.error('Error toggling status.');
                }
            });
        });

        // 3. Load Log History Modal via Ajax
        $('.view-activities').on('click', function() {
            var id = $(this).data('id');
            var container = $('#activities-list');
            container.empty().append('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
            $('#activitiesModal').modal('show');

            $.ajax({
                url: '/deposit-module/get-deposit-type-activities/' + id,
                type: 'GET',
                success: function(res) {
                    container.empty();
                    if (res.success && res.activities.length > 0) {
                        res.activities.forEach(function(act) {
                            var date = new Date(act.created_at).toLocaleString();
                            container.append(
                                '<li style="margin-bottom: 20px; list-style: none; border-left: 3px solid var(--primary); padding-left: 15px;">' +
                                '   <div style="font-weight: 600; color: #1e293b;">' + act.details + '</div>' +
                                '   <div style="font-size: 12px; color: #64748b; margin-top: 4px;">' +
                                '       <i class="fa fa-user"></i> ' + act.changed_by_user + ' | ' +
                                '       <i class="fa fa-clock"></i> ' + date +
                                '   </div>' +
                                '</li>'
                            );
                        });
                    } else {
                        container.append('<li class="text-center text-muted">No activity logged for this Deposit Type yet.</li>');
                    }
                },
                error: function() {
                    container.empty().append('<li class="text-center text-danger">Failed to load activity log history.</li>');
                }
            });
        });
    });
</script>
@endsection
