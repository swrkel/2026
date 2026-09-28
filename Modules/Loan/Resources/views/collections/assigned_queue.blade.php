@extends('layouts.app')

@section('title', 'Assigned Recovery Queue')

@section('content')

<section class="content-header">

    <h1>
        Assigned Recovery Queue
    </h1>

</section>

<section class="content">

    <div class="row">

        <div class="col-md-3">

            <div class="info-box bg-red">

                <span class="info-box-icon">
                    <i class="fa fa-warning"></i>
                </span>

                <div class="info-box-content">

                    <span class="info-box-text">
                        Assigned Accounts
                    </span>

                    <span class="info-box-number">
                        {{ $assignments->count() }}
                    </span>

                </div>

            </div>

        </div>

    </div>

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Recovery Officer Work Queue
            </h3>

        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>

                    <tr>

                        <th>
                            Loan ID
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Assignment Status
                        </th>

                        <th>
                            Assigned Date
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($assignments as $assignment)

                        <tr>

                            <td>
                                {{ optional($assignment->loan)->id }}
                            </td>

                            <td>
                                {{ optional(optional($assignment->loan)->customer)->name }}
                            </td>

                            <td>
                                {{ ucfirst($assignment->status) }}
                            </td>

                            <td>
                                {{ $assignment->created_at }}
                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="4"
                                class="text-center">

                                No assigned recovery accounts found.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</section>

@endsection