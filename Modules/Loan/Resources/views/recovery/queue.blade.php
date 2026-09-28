@extends('layouts.app')

@section('content')

<div class="container">

    <h2 class="mb-4">
        Recovery Work Queue
    </h2>

    <div class="card">

        <div class="card-body">

            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>
                            Application No
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            DPD
                        </th>

                        <th>
                            Bucket
                        </th>

                        <th>
                            Status
                        </th>

                    </tr>

                </thead>

                <tbody>

                    @foreach(
                        $assignments
                        as $assignment
                    )

                    <tr>

                        <td>
                            {{
                                optional(
                                    $assignment->loan
                                )->application_no
                            }}
                        </td>

                        <td>
                            {{
                                optional(
                                    optional(
                                        $assignment->loan
                                    )->customer
                                )->name
                            }}
                        </td>

                        <td>
                            {{
                                optional(
                                    $assignment->loan
                                )->dpd
                            }}
                        </td>

                        <td>
                            {{
                                optional(
                                    $assignment->loan
                                )->dpd_bucket
                            }}
                        </td>

                        <td>
                            {{
                                optional(
                                    $assignment->loan
                                )->status
                            }}
                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection