@extends('layouts.app')
@section('title', __('essentials::lang.hrm_advance'))

@section('content')
    @php
        $business_id = request()->session()->get('user.business_id');
        $pacakge_details = [];
        $subscription = Modules\Superadmin\Entities\Subscription::active_subscription($business_id);
        if (!empty($subscription)) {
            $pacakge_details = $subscription->package_details;
        }
    @endphp
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <div class="row" id="app">
        <div class="col-md-12">
            <div class="settlement_tabs">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item active">
                        <a href="#salary_payment" class="salary_payment" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('essentials::lang.hrm_tab_salary_payment')</strong>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#employee_advance" class="employee_advance" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('essentials::lang.hrm_tab_employee_advance')</strong>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#employee_advance_settings" class="employee_advance_settings" data-toggle="tab">
                            <i class="fa fa-file-text-o"></i> <strong>@lang('essentials::lang.hrm_tab_employee_advance_settings')</strong>
                        </a>
                    </li>

                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="salary_payment">
                        <section class="container">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="" style="margin-bottom: 5px !important;">
                                        <br />
                                        <div id="accordion1" class="according  ">
                                            <div class="card">
                                                <div class="card-header" style="cursor: pointer;">
                                                    <a class="card-link" data-toggle="collapse"
                                                        style="padding-top: 5px !important; padding-bottom: 5px !important"
                                                        href="#accordion11" aria-expanded="true"> <i class="fa fa-filter"
                                                            aria-hidden="true"></i> Filters </a>
                                                </div>
                                                <div id="accordion11" class="collapse show" data-parent="#accordion1"
                                                    style="">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label for="date">@lang('essentials::lang.hrm_advance_label_date')</label>
                                                                    <input v-model="payment.date" class="form-control"
                                                                        ref="date" name="date" type="text">
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group">
                                                                    <label for="period">@lang('essentials::lang.hrm_advance_label_period')</label>
                                                                    <input v-model="payment.salary_period"
                                                                        class="form-control" ref="daterange" name="period"
                                                                        id="salary_period" type="text" />
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label for="payment_type_id">@lang('essentials::lang.hrm_advance_label_type')</label>
                                                                    <br />
                                                                    <select v-model="payment.payment_type_id"
                                                                        id="payment_type_id"
                                                                        class="form-select form-control">
                                                                        <option value="" selected>Please Select
                                                                        </option>
                                                                        <template v-for="payAccount, in payment_system">
                                                                            <option :value="payAccount.id"
                                                                                :key="payAccount.id">
                                                                                @{{ payAccount.name }}</option>
                                                                        </template>
                                                                    </select>

                                                                </div>
                                                            </div>
                                                            <div class="col-md-3">
                                                                <div class="form-group">
                                                                    <label for="payment_method_id">@lang('essentials::lang.hrm_advance_label_account')</label>
                                                                    <br />
                                                                    <select ref="methodSelect"
                                                                        v-model="payment.payment_method_id"
                                                                        id="payment_method_id"
                                                                        class="form-select form-control filter-select">
                                                                        <template
                                                                            v-for="payMethod in payment_method_merged">
                                                                            <option :value="payMethod.id"
                                                                                :key="payMethod.id">
                                                                                @{{ payMethod.name }}</option>
                                                                        </template>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-3 pd_cheque_boxes" v-if="showPdChequeOptions">
                                                                <div class="form-group">
                                                                    <label class="text-danger">
                                                                        @lang('account.pd_cheques_management')
                                                                    </label>
                                                                    <div class="checkbox">
                                                                        <label class="text-danger">
                                                                            <input type="checkbox"
                                                                                v-model="payment.post_dated_cheque"
                                                                                :true-value="1" :false-value="0">
                                                                            @lang('account.post_dated_cheque')
                                                                        </label>
                                                                    </div>
                                                                    <div class="checkbox">
                                                                        <label class="text-danger">
                                                                            <input type="checkbox"
                                                                                v-model="payment.update_post_dated_cheque"
                                                                                :true-value="1" :false-value="0">
                                                                            @lang('account.update_post_dated_cheque')
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2">
                                                                <div class="form-group">
                                                                    <label for="paid">@lang('essentials::lang.hrm_advance_label_paid')</label>
                                                                    <br />
                                                                    <select v-model="payment.paid"
                                                                        class="form-select form-control">
                                                                        <option value="yes">
                                                                            @lang('essentials::lang.hrm_advance_label_paid_yes')
                                                                        </option>
                                                                        <option value="no">
                                                                            @lang('essentials::lang.hrm_advance_label_paid_no')
                                                                        </option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-2" v-if="showCheck">
                                                                <div class="form-group">
                                                                    <label for="check">@lang('essentials::lang.hrm_advance_label_check'):</label>
                                                                    <input v-model="payment.check" class="form-control"
                                                                        name="check" type="text" />
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @component('components.widget', ['class' => 'box-solid'])
                                <div class="table-responsive">
                                    <div width="100%" class="d-flex" style="justify-content: end;">
                                        <p class="text-danger" style="margin-right:30px;"><b>Total:</b></p>
                                        <p class="text-danger"> @{{ formatNumber(totalAmount, business.currency_precision) }}</p>
                                    </div>
                                    <table class="table table-sm table-bordered table-striped w-100 mb-0"
                                        id="employees_table">
                                        <thead>
                                            <tr>
                                                <th width="4%">@lang('essentials::lang.employee_no')</th>
                                                <th width="15%">@lang('essentials::lang.e_payment_no')</th>
                                                <th width="15%">@lang('essentials::lang.employee_name')</th>
                                                <th width="10%">@lang('essentials::lang.hrm_advance_label_type')</th>
                                                <th width="10%">@lang('essentials::lang.hrm_advance_payment_method')</th>
                                                <th width="10%">@lang('essentials::lang.hrm_transaction_date')</th>
                                                <th width="18%">@lang('essentials::lang.hrm_advance_title_amount')</th>
                                                <th width="18%" v-show="showPaidAmount">@lang('essentials::lang.hrm_advance_title_paid')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="employees.length > 0" v-for="(emp,ind) in employees">
                                                <td>@{{ emp.employee_no }}</td>
                                                <td class="text-danger">
                                                    EP-@{{ String(ind + 1).padStart(4, '0') }}
                                                </td>
                                                <td>@{{ emp.name }}</td>
                                                <td class="text-danger">
                                                    <template
                                                        v-if="payment.payment_type_id != undefined && payment.payment_type_id != null">
                                                        @{{ payment_system.find(item => item.id == payment.payment_type_id)?.name }}
                                                    </template>
                                                </td>
                                                <td class="text-danger">
                                                    <template
                                                        v-if="payment.payment_method_id != undefined && payment.payment_method_id != null">
                                                        @{{ payment_method_merged.find(item => item.id == payment.payment_method_id)?.name }}
                                                    </template>
                                                </td>
                                                <td class="text-danger">
                                                    @{{ new Date(payment.date).toLocaleDateString() }}
                                                </td>
                                                <td class="text-danger">
                                                    <input
                                                        :readonly="payment_system.find(item => item.id == payment.payment_type_id)
                                                            ?.need_amount == 0"
                                                        v-model="emp.amount" class="form-control"
                                                        @input="syncAmountPaid(emp)" />
                                                    <!--<input v-model="emp.amount" class="form-control" @input="syncAmountPaid(emp)"/>-->
                                                </td>
                                                <td class="text-danger" v-show="showPaidAmount">
                                                    <input v-model="emp.amount_paid" class="form-control" />
                                                </td>

                                            </tr>

                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th class="text-danger" colspan="6" align="right">Total</th>

                                                <th class="text-danger">@{{ formatNumber(totalAmount, business.currency_precision) }}</th>

                                                <th class="text-danger" v-show="showPaidAmount">@{{ formatNumber(totalAmountPaid, business.currency_precision) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>

                                </div>
                                <div class="row" style="display: flex; align-items: center; justify-content: flex-end;">
                                    <button class="btn btn-primary btn-sm" style="margin-left: 10px;" type="submit"
                                        @click="saveAdvancePayments">Save</button>
                                </div>
                            @endcomponent
                        </section>
                    </div>

                    <div class="tab-pane" id="employee_advance">
                        <section class="content">
                            @component('components.widget', ['class' => 'box-solid'])
                                <!-- System Standard Toolbar -->
                                <div class="row" style="margin-bottom: 15px;">
                                    <div class="col-md-6">
                                        <div class="dataTables_info" id="advances_table_info" role="status"
                                            aria-live="polite">
                                            Showing <span id="entries_count">@{{ advances.length }}</span> entries for today
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="dataTables_filter">
                                            <label>Universal Search:
                                                <input type="search" class="form-control input-sm" placeholder="Search..."
                                                    v-model="universalSearch" style="width: 200px; display: inline-block;">
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filters Section -->
                                <div id="accordion1" class="according">
                                    <div class="card">
                                        <div class="card-header" style="cursor: pointer;">
                                            <a class="card-link" data-toggle="collapse"
                                                style="padding-top: 5px !important; padding-bottom: 5px !important"
                                                href="#accordion11" aria-expanded="true">
                                                <i class="fa fa-filter" aria-hidden="true"></i> Filters
                                            </a>
                                        </div>
                                        <div id="accordion11" class="collapse show" data-parent="#accordion1"
                                            style="">
                                            <div class="card-body">
                                                <div class="row">
                                                    <!-- <div class="col-md-2">
                                      <div class="form-group">
                                       <label for="date">@lang('essentials::lang.hrm_advance_label_date')</label>
                                       <input v-model="payment.date" class="form-control" ref="date" name="date" type="text">
                                      </div>
                                     </div> -->
                                                    <div class="col-md-2">
                                                        <div class="form-group">
                                                            <label for="advance_date">@lang('essentials::lang.hrm_advance_label_date')</label>
                                                            <div class="input-group">
                                                                <input id="advance_date" class="form-control" type="text"
                                                                    placeholder="Select date" readonly>
                                                                <div class="input-group-append">
                                                                    <span class="input-group-text" id="advance_date_icon">
                                                                        <i class="fa fa-calendar"></i>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                    <!--  -->
                                </div>

                                <div class="table-responsive">
                                    <br />
                                    <table class="table table-bordered table-striped w-100" id="advances_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th width="2%">#</th>
                                                <th width="8%">@lang('essentials::lang.employee_no')</th>
                                                <th width="10%">@lang('essentials::lang.e_payment_no')</th>
                                                <th width="15%">@lang('essentials::lang.employee_name')</th>
                                                <th width="12%" class="text-right">@lang('essentials::lang.hrm_advance_title_amount')</th>
                                                <th width="12%" class="text-right">@lang('essentials::lang.hrm_advance_title_paid')</th>
                                                <th width="15%">@lang('essentials::lang.hrm_advance_title_date')</th>
                                                <th width="15%">@lang('essentials::lang.hrm_cheque_number')</th>
                                                <th width="15%">@lang('essentials::lang.description')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(adv,ind) in filteredAdvances">
                                                <td>
                                                    <div class="btn-group open">
                                                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                                            data-toggle="dropdown" aria-expanded="false">Actions<span
                                                                class="caret"></span><span class="sr-only">Toggle Dropdown
                                                            </span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-left" role="menu"
                                                            x-placement="top-start"
                                                            style="position: absolute; transform: translate3d(0px, -140px, 0px); top: 0px; left: 0px; will-change: transform;">
                                                            <li>
                                                                <a @click="showView(adv)" style="cursor:pointer;">
                                                                    <i class="fa fa-eye"></i> View
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a @click="showEdit(adv)" style="cursor:pointer;">
                                                                    <i class="fa fa-edit"></i> Edit
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td>@{{ ind + 1 }}</td>
                                                <td>@{{ adv.employee_no }}</td>
                                                <td>@{{ adv.e_payment_no }}</td>
                                                <td>@{{ adv.name }}</td>
                                                <td class="text-right">@{{ formatNumber(adv.amount, business.currency_precision) }}</td>
                                                <td class="text-right">@{{ formatNumber(adv.amount_paid, business.currency_precision) }}</td>
                                                <td>@{{ formatDate(adv.datetime_entered) }}</td>
                                                <td>
                                                    <template v-if="adv.check_no != undefined && adv.check_no != null">
                                                        @{{ adv.check_no }}
                                                    </template>
                                                </td>
                                                <td>
                                                    <template v-if="adv.payment_type_name">
                                                        <b>Payment Type:</b> @{{ adv.payment_type_name }}<br>
                                                    </template>
                                                    <template v-if="adv.payment_method_name">
                                                        <b>Payment Method:</b> @{{ adv.payment_method_name }}<br>
                                                    </template>
                                                    <template
                                                        v-if="adv.salary_period_start != undefined && adv.salary_period_start != null">
                                                        <b>Salary Period:</b> @{{ adv.salary_period_start }} to @{{ adv.salary_period_end }}
                                                    </template>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            @endcomponent
                        </section>
                    </div>

                    <div class="tab-pane" id="employee_advance_settings">
                        <section class="content">
                            @component('components.widget', ['class' => 'box-solid'])
                                <div class="row" style="display: flex; align-items: center; justify-content: flex-end;">
                                    <button class="btn btn-primary btn-sm" type="submit"
                                        @click="showSettingsForm({id:''})">Add Employee Payment Settings</button>
                                </div>
                                <hr />
                                <div class="table-responsive">
                                    <br />
                                    <table class="table table-bordered table-striped w-100" id="settings_table">
                                        <thead>
                                            <tr>
                                                <th>@lang('messages.action')</th>
                                                <th width="16%">@lang('essentials::lang.hrm_payment_settings_title_date')</th>
                                                <th width="20%">@lang('essentials::lang.hrm_payment_settings_title_type')</th>
                                                <th width="15%">@lang('essentials::lang.expense_account')</th>
                                                <th width="15%">@lang('essentials::lang.liability_account')</th>
                                                <th width="15%">
                                                    Enter Amount in the "Amount Paid" column
                                                </th>

                                                <th width="20%">@lang('essentials::lang.hrm_payment_settings_title_remarks')</th>
                                                <th width="15%">@lang('essentials::lang.hrm_payment_settings_title_user')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="payment_system.length === 0">
                                                <td colspan="7" class="text-center">No Data available</td>
                                            </tr>
                                            <tr v-for="(setting,ind) in payment_system">
                                                <td>

                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-info dropdown-toggle btn-xs"
                                                            data-toggle="dropdown" aria-expanded="false">Actions<span
                                                                class="caret"></span><span class="sr-only">Toggle Dropdown
                                                            </span>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-left" role="menu"
                                                            x-placement="top-start"
                                                            style="position: absolute; transform: translate3d(0px, -140px, 0px); top: 0px; left: 0px; will-change: transform;">
                                                            <li>
                                                                <a @click="showSettingsForm(setting)">
                                                                    <i class="fa fa-edit"></i> Edit
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <a @click="deletePaymentSetting(ind)">
                                                                    <i class="fa fa-trash"></i> Delete
                                                                </a>
                                                            </li>

                                                        </ul>
                                                    </div>

                                                </td>
                                                <td>@{{ setting.datetime_entered }}</td>
                                                <td>@{{ setting.name }}</td>

                                                <td>
                                                    <template
                                                        v-if="setting.expense_account_id != undefined && setting.expense_account_id != null">
                                                        @{{ setting.expense_account_name }}
                                                    </template>
                                                </td>
                                                <td>
                                                    <template
                                                        v-if="setting.liability_account_id != undefined && setting.liability_account_id != null">
                                                        @{{ setting.liable_bank }}
                                                    </template>
                                                </td>

                                                <td>@{{ setting.need_amount == 0 ? "No" : "Yes" }}</td>
                                                <td>@{{ setting.remarks }}</td>
                                                <td>
                                                    @{{ loguser.first_name }}
                                                    @{{ loguser.last_name }}
                                                </td>

                                            </tr>

                                        </tbody>

                                    </table>

                                </div>
                            @endcomponent
                        </section>
                    </div>
                </div>
            </div>
        </div>


        <div id="editModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" @click="closeEdit">&times;</button>
                        <h4 class="modal-title">
                            Edit Employee Advance
                        </h4>
                    </div>
                    <div class="modal-body">
                        <b>EMPLOYEE: @{{ advance.name }}</b>
                        <hr />
                        <div class="form-group">
                            <label for="amount">@lang('essentials::lang.hrm_advance_title_amount')</label>
                            <input v-model="advance.amount" class="form-control" />
                        </div>
                        <div class="form-group">
                            <label for="payment_status">@lang('essentials::lang.hrm_advance_label_paid')</label>
                            <select v-model="advance.payment_status" class="form-select filter-select">
                                <template v-if="advance.payment_status==0">
                                    <option value="0">No</option>
                                </template>
                                <template v-else>
                                    <option value="1">Yes</option>
                                </template>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="amount_paid">@lang('essentials::lang.hrm_advance_title_paid')</label>
                            <input v-model="advance.amount_paid" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" @click="closeEdit">Close</button>
                        <button type="button" class="btn btn-primary" @click="saveAdvance">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="settingsFormModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" @click="closeSettingsForm">×</button>
                        <h4 class="modal-title">Employee Payment Setting</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="date">@lang('essentials::lang.hrm_payment_settings_title_date')</label>
                            <input readonly="readonly" value="{{ now()->format('Y-m-d H:i') }}" class="form-control"
                                name="datetime" type="text">
                        </div>
                        <div class="form-group">
                            <label for="payment_id">@lang('essentials::lang.hrm_payment_settings_title_type')</label>
                            <input type="text" v-model="setting.name" id="payment_id" class="form-control"
                                placeholder="Enter Payment Type">
                        </div>
                        <div class="form-group">
                            <label for="need_amount">Enter value in the amount column</label>
                            <select v-model="setting.need_amount" id="need_amount" class="form-control">
                                <template v-if="setting.need_amount==1">
                                    <option v-model="setting.need_amount" value="1">Yes</option>
                                </template>
                                <template v-else-if="setting.need_amount==0">
                                    <option v-model="setting.need_amount" value="0">No</option>
                                </template>
                                <template v-else>
                                    <option v-model="setting.need_amount" value="">Select</option>
                                </template>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="expense_account_id">@lang('essentials::lang.expense_account')</label>
                            <input type="hidden" v-model="setting.expense_account_id" id="expense_account_id">
                            <select id="expense_data" class="form-control select2"
                                onchange="getData('expense_data','expense_account_id')">
                                <template v-if="setting.expense_account_id==null">
                                    <option value="">Please Select</option>
                                </template>
                                <template v-else>
                                    <option :value="setting.expense_account_id" :key="setting.expense_account_id">
                                        @{{ setting.expense_account_name }}</option>
                                </template>
                                <option v-for="account in expense_accounts" :key="account.id" :value="account.id">
                                    @{{ account.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="liability_account_id">@lang('essentials::lang.liability_account')</label>
                            <input type="hidden" v-model="setting.liability_account_id" id="liability_account_id">
                            <select id="liable_data" class="form-control select2"
                                onchange="getData('liable_data','liability_account_id')">
                                <template v-if="setting.liability_account_id==null">
                                    <option value="">Please Select</option>
                                </template>
                                <template v-else>
                                    <option :value="setting.liability_account_id" :key="setting.liability_account_id">
                                        @{{ setting.liable_bank }}</option>
                                </template>
                                <option v-for="account in liability_accounts" :key="account.id"
                                    :value="account.id">
                                    @{{ account.name }}
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="employee_ledger">@lang('essentials::lang.hrm_payment_settings_ledger_setup_title')</label>
                            <select v-model="setting.employee_ledger" id="employee_ledger" class="form-control">
                                <template v-if="setting.employee_ledger==1">
                                    <option v-model="setting.employee_ledger" value="1">Yes</option>
                                </template>
                                <template v-else-if="setting.employee_ledger==0">
                                    <option v-model="setting.employee_ledger" value="0">No</option>
                                </template>
                                <template v-else>
                                    <option v-model="setting.employee_ledger" value="">Select</option>
                                </template>
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="remarks">@lang('essentials::lang.hrm_payment_settings_title_remarks')</label>
                            <input v-model="setting.remarks" id="remarks" class="form-control" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="checkbox" class="create_another" id="create_another" name="create_another"
                            value="1">
                        <button type="button" class="btn btn-default" @click="closeSettingsForm">Close</button>
                        <button type="button" class="btn btn-primary" @click="savePaymentSetting">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="viewModal" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" @click="closeView">&times;</button>
                        <h4 class="modal-title">
                            View Employee Advance
                        </h4>
                    </div>
                    <div class="modal-body">

                        <div class="card bg-light text-dark" style="border: 1px solid #D9D8D8;padding: 20px;height: 100%">
                            <div class="box-header with-border">

                                <h4 class="box-title"><i class="fas fa-account"></i> <b>EMPLOYEE:
                                        @{{ advance.name }}</b> </h4>
                            </div>
                            <div class="box-body p-10">
                                <table class="table no-margin">
                                    <thead>
                                        <tr>
                                            <td>
                                                <strong>Amount:
                                                </strong>
                                                <h4 class="text-success">
                                                    @{{ advance.amount }}
                                                </h4>
                                            </td>
                                            <td>
                                                <strong>Amount Paid:
                                                </strong>
                                                <h4 class="text-success">
                                                    @{{ advance.amount_paid }}
                                                </h4>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Salary Period Form:
                                                </strong>
                                                <h4 class="text-success">
                                                    @{{ advance.salary_period_start }}
                                                </h4>
                                            </td>
                                            <td>
                                                <strong>Salary Period To:
                                                </strong>
                                                <h4 class="text-success">
                                                    @{{ advance.salary_period_end }}
                                                </h4>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <strong>Date:
                                                </strong>
                                                <h4 class="text-success">
                                                    @{{ advance.datetime_entered }}
                                                </h4>
                                            </td>
                                            <td>
                                                <strong>Paid Now?:
                                                </strong>
                                                <h4 class="text-success">
                                                    <template v-if="advance.payment_status==1">
                                                        Yes
                                                    </template>
                                                    <template v-else>
                                                        No
                                                    </template>
                                                </h4>
                                            </td>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" @click="closeView">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


@endsection
@section('javascript')
    <script>
        // function for get select2 data by virtual it
        function getData(a, b) {
            var x = document.getElementById(a).value;
            document.getElementById(b).value = x;
        }

        $(document).ready(function() {
            $('.employees_search').select2({
                placeholder: "Search or select an employee",
                allowClear: true
            });
        });


        new Vue({
            el: '#app',
            delimiters: ['@{{ ', ' }}'], // For Laravel Blade templates
            data() {
                return {
                    location_id: @json($location_id),
                    advance: {},
                    setting: {
                        date: '',
                        payment_type: '',
                        liability_account_id: '',
                        expense_account_id: '',
                        remarks: '',
                        employee_ledger: '',
                        need_amount: ''
                    },
                    advances: {!! json_encode($advances) !!},
                    employees: {!! json_encode($employees) !!},
                    business: {!! json_encode($business) !!},
                    payment: {
                        period: '{{ $startdate }} to {{ $enddate }}',
                        date: '{{ $today }}',
                        paid: "yes",
                        check: '',
                        account: '',
                        payment_type_id: "",
                        payment_method_id: "",
                        post_dated_cheque: 0,
                        update_post_dated_cheque: 0
                    },
                    accounts_with_check: {!! json_encode($accounts_with_check) !!},
                    accounts: {!! json_encode($accounts) !!},
                    payment_method: {!! json_encode($paymentMethod) !!},
                    payment_method_merged: {!! json_encode($paymentMethod) !!},
                    expense_accounts: {!! json_encode($expenseAccounts) !!},
                    liability_accounts: {!! json_encode($liabilityAccounts) !!},
                    payment_system: {!! json_encode($paymentType) !!},
                    loguser: {!! json_encode($users) !!},
                    settings: {!! json_encode($settings) !!},
                    searchQuery: '',
                    universalSearch: '',
                    advanceFilterDate: ''
                }
            },
            computed: {
                totalAmount() {
                    return this.employees.reduce((acc, curr) => acc + parseFloat(curr.amount), 0);
                },


                totalAmountPaid() {
                    return this.employees.reduce((acc, curr) => acc + parseFloat(curr.amount_paid), 0);
                },
                showPaidAmount() {
                    return this.payment.paid == "yes";
                },
                showPdChequeOptions() {
                    const methodId = this.payment.payment_method_id;
                    if (!methodId) {
                        return false;
                    }

                    const method = this.payment_method_merged.find(m => m.id == methodId);
                    if (!method) {
                        return false;
                    }

                    const name = (method.name || '').toString().toLowerCase();

                    // Show PD cheque options when:
                    // - selected method is from Bank group (bank_transfer), OR
                    // - method name contains 'cheque'
                    return method.group === 'bank_transfer' || name.includes('cheque');
                },
                showCheck() {
                    return this.accounts_with_check.includes(this.payment.account);
                },
                filteredAccounts() {
                    if (!this.accounts || this.accounts.length === 0) {
                        console.warn("⚠ No accounts found! Check backend response.");
                        return [];
                    }

                    // Extract first array if accounts is nested
                    let accountsList = Array.isArray(this.accounts[0]) ? this.accounts[0] : this.accounts;

                    return accountsList
                        .filter(acc => acc.account_type_id == 8) // ✅ Filter Current Liability accounts
                        .filter(acc => acc.name.toLowerCase().includes(this.searchQuery.toLowerCase()));
                },
                filteredAdvances() {
                    return this.advances.filter(adv => {

                        console.log("DB:", adv.datetime_entered, "Selected:", this.advanceFilterDate);
                        let matchesDate = true;
                        if (this.advanceFilterDate) {
                            // extract only YYYY-MM-DD from datetime_entered
                            const advDate = adv.datetime_entered ? adv.datetime_entered.substring(0, 10) :
                                '';
                            matchesDate = advDate === this.advanceFilterDate;
                        }

                        let matchesSearch = true;
                        if (this.universalSearch) {
                            const searchTerm = this.universalSearch.toLowerCase();
                            const employee_no = adv.employee_no?.toString().toLowerCase() || '';
                            const e_payment_no = adv.e_payment_no?.toString().toLowerCase() || '';
                            const name = adv.name?.toString().toLowerCase() || '';
                            const payment_type_name = adv.payment_type_name?.toString().toLowerCase() || '';
                            const payment_method_name = adv.payment_method_name?.toString().toLowerCase() ||
                                '';
                            const check_no = adv.check_no?.toString().toLowerCase() || '';

                            matchesSearch =
                                employee_no.includes(searchTerm) ||
                                e_payment_no.includes(searchTerm) ||
                                name.includes(searchTerm) ||
                                payment_type_name.includes(searchTerm) ||
                                payment_method_name.includes(searchTerm) ||
                                check_no.includes(searchTerm);
                        }

                        return matchesDate && matchesSearch;
                    });
                    // if (!this.universalSearch) {
                    // 	return this.advances;
                    // }

                    // const searchTerm = this.universalSearch.toLowerCase();
                    // return this.advances.filter(adv => 
                    // 	(adv.employee_no && adv.employee_no.toLowerCase().includes(searchTerm)) ||
                    // 	(adv.e_payment_no && adv.e_payment_no.toLowerCase().includes(searchTerm)) ||
                    // 	(adv.name && adv.name.toLowerCase().includes(searchTerm)) ||
                    // 	(adv.payment_type_name && adv.payment_type_name.toLowerCase().includes(searchTerm)) ||
                    // 	(adv.payment_method_name && adv.payment_method_name.toLowerCase().includes(searchTerm)) ||
                    // 	(adv.check_no && adv.check_no.toLowerCase().includes(searchTerm))
                    // );
                }
            },
            mounted() {

                this.$nextTick(() => {
                    this.initSettingsTable(); // initialize on load
                    this.loadAllPaymentMethods();
                });

                // console.log("✅ Accounts Data in Vue:", this.accounts);
                // console.log("settings data in Vue:", this.settings);
                // console.log("liabilityAccount Name data in Vue:", this.liabilityAccount);
                const dateRangeSettings = {
                    locale: {
                        format: moment_date_format,
                        applyLabel: 'Apply',
                        cancelLabel: 'Cancel',
                        customRangeLabel: 'Custom Range',
                    },
                    startDate: moment().subtract(29, 'days'),
                    endDate: moment(),
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [
                            moment().subtract(1, 'month').startOf('month'),
                            moment().subtract(1, 'month').endOf('month')
                        ],
                        'This month last year': [
                            moment().subtract(1, 'year').startOf('month'),
                            moment().subtract(1, 'year').endOf('month')
                        ],
                        'This Year': [moment().startOf('year'), moment().endOf('year')],
                        'Last Year': [
                            moment().subtract(1, 'year').startOf('year'),
                            moment().subtract(1, 'year').endOf('year')
                        ],
                        'Current financial year': [
                            moment().month() >= 6 ? moment().month(6).startOf('month') : moment().subtract(
                                1, 'year').month(6).startOf('month'),
                            moment().month() >= 6 ? moment().add(1, 'year').month(5).endOf('month') :
                            moment().month(5).endOf('month')
                        ],
                        'Last financial year': [
                            moment().month() >= 6 ? moment().subtract(1, 'year').month(6).startOf('month') :
                            moment().subtract(2, 'year').month(6).startOf('month'),
                            moment().month() >= 6 ? moment().month(5).endOf('month') : moment().subtract(1,
                                'year').month(5).endOf('month')
                        ],
                        'Custom Date Range': [moment().subtract(7, 'days'), moment()] // Default dummy range
                    }
                };
                $('#date_range').daterangepicker(
                    dateRangeSettings,
                    function (start, end) {
                        $('#date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                        advances_table.ajax.reload();
                    }
                );
                $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
                    $('#date_range').val('');
                    advances_table.ajax.reload();
                });

                const self2 = this; // reference to Vue instance

                // initialize flatpickr but do not open automatically
                self2.fp = flatpickr("#advance_date", {
                    onChange: function(selectedDates, dateStr) {
                        self2.advanceFilterDate = dateStr; // populate input
                    },
                    clickOpens: false // prevent input click from opening
                });

                // open datepicker only when icon is clicked
                document.getElementById("advance_date_icon").addEventListener("click", function() {
                    self2.fp.open();
                });


                $('.nav-tabs a').click(function(e) {
                    e.preventDefault();
                    $(this).tab('show');
                });


                $(this.$refs.daterange).daterangepicker(dateRangeSettings, (start, end, label) => {

                    if (label == 'Custom Date Range') {
                        // Show the modal for manual input						
                        $('.custom_date_typing_modal').modal('show');
                    } else {
                        // Set the payment period based on the selected range
                        this.payment.salary_period =
                            `${start.format('YYYY-MM-DD')} to ${end.format('YYYY-MM-DD')}`;
                    }



                });
                const vm = this; // Vue instance reference
                $('#custom_date_apply_button').on('click', function() {
                    // Get the values from the custom date inputs
                    // Format them into a date string			

                    let startDate = $('#custom_date_from_year1').val() + $('#custom_date_from_year2')
                        .val() + $('#custom_date_from_year3').val() + $('#custom_date_from_year4').val() +
                        "-" +
                        $('#custom_date_from_month1').val() + $('#custom_date_from_month2').val() + "-" + $(
                            '#custom_date_from_date1').val() + $('#custom_date_from_date2').val();
                    let endDate = $('#custom_date_to_year1').val() + $('#custom_date_to_year2').val() + $(
                        '#custom_date_to_year3').val() + $('#custom_date_to_year4').val() + "-" + $(
                        '#custom_date_to_month1').val() + $('#custom_date_to_month2').val() + "-" + $(
                        '#custom_date_to_date1').val() + $('#custom_date_to_date2').val();
                    if (startDate.length === 10 && endDate.length === 10) {
                        // Set the payment period based on the custom date inputs
                        vm.payment.salary_period = `${startDate} to ${endDate}`;

                        // Update the input visually
                        $(vm.$refs.daterange).val(vm.payment.salary_period);



                        // Hide the modal
                        $('.custom_date_typing_modal').modal('hide');
                    } else {
                        alert("Please select both start and end dates.");
                    }
                });



                $(this.$refs.date).datepicker();

                $(this.$refs.datetime).datetimepicker({
                    format: 'YYYY-MM-DD HH:mm:ss'
                }).on("dp.change", (e) => {
                    let dateString = e.date.format('YYYY-MM-DD HH:mm:ss');
                    this.setting.date = dateString;

                });

                $(this.$refs.select2).select2().on("change", (e) => {
                    this.setting.liability_account_id = $(e.target).val();
                });

                $('#employees_table').DataTable({
                    columns: [{
                            data: 'employee_no'
                        },
                        {
                            data: 'e_payment_no'
                        },
                        {
                            data: 'name'
                        },
                        null,
                        null
                    ]
                });

                $('#settings_table').DataTable({
                    columns: [
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null
                    ],
                    language: {
                        emptyTable: ''
                    }
                });
                $('#advances_table').DataTable({
                    columns: [
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null,
                        null
                    ],
                    order: [
                        [7, 'desc']
                    ], // Sort by date column (index 7) in descending order
                    pageLength: 25,
                    language: {
                        emptyTable: 'No data available'
                    }
                });

            },
            watch: {
                payment_system(newVal) {
                    // Rebuild DataTable every time payment_system changes
                    this.initSettingsTable();
                },


            },
            beforeDestroy() {

            },
            methods: {

                loadAllPaymentMethods() {
                    // Use the standard payment types (cash / bank / card / cheque)
                    // and then filter out only the special "Post dated Cheques" options.
                    const paymentTypes = ['cash', 'bank_transfer', 'card', 'cheque'];
                    const location_id = this.location_id ?? null;
                    const allOptions = [];

                    const fetchOptions = (type) => {
                        return $.ajax({
                            method: 'get',
                            url: '/finance/get-account-group-name-dp',
                            data: {
                                group_name: type,
                                location_id: location_id
                            },
                            contentType: 'html'
                        });
                    };

                    const promises = paymentTypes.map(type => fetchOptions(type));


                    Promise.all(promises).then((results) => {
                        // let mergedOptions = '';
                        // let selectAdded = false;
                        const seenValues = new Set();
                        const paymentOptions = [];
                        results.forEach((html, index) => {
                            const group = paymentTypes[index];
                            const options = $('<div>').html(html).find('option').toArray();

                            options.forEach(option => {
                                const $option = $(option);
                                const value = $option.attr('value');
                                const rawText = $option.text().trim();
                                const text = rawText.toLowerCase();

                                // Skip "Please Select"
                                if (text === 'please select') {
                                    return;
                                }

                                // Skip Post Dated Cheques payment methods only
                                if (text.includes('post dated cheque') || text.includes('post-dated cheque')) {
                                    return;
                                }

                                // Skip duplicate option values (except empty/undefined)
                                if (value && seenValues.has(value)) return;

                                if (value) seenValues.add(value);
                                paymentOptions.push({
                                    id: value,
                                    name: rawText,
                                    group: group
                                });
                                // mergedOptions += option.outerHTML;
                            });
                        });

                        // Update the select element with merged options
                        this.payment_method_merged = paymentOptions;
                        const select = this.$refs.methodSelect;
                        $(select)
                            .select2()
                            .on('change', () => {
                                console.log('Payment method changed:', $(select).val(), this.payment
                                    .period);

                                this.payment.payment_method_id = $(select).val();
                            });

                    }).catch((err) => {
                        console.error('Failed to load payment methods:', err);
                    });
                },
                initSettingsTable() {
                    this.$nextTick(() => {
                        const table = $('#settings_table');

                        // Destroy any existing instance
                        if ($.fn.DataTable.isDataTable(table)) {
                            table.DataTable().destroy();
                        }

                        // Wait a bit to ensure table DOM has updated
                        setTimeout(() => {
                            $('#settings_table').DataTable({
                                columns: [
                                    null,
                                    null,
                                    null,
                                    null,
                                    null,
                                    null
                                ],
                                language: {
                                    emptyTable: 'No data available'
                                }
                            });
                        }, 100);
                    });
                },
                reinitSettingsTable() {
                    this.$nextTick(() => {
                        // Check if DataTable is already initialized
                        if ($.fn.DataTable.isDataTable('#settings_table')) {
                            $('#settings_table').DataTable().clear().destroy();
                        }

                        this.$nextTick(() => {
                            $('#settings_table').DataTable({
                                columns: [
                                    null,
                                    null,
                                    null,
                                    null,
                                    null,
                                    null
                                ],
                                language: {
                                    emptyTable: ''
                                }
                            });
                        });
                    });
                },
                showSettingsForm(setting) {
                    if (setting != undefined) {
                        this.setting = {
                            ...setting
                        };
                    } else {
                        this.setting = {
                            date: '',
                            payment_type: '',
                            liability_account_id: '',
                            expense_account_id: '',
                            remarks: ''
                        };
                    }
                    this.$nextTick(() => {
                        $('#settingsFormModal').modal('show');
                    });
                },

                liabilityAccount(setting) {
                    if (!this.accounts.length) {
                        console.error("Accounts array is empty!");
                        return "N/A";
                    } else {
                        return 1;
                    }

                    const liabilityId = Number(setting.liability_account_id);

                    // Flatten accounts array
                    const flatAccounts = this.accounts.flat();
                    const matchedAccount = flatAccounts.find(item => Number(item.id) === liabilityId);

                    return matchedAccount ? matchedAccount.name : "N/A";
                },
                closeSettingsForm() {
                    $('#settingsFormModal').modal('hide');
                },
                updateDate(event) {
                    this.setting.date = event.target.value;
                },
                showEdit(adv) {
                    this.advance = adv;

                    this.$nextTick(() => {
                        $('#editModal').modal('show');
                    });
                },
                showView(adv) {
                    this.advance = adv;

                    this.$nextTick(() => {
                        $('#viewModal').modal('show');
                    });
                },
                closeEdit() {
                    this.advance = {};
                    console.log('closeEdit');
                    $('#editModal').modal('hide');
                },
                closeView() {
                    $('#viewModal').modal('hide');
                },
                manualUpdateAmount(ind) {
                    console.log(ind);
                },
                syncAmountPaid(emp) {
                    if (emp.amount_paid !== emp.amount) {
                        emp.amount_paid = emp.amount;
                    }
                },
                savePaymentSetting() {
                    var x = document.getElementById('liable_data').value;
                    // this.setting.liability_account_id = x;
                    this.setting.liability_account_id = x;
                    var y = document.getElementById('expense_data').value;
                    this.setting.expense_account_id = y;
                    var z = document.getElementById('payment_id').value;
                    // this.setting.liability_account_id = x;
                    this.setting.payment_type = z;
                    axios.post('{{ url('/') }}/hrm/advances/save-payment-settings', this.setting)
                        .then(res => {
                            if (res.status == 200) {
                                if (this.setting.id == undefined || this.setting.id == '') {
                                    this.settings.push(res.data.setting);
                                }

                                if (!$('#create_another').prop('checked')) {
                                    // Call the closeSettingsForm() function if the checkbox is not checked
                                    toastr.success('Payment setting saved successfully');
                                    //window.location.reload();
                                    // Call this after updating `this.settings`
                                    if (!this.setting.id) {
                                        this.payment_system.push(res.data.setting);
                                    } else {
                                        const i = this.payment_system.findIndex(s => s.id === res.data.setting
                                            .id);
                                        if (i !== -1) this.$set(this.payment_system, i, res.data.setting);
                                    }
                                    this.closeSettingsForm();
                                } else {
                                    // Reset form fields for creating another entry
                                    this.setting = {
                                        date: '',
                                        payment_type: '',
                                        liability_account_id: '',
                                        expense_account_id: '',
                                        remarks: ''
                                    };

                                    // Reset the search query for accounts
                                    this.searchQuery = '';

                                    if (this.$refs.datetime) {}
                                    if (!this.setting.id) {
                                        this.payment_system.push(res.data.setting);
                                    } else {
                                        const i = this.payment_system.findIndex(s => s.id === res.data.setting
                                            .id);
                                        if (i !== -1) this.$set(this.payment_system, i, res.data.setting);
                                    }
                                    toastr.success('Payment Setting saved. Add another new entry.');
                                    window.location.reload();
                                }
                            }
                        })
                        .catch(err => {
                            toastr.error('Failed to save payment settings');
                        });
                },
                deletePaymentSetting(index) {
                    axios.post('{{ url('/') }}/hrm/advances/remove-payment-settings', {
                        id: this.settings[index].id
                    }).then(res => {
                        if (res.status == 200) {
                            this.settings.splice(index, 1);
                        }
                        // Call the closeSettingsForm() function if the checkbox is not checked
                        toastr.success('Payment setting delete successfully');
                        window.location.reload();
                        this.closeSettingsForm();
                    }).catch(err => {
                        console.log(err);
                    });

                },

                saveAdvancePayments() {
                    axios.post('{{ url('/') }}/hrm/advances/save-advance-payments', {
                        advances: this.employees,
                        payment: this.payment
                    }).then(res => {
                        if (res.status === 200 && res.data?.createdAdvances) {
                            toastr.success('Payment saved successfully');

                            // 1. Update Vue data with new advances
                            const newAdvances = res.data.createdAdvances;
                            this.advances.unshift(...newAdvances); // Add to beginning to show latest first

                            // 2. Wait for DOM to update, then reinit DataTable
                            this.$nextTick(() => {
                                const table = $('#advances_table');

                                if ($.fn.DataTable.isDataTable(table)) {
                                    table.DataTable().clear().destroy();
                                }

                                // Wait briefly to let Vue fully re-render DOM
                                setTimeout(() => {
                                    $('#advances_table').DataTable({
                                        columns: [
                                            null, null, null, null, null,
                                            null, null, null, null, null
                                        ],
                                        order: [
                                            [7, 'desc']
                                        ], // Sort by date column in descending order
                                        pageLength: 25,
                                        language: {
                                            emptyTable: 'No data available'
                                        }
                                    });
                                }, 100);
                            });

                            // Clear form/table inputs
                            this.clearTable();
                        }
                    }).catch(err => {
                        console.error(err);
                        toastr.error('Failed to save payment');
                    });
                },
                saveAdvance() {
                    axios.post('{{ url('/') }}/hrm/advances/save-advance', this.advance).then(res => {
                        if (res.status == 200) {
                            toastr.success('Payment updated successfully');
                            window.location.reload();
                            this.closeView();
                        }
                    }).catch(err => {
                        console.log(err);
                    });
                },
                clearTable() {
                    for (let i in this.employees) {
                        this.employees[i].amount = 0;
                        this.employees[i].amount_paid = 0;
                    }
                },
                formatNumber(number, decimals = 2) {
                    let [integer, decimal] = number.toFixed(decimals).split('.');

                    integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                    return `${integer}.${decimal}`;
                },
                formatDate(dateString) {
                    if (!dateString) return '';

                    // Parse the date and return only the date part (YYYY-MM-DD)
                    const date = new Date(dateString);
                    return date.toISOString().split('T')[0];
                },
                showAddSettings() {

                }
            }
        });
    </script>
    <style>
        .filter-select {
            background-color: #fff;
            border: 1px solid #aaa;
            border-radius: 4px;
            height: 35px;
            width: 100%;
        }

        /* Right align amount columns */
        .text-right {
            text-align: right !important;
        }

        /* DataTables styling improvements */
        .dataTables_info {
            font-size: 14px;
            color: #666;
        }

        .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px 10px;
        }

        /* Table header styling */
        #advances_table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        /* Amount columns with proper spacing */
        #advances_table td.text-right {
            padding-right: 15px;
        }
    </style>
@endsection
