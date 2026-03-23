@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="checkout-checkout" class="container order-page-wrapper orders-theme">
  <ul class="breadcrumb">
    <h1>Checkout</h1>
    <li><a href="{{ route('frontend.home') }}"><i class="fa fa-home"></i></a></li>
    <li><a href="{{ route('frontend.cart') }}">Shopping Cart</a></li>
    <li><a href="{{ route('frontend.checkout') }}">Checkout</a></li>
  </ul>

  <div class="row">
    <div id="content" class="col-sm-12 checkout">
      @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif

      <form method="POST" action="{{ route('frontend.checkout.proceed') }}" id="checkout-address-form">
        @csrf
        <input type="hidden" name="selected_address_id" id="selected_address_id" value="{{ old('selected_address_id', '') }}">

        <div class="row equal-height">
          <div class="col-sm-7">
            <div class="order-sidebar">
              <h3>Delivery Address</h3>

              @if($errors->has('selected_address_id'))
                <div class="alert alert-danger">{{ $errors->first('selected_address_id') }}</div>
              @endif

              @if($addresses->isEmpty())
                <p>No delivery address found for your account.</p>
              @else
                <div class="address-list scrollable">
                  @foreach($addresses as $address)
                    @php
                      $isChecked = (string) old('selected_address_id') === (string) $address->id;
                    @endphp
                    <label class="address-option">
                      <input
                        type="checkbox"
                        class="address-checkbox"
                        value="{{ $address->id }}"
                        {{ $isChecked ? 'checked' : '' }}
                      >
                      <span class="address-card">
                        <strong>{{ $address->address_line_1 }}</strong><br>
                        @if(!empty($address->address_line_2))
                          {{ $address->address_line_2 }}<br>
                        @endif
                        {{ $address->location }} - {{ $address->pincode }}<br>
                        Landmark: {{ $address->landmark ?: 'N/A' }}
                      </span>
                    </label>
                  @endforeach
                </div>
              @endif

              <center><a href="{{ route('frontend.add_address') }}" class="btn btn-default">
                Add NEW address
              </a></center>
            </div>
          </div>

          <div class="col-sm-5">
            <div class="order-details">
              <h3>Price Details</h3>
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

              <center><button type="submit" id="proceed-payment-btn" class="btn btn-primary w-auto" disabled>
                Proceed to payment
              </button></center>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (function () {
    var addressCheckboxes = document.querySelectorAll('.address-checkbox');
    var selectedAddressInput = document.getElementById('selected_address_id');
    var proceedButton = document.getElementById('proceed-payment-btn');

    function setButtonState() {
      var selectedValue = selectedAddressInput.value.trim();
      proceedButton.disabled = selectedValue === '';
    }

    addressCheckboxes.forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        if (checkbox.checked) {
          addressCheckboxes.forEach(function (otherCheckbox) {
            if (otherCheckbox !== checkbox) {
              otherCheckbox.checked = false;
            }
          });
          selectedAddressInput.value = checkbox.value;
        } else {
          selectedAddressInput.value = '';
        }

        setButtonState();
      });
    });

    setButtonState();
  })();
</script>

@include('frontend.footer')
