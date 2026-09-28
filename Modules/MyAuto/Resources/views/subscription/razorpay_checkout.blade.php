<form action="{{ route('myauto.subscription.callback') }}" method="POST">
    @csrf
    <script
        src="https://checkout.razorpay.com/v1/checkout.js"
        data-key="{{ env('RAZORPAY_KEY_ID') }}"
        data-amount="{{ $order['amount'] }}"
        data-currency="INR"
        data-order_id="{{ $order['id'] }}"
        data-buttontext="Pay Now"
        data-name="My Auto Subscription"
        data-description="Subscription Renewal"
        data-theme.color="#28a745">
    </script>

    <input type="hidden" name="order_id" value="{{ $order['id'] }}">
</form>
