@extends('layouts.app')

@section('title', 'Customer Profile')

@section('content')

<section class="content-header">
    <h1>
        Customer Profile
    </h1>
</section>

<section class="content">

    <div class="row">

        <div class="col-md-12">

            <div class="box box-primary">

                <div class="box-header with-border">
                    <h3 class="box-title">
                        Customer Information
                    </h3>
                </div>

                <div class="box-body">

                    <div class="row">

                        <div class="col-md-4">
                            <strong>Customer Code</strong>
                            <br>
                            {{ $customer->contact_id ?? '-' }}
                        </div>

                        <div class="col-md-4">
                            <strong>Customer Name</strong>
                            <br>
                            {{ $customer->name ?? '-' }}
                        </div>

                        <div class="col-md-4">
                            <strong>Mobile</strong>
                            <br>
                            {{ $customer->mobile ?? '-' }}
                        </div>

                    </div>

                    <hr>

                    <div class="row">

                        <div class="col-md-4">
                            <strong>Email</strong>
                            <br>
                            {{ $customer->email ?? '-' }}
                        </div>

                        <div class="col-md-4">
                            <strong>NIC / Passport</strong>
                            <br>
                            {{ $customer->tax_number ?? '-' }}
                        </div>

                        <div class="col-md-4">
                            <strong>Credit Limit</strong>
                            <br>
                            {{ number_format($customer->credit_limit ?? 0, 2) }}
                        </div>

                    </div>

                    <hr>

                    <div class="row">

                        <div class="col-md-6">
                            <strong>Address</strong>
                            <br>
                            {{ $customer->address_line_1 ?? '' }}
                            {{ $customer->address_line_2 ?? '' }}
                            {{ $customer->city ?? '' }}
                        </div>

                        <div class="col-md-3">
                            <strong>Customer Group</strong>
                            <br>
                            {{ $customerGroup->name ?? '-' }}
                        </div>

                        <div class="col-md-3">
                            <strong>Assigned Officer</strong>
                            <br>
                            {{ $assignedOfficer->username ?? '-' }}
                        </div>

                    </div>

                    <hr>

                    <div class="row">

                        <div class="col-md-3">
                            <strong>Status</strong>
                            <br>
                            {{ !empty($customer->active) ? 'Active' : 'Inactive' }}
                        </div>

                        <div class="col-md-3">
                            <strong>Created Date</strong>
                            <br>
                            {{ optional($customer->created_at)->format('Y-m-d') }}
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

@endsection