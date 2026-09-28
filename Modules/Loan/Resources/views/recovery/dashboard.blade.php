@extends('layouts.app')

@section('content')

<div class="container">

    <h2 class="mb-4">
        Recovery Dashboard
    </h2>

    <div class="row">

        <div class="col-md-3">
            <div class="card p-3">
                <h5>Assigned Loans</h5>
                <h2>
                    {{ $assignedLoans }}
                </h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3">
                <h5>Overdue Loans</h5>
                <h2>
                    {{ $overdueLoans }}
                </h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3">
                <h5>NPA Loans</h5>
                <h2>
                    {{ $npaLoans }}
                </h2>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card p-3">
                <h5>Broken Promises</h5>
                <h2>
                    {{ $brokenPromises }}
                </h2>
            </div>
        </div>

    </div>

</div>

@endsection