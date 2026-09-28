<div class="box box-primary customers-dashboard-box">
    <div class="box-header with-border">
        <h3 class="box-title">Recent Customers</h3>
        <div class="box-tools pull-right">
            @if(\Illuminate\Support\Facades\Route::has('customers.index'))
                <a href="{{ route('customers.index') }}" class="btn btn-box-tool">View Register</a>
            @endif
        </div>
    </div>
    <div class="box-body table-responsive no-padding">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Credit Limit</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recent_customers as $customer)
                    <tr>
                        <td>{{ $customer->contact_id }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->mobile }}</td>
                        <td>{{ $customer->email }}</td>
                        <td class="customers-money">{{ number_format((float) ($customer->credit_limit ?? 0), 2) }}</td>
                        <td>
                            @if((int) $customer->active === 1)
                                <span class="customers-status-badge customers-status-active">Active</span>
                            @else
                                <span class="customers-status-badge customers-status-inactive">Inactive</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
