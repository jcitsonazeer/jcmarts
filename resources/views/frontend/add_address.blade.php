@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="account-address" class="container order-page-wrapper orders-theme">
  <ul class="breadcrumb">
    <h1>Add Delivery Address</h1>
    <li><a href="{{ route('frontend.home') }}"><i class="fa fa-home"></i></a></li>
    <li><a href="{{ route('frontend.checkout') }}">Checkout</a></li>
    <li><a href="{{ route('frontend.add_address') }}">Add Address</a></li>
  </ul>

  <div class="row">
    <div id="content" class="col-sm-12 checkout">
      @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
      @endif

      <div class="row">
        <div class="col-md-8 col-md-offset-2">
          <div class="order-details address-form-card">
            <div class="top-row">
              <h3>New Address</h3>
            </div>
          <form method="POST" action="{{ route('frontend.add_address.store') }}" id="add-address-form">
            @csrf

            <div class="form-group">
              <label for="address_line_1">Address Line 1</label>
              <input type="text" name="address_line_1" id="address_line_1" class="form-control" value="{{ old('address_line_1') }}" required>
              @error('address_line_1')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="address_line_2">Address Line 2</label>
              <input type="text" name="address_line_2" id="address_line_2" class="form-control" value="{{ old('address_line_2') }}" required>
              @error('address_line_2')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="location">Location</label>
              <input type="text" name="location" id="location" class="form-control" value="{{ old('location') }}" required>
              @error('location')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="pincode">Pincode</label>
              <input
                type="text"
                name="pincode"
                id="pincode"
                class="form-control"
                value="{{ old('pincode') }}"
                required
                inputmode="numeric"
                maxlength="6"
                pattern="[0-9]{6}"
              >
              @error('pincode')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="landmark">Landmark</label>
              <input type="text" name="landmark" id="landmark" class="form-control" value="{{ old('landmark') }}" required>
              @error('landmark')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>

            <div class="buttons clearfix">
              <div class="pull-left">
                <a href="{{ route('frontend.checkout') }}" class="btn btn-default">Back to Checkout</a>
              </div>
              <div class="pull-right">
                <button type="submit" class="btn btn-primary">Save Address</button>
              </div>
            </div>
          </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var form = document.getElementById('add-address-form');
    var pincodeInput = document.getElementById('pincode');
    var allowedPincodes = @json($serviceablePincodes ?? []);
    var deliveryMessage = 'Delivery not available for the entered pincode';

    if (!form || !pincodeInput) {
      return;
    }

    function validatePincode(showAlert) {
      var pincode = pincodeInput.value.trim();

      if (!/^\d{6}$/.test(pincode)) {
        pincodeInput.setCustomValidity('Pincode must be exactly 6 digits.');
        return false;
      }

      if (allowedPincodes.indexOf(pincode) === -1) {
        pincodeInput.setCustomValidity(deliveryMessage);
        if (showAlert) {
          alert(deliveryMessage);
        }

        return false;
      }

      pincodeInput.setCustomValidity('');

      return true;
    }

    pincodeInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 6);
      this.setCustomValidity('');
    });

    pincodeInput.addEventListener('blur', function () {
      if (this.value.trim() !== '') {
        validatePincode(true);
      }
    });

    form.addEventListener('submit', function (event) {
      if (!validatePincode(true)) {
        event.preventDefault();
        pincodeInput.reportValidity();
      }
    });
  })();
</script>

@include('frontend.footer')
