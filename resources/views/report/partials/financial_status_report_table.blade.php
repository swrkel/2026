<table class="table table-bordered table-striped" id="financail_status_table">
                
                                    <thead>
                
                                    <tr>
                
                                        <th class="text-left"><span
                                                    style="background: #800080; padding: 5px 10px 5px 10px; color: #fff;">Financial
                                                                Status</span></th>
                
                                        @foreach($accounts as $one)
                                            <th>{{$one->name}}</th>
                                        @endforeach
                
                                    </tr>
                
                                    </thead>
                
                                    <tbody style="background-color: #FFF0D9">
                
                                    <tr>
                
                                        <td>Previous Day Balance</td>
                                        
                                        @foreach($accounts as $one)
                                            @if ($one->name == 'Credit Sales' || $one->name == 'Credit Sales Account')
                                                {{-- For Credit Sales, show only the Receivable A/C balance (previous_day_balance) without adding OB --}}
                                                <td>{{ @num_format($previous_day_balance[$one->id]) }}</td>
                                            @else
                                                <td>{{ @num_format($previous_day_balance[$one->id]+$OB[$one->id]) }}</td>
                                            @endif
                                        @endforeach
                                        
                                    </tr>
                
                                    <tr>
                
                                        <td>@lang('report.total_in')</td> 
                
                                        @foreach($accounts as $one)
                                            @if (in_array($one->name, ['Cash Account', 'Bank Account', 'Card', 'Cheques in Hand (Customer\'s)']))
                                                <td>{{ @num_format(0) }}</td>
                                            @else
                                                <td>{{ @num_format($debits[$one->id]) }}</td>
                                            @endif
                                        @endforeach
                
                                    </tr>
                
                
                                    <tr>
                
                                        <td>@lang('report.total_out')</td>
                                        
                                        @foreach($accounts as $one)
                                            <td>{{ @num_format($credits[$one->id]) }}</td>
                                        @endforeach
                
                                    </tr>
                                    
                                    <tr>
                
                                        <td>Balance</td>
                                        
                                        @foreach($accounts as $one)
                                            <td>{{ @num_format($previous_day_balance[$one->id] + $debits[$one->id] -$credits[$one->id]) }}</td>
                                        @endforeach
                                    </tr>
                
                                    
                                    </tbody>
                
                                </table>