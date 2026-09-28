@extends('layouts.app')

@section('title', 'Settlement Requests')

@section('content')

<section class="content-header">

    <h1>
        Settlement Requests
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Borrower Settlement Workspace
            </h3>

        </div>

        <div class="box-body">

            <p>
                Borrower settlement workspace initialized successfully.
            </p>

            <form method="POST"
                  action="{{ route('borrower.settlements.store') }}">

                @csrf

                <div class="form-group">

                    <label>
                        Settlement Request
                    </label>

                    <textarea
                        name="request_note"
                        class="form-control"
                        rows="5"
                    ></textarea>

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Submit Request
                </button>

            </form>

        </div>

    </div>

</section>

@endsection