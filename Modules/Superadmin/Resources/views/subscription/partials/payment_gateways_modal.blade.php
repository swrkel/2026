<div class="modal fade" id="payment_gateways_modal" tabindex="-1" role="dialog" aria-labelledby="paymentGatewaysModalLabel" style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(8px); z-index: 1060;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2); overflow: hidden;">
            <div class="modal-header" style="background: linear-gradient(135deg, #38b2ac, #319795); color: white; padding: 20px; border-bottom: none;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 0.9; font-size: 28px; margin-top: -5px;"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="paymentGatewaysModalLabel" style="font-weight: 800; font-size: 22px; letter-spacing: 0.5px;">
                    <i class="fa fa-credit-card-alt" style="margin-right: 10px;"></i> Select Payment Gateway
                </h4>
            </div>
            
            <div class="modal-body" style="background: #f7fafc; padding: 30px;">
                <div class="text-center" style="margin-bottom: 25px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                    <span style="font-size: 14px; text-transform: uppercase; color: #a0aec0; font-weight: 700; letter-spacing: 1px;">Selected Subscription</span>
                    <h3 style="margin-top: 5px; font-weight: 800; color: #2d3748; font-size: 26px;">{{ $package->name }}</h3>
                    <div style="font-size: 20px; font-weight: 700; color: #319795; margin-top: 5px;">
                        Rs {{ number_format($package->price, 2) }} <small style="color: #718096; font-size: 14px;">/ {{ $package->interval_count }} {{ ucfirst($package->interval) }}</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="list-group" style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
                            @foreach($gateways as $k => $v)
                                <div class="list-group-item" style="padding: 20px; border: 1px solid #edf2f7; margin-bottom: 10px; border-radius: 10px; background: white; transition: all 0.2s ease;">
                                    <div class="row">
                                        <div class="col-xs-12" style="margin-bottom: 10px;">
                                            <strong style="font-size: 16px; color: #4a5568;"><i class="fa fa-chevron-right" style="color: #319795; margin-right: 8px; font-size: 12px;"></i> @lang('superadmin::lang.pay_via', ['method' => $v])</strong>
                                        </div>
                                        <div class="col-xs-12">
                                            <div id="paymentdiv_{{$k}}">
                                                @php
                                                    $view = 'superadmin::subscription.partials.pay_'.$k;
                                                @endphp
                                                @includeIf($view)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer" style="background: #edf2f7; border-top: none; padding: 15px 30px; border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px; font-weight: 700; padding: 8px 20px; color: #4a5568;">Cancel</button>
            </div>
        </div>
    </div>
</div>
