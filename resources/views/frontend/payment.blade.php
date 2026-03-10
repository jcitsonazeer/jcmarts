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
    </div>
  </div>
</div>

@include('frontend.footer')
