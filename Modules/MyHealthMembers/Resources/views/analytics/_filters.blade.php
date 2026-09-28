<form method="GET" class="form-inline" style="margin-bottom: 15px;">
    <div class="form-group">
        <label>Date From</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from', $analytics['filters']['date_from'] ?? '') }}">
    </div>
    <div class="form-group" style="margin-left: 10px;">
        <label>Date To</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to', $analytics['filters']['date_to'] ?? '') }}">
    </div>
    <button type="submit" class="btn btn-primary" style="margin-left: 10px;"><i class="fa fa-search"></i> Filter</button>
    <button type="button" class="btn btn-default" onclick="window.print();"><i class="fa fa-print"></i> Print</button>
</form>
