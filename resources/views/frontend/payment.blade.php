@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="checkout-payment" class="container">
  <ul class="breadcrumb">
    <h1>Payment</h1>
    <li><a href="{{ route('frontend.home') }}"><i class="fa fa-home"></i></a></li>
    <li><a href="{{ route('frontend.cart') }}">Shopping Cart</a></li>
    <li><a href="{{ route('frontend.checkout') }}">Checkout</a></li>
    <li><a href="{{ route('frontend.payment') }}">Payment</a></li>
  </ul>

  <div class="row">
    <div class="col-sm-7">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title">Selected Delivery Address</h4>
        </div>
        <div class="panel-body">
          <p style="margin:0;">
            <strong>{{ $selectedAddress->address_line_1 }}</strong><br>
            @if(!empty($selectedAddress->address_line_2))
              {{ $selectedAddress->address_line_2 }}<br>
            @endif
            {{ $selectedAddress->location }} - {{ $selectedAddress->pincode }}<br>
            Landmark: {{ $selectedAddress->landmark ?: 'N/A' }}
          </p>
        </div>
      </div>
    </div>

    <div class="col-sm-5">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title">Order Summary</h4>
        </div>
        <div class="panel-body" style="padding:0;">
          <table class="table table-bordered" style="margin:0;">
            <tbody>
              <tr>
                <td><strong>Sub-Total</strong></td>
                <td class="text-right">&#8377;{{ number_format($subTotal, 2) }}</td>
              </tr>
              <tr>
                <td>Delivery Charges</td>
                <td class="text-right">&#8377;{{ number_format($deliveryCharge, 2) }}</td>
              </tr>
              <tr>
                <td>Packing Charges</td>
                <td class="text-right">&#8377;{{ number_format($packingCharge, 2) }}</td>
              </tr>
              <tr>
                <td>Other Charges</td>
                <td class="text-right">&#8377;{{ number_format($otherCharge, 2) }}</td>
              </tr>
              <tr>
                <td><strong>Total</strong></td>
                <td class="text-right"><strong>&#8377;{{ number_format($total, 2) }}</strong></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="panel panel-default">
        <div class="panel-body">
          <button id="continue-payment" class="btn btn-primary btn-block" type="button">
            Continue Payment
          </button>
          <p id="payment-status" class="text-center" style="margin:10px 0 0; display:none;"></p>
        </div>
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
