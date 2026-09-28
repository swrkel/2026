                <div class="row check_group">
                    <div class="col-md-1">
                        <h4><label>Users with this Role</label></h4>
                    </div>
                    <div class="col-md-2">
                        {{-- Just a list --}}
                    </div>
                    <div class="col-md-9">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($users as $u)
                                        <tr>
                                            <td>{{ $u->first_name }} {{ $u->last_name }}</td>
                                            <td>{{ $u->username }}</td>
                                            <td>{{ $u->email }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <hr class="blue-hr">
