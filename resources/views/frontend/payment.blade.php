@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="checkout-payment" class="container order-page-wrapper orders-theme">
  <ul class="breadcrumb">
    <h1>Payment</h1>
    <li><a href="{{ route('frontend.home') }}"><i class="fa fa-home"></i></a></li>
    <li><a href="{{ route('frontend.cart') }}">Shopping Cart</a></li>
    <li><a href="{{ route('frontend.checkout') }}">Checkout</a></li>
    <li><a href="{{ route('frontend.payment') }}">Payment</a></li>
  </ul>

  <div class="row equal-height">
    <div class="col-sm-7">
      <div class="order-sidebar">
        <h3>Selected Delivery Address</h3>
        <div class="address-card">
          <strong>{{ $selectedAddress->address_line_1 }}</strong><br>
          @if(!empty($selectedAddress->address_line_2))
            {{ $selectedAddress->address_line_2 }}<br>
          @endif
          {{ $selectedAddress->location }} - {{ $selectedAddress->pincode }}<br>
          Landmark: {{ $selectedAddress->landmark ?: 'N/A' }}
        </div>
      </div>
    </div>

    <div class="col-sm-5">
      <div class="order-details">
        <h3>Order Summary</h3>
        <div class="amount-card">
          <div class="amount-row">
            <div class="label">Sub-Total</div>
            <div class="value">&#8377;{{ number_format($subTotal, 2) }}</div>
          </div>
          <div class="amount-row">
            <div class="label">Delivery Charges</div>
            <div class="value">&#8377;{{ number_format($deliveryCharge, 2) }}</div>
          </div>
          <div class="amount-row">
            <div class="label">Packing Charges</div>
            <div class="value">&#8377;{{ number_format($packingCharge, 2) }}</div>
          </div>
          <div class="amount-row">
            <div class="label">Other Charges</div>
            <div class="value">&#8377;{{ number_format($otherCharge, 2) }}</div>
          </div>
          <div class="amount-row">
            <div class="label">Total</div>
            <div class="value">&#8377;{{ number_format($total, 2) }}</div>
          </div>
        </div>

        <button id="continue-payment" class="btn btn-primary btn-block" type="button">
          Continue Payment
        </button>
        <p id="payment-status" class="text-center payment-status"></p>
      </div>
    </div>
  </div>
</div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
  (function () {
    var payBtn = document.getElementById('continue-payment');
    var statusEl = document.getElementById('payment-status');

    function setStatus(message, isError) {
      statusEl.style.display = 'block';
      statusEl.className = isError ? 'text-danger text-center' : 'text-success text-center';
      statusEl.textContent = message;
    }

    payBtn.addEventListener('click', function () {
      payBtn.disabled = true;
      setStatus('Starting payment...', false);

      fetch("{{ route('frontend.payment.create_order') }}", {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': "{{ csrf_token() }}",
          'Accept': 'application/json'
        }
      })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.order_id) {
          throw new Error(data.message || 'Unable to create payment order.');
        }

        var options = {
          key: data.key,
          amount: data.amount,
          currency: data.currency,
          name: 'JC Mart',
          description: 'Order Payment',
          order_id: data.order_id,
          handler: function (response) {
            fetch("{{ route('frontend.payment.verify') }}", {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}",
                'Accept': 'application/json'
              },
              body: JSON.stringify(response)
            })
            .then(function (res) {
              return res.json().then(function (data) {
                return { ok: res.ok, data: data };
              });
            })
            .then(function (payload) {
              var message = payload.data && payload.data.message ? payload.data.message : 'Payment updated.';
              setStatus(message, !payload.ok);
            })
            .catch(function () {
              setStatus('Payment verification failed. Please contact support.', true);
            })
            .finally(function () {
              payBtn.disabled = false;
            });
          }
        };

        var rzp = new Razorpay(options);
        rzp.on('payment.failed', function () {
          setStatus('Payment failed. Please try again.', true);
          payBtn.disabled = false;
        });
        rzp.open();
      })
      .catch(function (err) {
        setStatus(err.message || 'Payment could not be started.', true);
        payBtn.disabled = false;
      });
    });

  })();
</script>

@include('frontend.footer')
