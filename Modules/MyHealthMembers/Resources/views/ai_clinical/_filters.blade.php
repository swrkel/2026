<form method="GET" class="form-inline" style="margin-bottom: 15px;">
    <div class="form-group">
        <label>Date From</label>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control input-sm">
    </div>
    <div class="form-group">
        <label>Date To</label>
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control input-sm">
    </div>
    <div class="form-group">
        <label>Member Code</label>
        <input type="text" name="member_code" value="{{ request('member_code') }}" class="form-control input-sm" placeholder="Member Code">
    </div>
    <div class="form-group">
        <label>Severity</label>
        <select name="severity" class="form-control input-sm">
            <option value="">All</option>
            @foreach(['critical' => 'Critical', 'high' => 'High', 'medium' => 'Medium', 'low' => 'Low'] as $value => $label)
                <option value="{{ $value }}" {{ request('severity') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control input-sm">
            <option value="">All</option>
            @foreach(['open' => 'Open', 'acknowledged' => 'Acknowledged', 'closed' => 'Closed'] as $value => $label)
                <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> Search</button>
    <a href="{{ url()->current() }}" class="btn btn-default btn-sm">Reset</a>
</form>
