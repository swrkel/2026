<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">
<style>
         .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .business-title {
            text-align: center;
            flex-grow: 1; /* Takes remaining space to center properly */
        }
        .report-date {
            text-align: right;
            width: 200px; /* Fixed width for date */
        }
        @media print {
            @page {
                size: portrait;
                margin: 0.5cm;
            }
            body {
                writing-mode: horizontal-tb;
            }
            table {
                width: 100%;
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
        body {
            font-family: Arial, sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
<!-- Main content -->
<div class="content">
  <div class="header-container">
        <div class="business-title">
            <h3>{{ $business_locations }}</h3>
        </div>
        <div class="report-date">
            <p>Date: {{ $date }}</p>
        </div>
    </div>
        <div class="col-md-12">
        <div class="col-md-4">
        <h4 style="color: darkblue;">Service Details</h4>        
        </div>
       
            </div>

    </div>
     <div class="row" id ="print_content">
    <div class="table-responsive" >
      <table class="table table-bordered table-striped" id="summary_table">
    <thead>
        <tr>
            <th class="align-middle text-center">@lang('autorepairservices::lang.job_sheet_no')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.bill_no')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.customer')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.vehicle_no')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.service')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.bill_amount')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.cash')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.card')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.credit')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.note')</th>
            <th class="align-middle text-center">@lang('autorepairservices::lang.assigned_staff')</th>
        </tr>
    </thead>
    <tbody>
        @foreach($processedData as $data)
        <tr>
            <td class="align-middle text-center">{{ $data['job_sheet_no'] }}</td>
            <td class="align-middle text-center">{{ $data['bill_no'] }}</td>
            <td class="align-middle text-center">{{ $data['customer'] }}</td>
            <td class="align-middle text-center">{{ $data['vehicle_name'] }}</td>
            <td class="align-middle text-center">{{ $data['service_type'] }}</td>
            <td class="align-middle text-center">{{ $data['final_total'] }}</td>
            <td class="align-middle text-center">{{ $data['cash'] }}</td>
            <td class="align-middle text-center">{{ $data['card'] }}</td>
            <td class="align-middle text-center">{{ $data['credit'] }}</td>
            <td class="align-middle text-center">{{ $data['note'] }}</td>
            <td class="align-middle text-center">{{ $data['service_staff'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
    </div>
    <div class="row">  
        <div class="col-md-12">
            <div class="col-md-6">
            <h4 style="color: darkblue;" > POS Sales Detail</h4>           
            </div>
            <div class="col-md-2">
            <h4 style="color: darkblue;">Payment Summary</h4>           
            </div>
            <div class="col-md-4">
            <table class="table table-bordered table-striped"> 
            <tr>
                <th>Service</th>
                <th>POS</th>
                <th>Grand Total</th>
            </tr>
            <tr>
                <td>{{ $paymentSummary['total_bill_amount'] ?? 0 }}</td>
                <td>{{ $paymentSummary['total_bill_amount'] ?? 0 }}</td>
                <td>{{ $paymentSummary['grand_total'] ?? 0 }}</td>
            </tr>
            </table>        
            </div>        
        </div>
        </div>
        <div class="col-md-12">
            <div class="col-md-6">
            <table class="table table-bordered table-striped"> 
    <thead>
        <tr>
            <th>Bill No</th>
            <th>Bill Amount</th>
            <th>Cash</th>
            <th>Card</th>
            <th>Credit</th>
        </tr>
    </thead>

    <tbody id="salesData">
        @foreach($processedData as $data)
        <tr>
            <td>{{ $data['bill_no'] }}</td>
            <td>{{ $data['final_total'] }}</td>
            <td>{{ $data['cash'] }}</td>
            <td>{{ $data['card'] }}</td>
            <td>{{ $data['credit'] }}</td>
        </tr>
        @endforeach
    </tbody>

    <tfoot>
        <tr id="salesFooter">
            <td><strong>Total</strong></td>
            <td id="salesbill">{{ $paymentSummary['total_bill_amount'] ?? 0 }}</td>
            <td id="salescash">{{ $paymentSummary['total_cash'] ?? 0 }}</td>
            <td id="salescard">{{ $paymentSummary['total_card'] ?? 0 }}</td>
            <td id="salescredit">{{ $paymentSummary['total_credit'] ?? 0 }}</td>
        </tr>
    </tfoot>
</table>
               
            </div>
            <div class="col-md-2">
            <h4 style="color: darkblue;  "> Total Sales Today</h4> 
            <table class="table table-bordered table-striped"> 
            <tr>
            <th></th>
            <th>Amount</th>        
            </tr>
            <tr>
            <td >Expense</td>
            <td id="expense">0.00</td>
            
            </tr>
            <tr>
            <td>Credit</td>
            <td id="credit">{{ $paymentSummary['total_credit'] ?? 0 }}</td>
            
            </tr>
            <tr>
            <td>Card</td>
            <td id="card">{{ $paymentSummary['total_card'] ?? 0 }}</td>
            
            </tr>
            <tr>
            <td>Cash</td>
            <td id="cash">{{ $paymentSummary['total_cash'] ?? 0 }}</td>
            
            </tr>
            <tr>
            <td>Total</td>
            <td id="totals">{{ $paymentSummary['grand_total'] ?? 0 }}</td>
            
            </tr>
            </table>          
            </div>
            <div class="col-md-4">
            <h4 style="color: darkblue;">Expense Summary</h4>
            <table class="table table-bordered table-striped" style="width: 100%;"> 
        <thead>
            <tr>
                <th style="width: 70%;">Expense Category</th>
                <th style="width: 30%;">Amount</th>
            </tr>
        </thead>
        <tbody id="expenseBody">
            <tr>
                <td colspan="2"></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td><strong>Total:</strong></td>
                <td id="totalExpenses">0.00</td>
            </tr>
        </tfoot>
    </table>
        
            </div>        
        </div>
   
    </div>
    </div> 
</div>