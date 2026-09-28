@extends('layouts.app')

@section('title', 'Restructuring Requests')

@section('content')

<section class="content-header">

    <h1>
        Restructuring Requests
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Borrower Restructuring Workspace
            </h3>

        </div>

        <div class="box-body">

            <p>
                Borrower restructuring workspace initialized successfully.
            </p>

            <form method="POST"
                  action="{{ route('borrower.restructuring.store') }}">

                @csrf

                <div class="form-group">

                    <label>
                        Restructuring Request
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