@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="checkout-checkout" class="container">
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

        <div class="row">
          <div class="col-sm-7">
            <div class="panel panel-default">
              <div class="panel-heading"><h4 class="panel-title">Delivery Address</h4></div>
              <div class="panel-body">
                @if($errors->has('selected_address_id'))
                  <div class="alert alert-danger">{{ $errors->first('selected_address_id') }}</div>
                @endif

                @if($addresses->isEmpty())
                  <p>No delivery address found for your account.</p>
                @else
                  @foreach($addresses as $address)
                    @php
                      $isChecked = (string) old('selected_address_id') === (string) $address->id;
                    @endphp
                    <div class="well" style="margin-bottom: 12px;">
                      <label style="display:flex; gap:10px; align-items:flex-start; margin:0;">
                        <input
                          type="checkbox"
                          class="address-checkbox"
                          value="{{ $address->id }}"
                          {{ $isChecked ? 'checked' : '' }}
                          style="margin-top: 2px;"
                        >
                        <span>
                          <strong>{{ $address->address_line_1 }}</strong><br>
                          @if(!empty($address->address_line_2))
                            {{ $address->address_line_2 }}<br>
                          @endif
                          {{ $address->location }} - {{ $address->pincode }}<br>
                          Landmark: {{ $address->landmark ?: 'N/A' }}
                        </span>
                      </label>
                    </div>
                  @endforeach
                @endif

                <a href="{{ route('frontend.add_address') }}" class="btn btn-default">
                  Add NEW address
                </a>
              </div>
            </div>
          </div>

          <div class="col-sm-5">
            <div class="panel panel-default">
              <div class="panel-heading"><h4 class="panel-title">Price Details</h4></div>
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

            <button type="submit" id="proceed-payment-btn" class="btn btn-primary btn-block" disabled>
              Proceed to payment
            </button>
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
