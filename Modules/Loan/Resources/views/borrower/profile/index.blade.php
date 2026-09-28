@extends('layouts.app')

@section('title', 'My Profile')

@section('content')

<section class="content-header">

    <h1>
        Borrower Profile
    </h1>

</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">

            <h3 class="box-title">
                Borrower Profile Management
            </h3>

        </div>

        <div class="box-body">

            <p>
                Borrower profile workspace initialized successfully.
            </p>

            <form method="POST"
                  action="{{ route('borrower.profile.update') }}">

                @csrf

                <div class="form-group">

                    <label>
                        Full Name
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="name"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Mobile Number
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        name="mobile"
                    >

                </div>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Profile
                </button>

            </form>

        </div>

    </div>

</section>

@endsection