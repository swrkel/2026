@extends('layouts.app')

@section('title', 'Finance Notifications')

@section('content')

<section class="content-header">
    <h1>
        Finance Notifications
    </h1>
</section>

<section class="content">

    <div class="box box-primary">

        <div class="box-header with-border">
            <h3 class="box-title">
                Notification Filters
            </h3>
        </div>

        <div class="box-body">

            <form method="GET">

                <div class="row">

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>

                            <select name="location_id" class="form-control">
                                <option value="all">All Branches</option>

                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ request()->location_id == $id ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Priority</label>

                            <select name="priority" class="form-control">
                                <option value="">All</option>
                                <option value="low" {{ request()->priority == 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ request()->priority == 'medium' ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ request()->priority == 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ request()->priority == 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Status</label>

                            <select name="status" class="form-control">
                                <option value="">All</option>
                                <option value="unread" {{ request()->status == 'unread' ? 'selected' : '' }}>Unread</option>
                                <option value="read" {{ request()->status == 'read' ? 'selected' : '' }}>Read</option>
                                <option value="archived" {{ request()->status == 'archived' ? 'selected' : '' }}>Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>

                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fa fa-search"></i>
                                Filter
                            </button>
                        </div>
                    </div>

                </div>

            </form>

        </div>

    </div>

    <div class="box box-info">

        <div class="box-header with-border">
            <h3 class="box-title">
                Notification Center
            </h3>
        </div>

        <div class="box-body table-responsive">

            <table class="table table-bordered table-striped">

                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Branch</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($notifications as $notification)
                        <tr>
                            <td>{{ $notification->created_at }}</td>
                            <td>{{ optional($notification->location)->name }}</td>
                            <td>{{ optional($notification->user)->username }}</td>
                            <td>{{ $notification->notification_type }}</td>
                            <td>
                                <span class="label label-{{ $notification->priority == 'critical' ? 'danger' : ($notification->priority == 'high' ? 'warning' : 'primary') }}">
                                    {{ ucfirst($notification->priority) }}
                                </span>
                            </td>
                            <td>{{ $notification->title }}</td>
                            <td>{{ $notification->message }}</td>
                            <td>
                                <span class="label label-info">
                                    {{ ucfirst($notification->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>

            </table>

            <div class="text-center">
                {{ $notifications->links() }}
            </div>

        </div>

    </div>

</section>

@endsection