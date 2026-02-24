@include('frontend.header')
<div class="content-top-breadcum" >
 <ul class="breadcrumb"><h1>Shopping Cart
                &nbsp;(0.00kg)
         </h1>
        <li><a href="#"><i class="fa fa-home"></i></a></li>
        <li><a href="#">Shopping Cart</a></li>
      </ul>
</div>

<div id="checkout-checkout" class="container">
  <ul class="breadcrumb"><h1>Checkout</h1>
        <li><a href="#"><i class="fa fa-home"></i></a></li>
        <li><a href="#">Shopping Cart</a></li>
        <li><a href="#">Checkout</a></li>
      </ul>
    <div class="row">
                <div id="content" class="col-sm-12 checkout">
      
      <div class="panel-group" id="accordion">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title"><a href="#" data-toggle="collapse" data-parent="#accordion" class="accordion-toggle" aria-expanded="true">Step 1: Checkout Options <i class="fa fa-caret-down"></i></a></h4>
          </div>
          <div class="panel-collapse collapse in" id="collapse-checkout-option" aria-expanded="true" style="">
            <div class="panel-body"><div class="row">
  <div class="col-sm-6">
    <h2>New Customer</h2>
    <p>Checkout Options:</p>
    <div class="radio">
      <label>         <input type="radio" name="account" value="register" checked="checked">
                Register Account</label>
    </div>
        <div class="radio">
      <label>         <input type="radio" name="account" value="guest">
                Guest Checkout</label>
    </div>
        <p>By creating an account you will be able to shop faster, be up to date on an order's status, and keep track of the orders you have previously made.</p>
    <input type="button" value="Continue" id="button-account" data-loading-text="Loading..." class="btn btn-primary">
  </div>
  <div class="col-sm-6">
    <h2>Returning Customer</h2>
    <p>I am a returning customer</p>
    <div class="form-group">
      <label class="control-label" for="input-email">E-Mail</label>
      <input type="text" name="email" value="" placeholder="E-Mail" id="input-email" class="form-control">
    </div>
    <div class="form-group">
      <label class="control-label" for="input-password">Password</label>
      <input type="password" name="password" value="" placeholder="Password" id="input-password" class="form-control">
      <a href="#">Forgotten Password</a></div>
    <input type="button" value="Login" id="button-login" data-loading-text="Loading..." class="btn btn-primary">
  </div>
</div>
</div>
          </div>
        </div>
                <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">Step 2: Account &amp; Billing Details</h4>
          </div>
          <div class="panel-collapse collapse" id="collapse-payment-address">
            <div class="panel-body"></div>
          </div>
        </div>
                        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">Step 3: Delivery Details</h4>
          </div>
          <div class="panel-collapse collapse" id="collapse-shipping-address">
            <div class="panel-body"></div>
          </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">Step 4: Delivery Method</h4>
          </div>
          <div class="panel-collapse collapse" id="collapse-shipping-method">
            <div class="panel-body"></div>
          </div>
        </div>
                <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">Step 5: Payment Method</h4>
          </div>
          <div class="panel-collapse collapse" id="collapse-payment-method">
            <div class="panel-body"></div>
          </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">Step 6: Confirm Order</h4>
          </div>
          <div class="panel-collapse collapse" id="collapse-checkout-confirm">
            <div class="panel-body"></div>
          </div>
        </div>
      </div>
      </div>
    </div>
</div>

@include('frontend.footer')
        
