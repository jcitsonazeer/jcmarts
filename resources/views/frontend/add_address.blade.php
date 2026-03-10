@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="account-address" class="container">
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

      <div class="panel panel-default" style="max-width: 700px;">
        <div class="panel-heading">
          <h4 class="panel-title">New Address</h4>
        </div>
        <div class="panel-body">
          <form method="POST" action="{{ route('frontend.add_address.store') }}">
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
              <input type="text" name="address_line_2" id="address_line_2" class="form-control" value="{{ old('address_line_2') }}">
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
              <input type="text" name="pincode" id="pincode" class="form-control" value="{{ old('pincode') }}" required>
              @error('pincode')
                <div class="text-danger">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group">
              <label for="landmark">Landmark</label>
              <input type="text" name="landmark" id="landmark" class="form-control" value="{{ old('landmark') }}">
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

@include('frontend.footer')
