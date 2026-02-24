@include('frontend.header')
<div class="content-top-breadcum">

</div>

<div id="checkout-cart" class="container">
  <ul class="breadcrumb"><h1>Shopping Cart
                &nbsp;(0.00kg)
         </h1>
        <li><a href="#"><i class="fa fa-home"></i></a></li>
        <li><a href="#">Shopping Cart</a></li>
      </ul>
        <div class="row">
                <div id="content" class="col-sm-12 checkout">
      
      <div class="row">
      <div class="col-xs-12 col-sm-8">
      <form action="#" method="post" enctype="multipart/form-data">
        <div class="table-responsive">
          <table class="table table-bordered">
            <thead>
              <tr>
                <td class="text-center">Image</td>
                <td class="text-left">Product Name</td>
                <td class="text-left">Model</td>
                <td class="text-left">Quantity</td>
                <td class="text-right">Unit Price</td>
                <td class="text-right">Total</td>
              </tr>
            </thead>
            <tbody>
            
                        <tr>
              <td class="text-center"> <a href="#"><img src="{{ asset('assets/frontend/images/c5.png') }}" alt="Strawberry" title="Strawberry" class="img-thumbnail"></a> </td>
              <td class="text-left"><a href="#">Strawberry</a>                                  <br>
                <small>Packet Size: 10kg</small>                                  <br>
                <small>Reward Points: 200</small>                 </td>
              <td class="text-left">Product 3</td>
              <td class="text-left"><div class="input-group btn-block" style="max-width: 200px;">
                  <input type="text" name="quantity[888]" value="1" size="1" class="form-control">
                  <span class="input-group-btn">
                  <button type="submit" data-toggle="tooltip" title="" class="btn btn-primary refresh" ><i class="fa fa-refresh"></i></button>
                  <button type="button" data-toggle="tooltip" title="" class="btn btn-danger delete"><i class="fa fa-times-circle"></i></button>
                  </span></div></td>
              <td class="text-right">₹198</td>
              <td class="text-right">₹198</td>
            </tr>
                                      </tbody>
            
          </table>
        </div>
      </form>

      </div>
     
                
      <div class="panel-group col-xs-12 col-sm-4" id="accordion">         <div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title"><a href="#collapse-coupon" class="accordion-toggle" data-toggle="collapse" data-parent="#accordion">Use Coupon Code <i class="fa fa-caret-down"></i></a></h4>
  </div>
  <div id="collapse-coupon" class="panel-collapse collapse">
    <div class="panel-body">
      <label class="col-sm-2 control-label" for="input-coupon">Enter your coupon here</label>
      <div class="input-group">
        <input type="text" name="coupon" value="" placeholder="Enter your coupon here" id="input-coupon" class="form-control">
        <span class="input-group-btn">
        <input type="button" value="Apply Coupon" id="button-coupon" data-loading-text="Loading..." class="btn btn-primary">
        </span></div>
      
    </div>
  </div>
</div>

                <div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title"><a href="#collapse-shipping" class="accordion-toggle" data-toggle="collapse" data-parent="#accordion">Estimate Shipping &amp; Taxes <i class="fa fa-caret-down"></i></a></h4>
  </div>
  <div id="collapse-shipping" class="panel-collapse collapse">
    <div class="panel-body">
      <p>Enter your destination to get a shipping estimate.</p>
      <div class="form-horizontal">
        <div class="form-group required">
          <label class="col-sm-2 control-label" for="input-country">Country</label>
          <div class="col-sm-10">
            <select name="country_id" id="input-country" class="form-control">
              <option value=""> --- Please Select --- </option>
                                          <option value="244">Aaland Islands</option>
                                                        <option value="1">Afghanistan</option>
                                                        <option value="2">Albania</option>
                                                        <option value="3">Algeria</option>
                                                        <option value="4">American Samoa</option>
                                                        <option value="5">Andorra</option>
                                                        
                                        </select>
          </div>
        </div>
        <div class="form-group required">
          <label class="col-sm-2 control-label" for="input-zone">Region / State</label>
          <div class="col-sm-10">
            <select name="zone_id" id="input-zone" class="form-control"><option value=""> --- Please Select --- </option><option value="3513">Aberdeen</option><option value="3514">Aberdeenshire</option><option value="3515">Anglesey</option><option value="3516">Angus</option><option value="3517">Argyll and Bute</option><option value="3518">Bedfordshire</option><option value="3519">Berkshire</option><option value="3520">Blaenau Gwent</option><option value="3521">Bridgend</option><option value="3522">Bristol</option><option value="3523">Buckinghamshire</option><option value="3524">Caerphilly</option><option value="3525">Cambridgeshire</option><option value="3526">Cardiff</option><option value="3527">Carmarthenshire</option><option value="3528">Ceredigion</option><option value="3529">Cheshire</option><option value="3530">Clackmannanshire</option><option value="3531">Conwy</option><option value="3532">Cornwall</option><option value="3949">County Antrim</option><option value="3950">County Armagh</option><option value="3951">County Down</option><option value="3952">County Fermanagh</option><option value="3953">County Londonderry</option><option value="3954">County Tyrone</option><option value="3955">Cumbria</option><option value="3533">Denbighshire</option><option value="3534">Derbyshire</option><option value="3535">Devon</option><option value="3536">Dorset</option><option value="3537">Dumfries and Galloway</option><option value="3538">Dundee</option><option value="3539">Durham</option><option value="3540">East Ayrshire</option><option value="3541">East Dunbartonshire</option><option value="3542">East Lothian</option><option value="3543">East Renfrewshire</option><option value="3544">East Riding of Yorkshire</option><option value="3545">East Sussex</option><option value="3546">Edinburgh</option><option value="3547">Essex</option><option value="3548">Falkirk</option><option value="3549">Fife</option><option value="3550">Flintshire</option><option value="3551">Glasgow</option><option value="3552">Gloucestershire</option><option value="3553">Greater London</option><option value="3554">Greater Manchester</option><option value="3555">Gwynedd</option><option value="3556">Hampshire</option><option value="3557">Herefordshire</option><option value="3558">Hertfordshire</option><option value="3559">Highlands</option><option value="3560">Inverclyde</option><option value="3561">Isle of Wight</option><option value="3562">Kent</option><option value="3563">Lancashire</option><option value="3564">Leicestershire</option><option value="3565">Lincolnshire</option><option value="3566">Merseyside</option><option value="3567">Merthyr Tydfil</option><option value="3568">Midlothian</option><option value="3569">Monmouthshire</option><option value="3570">Moray</option><option value="3571">Neath Port Talbot</option><option value="3572">Newport</option><option value="3573">Norfolk</option><option value="3574">North Ayrshire</option><option value="3575">North Lanarkshire</option><option value="3576">North Yorkshire</option><option value="3577">Northamptonshire</option><option value="3578">Northumberland</option><option value="3579">Nottinghamshire</option><option value="3580">Orkney Islands</option><option value="3581">Oxfordshire</option><option value="3582">Pembrokeshire</option><option value="3583">Perth and Kinross</option><option value="3584">Powys</option><option value="3585">Renfrewshire</option><option value="3586">Rhondda Cynon Taff</option><option value="3587">Rutland</option><option value="3588">Scottish Borders</option><option value="3589">Shetland Islands</option><option value="3590">Shropshire</option><option value="3591">Somerset</option><option value="3592">South Ayrshire</option><option value="3593">South Lanarkshire</option><option value="3594">South Yorkshire</option><option value="3595">Staffordshire</option><option value="3596">Stirling</option><option value="3597">Suffolk</option><option value="3598">Surrey</option><option value="3599">Swansea</option><option value="3600">Torfaen</option><option value="3601">Tyne and Wear</option><option value="3602">Vale of Glamorgan</option><option value="3603">Warwickshire</option><option value="3604">West Dunbartonshire</option><option value="3605">West Lothian</option><option value="3606">West Midlands</option><option value="3607">West Sussex</option><option value="3608">West Yorkshire</option><option value="3609">Western Isles</option><option value="3610">Wiltshire</option><option value="3611">Worcestershire</option><option value="3612">Wrexham</option></select>
          </div>
        </div>
        <div class="form-group required">
          <label class="col-sm-2 control-label" for="input-postcode">Post Code</label>
          <div class="col-sm-10">
            <input type="text" name="postcode" value="" placeholder="Post Code" id="input-postcode" class="form-control">
          </div>
        </div>
        <button type="button" id="button-quote"  class="btn btn-primary">Get Quotes</button>
      </div>
      

    </div>
  </div>
</div>

                <div class="panel panel-default">
  <div class="panel-heading">
    <h4 class="panel-title"><a href="#collapse-voucher" data-toggle="collapse" data-parent="#accordion" class="accordion-toggle">Use Gift Certificate <i class="fa fa-caret-down"></i></a></h4>
  </div>
  <div id="collapse-voucher" class="panel-collapse collapse">
    <div class="panel-body">
      <label class="col-sm-2 control-label" for="input-voucher">Enter your gift certificate code here</label>
      <div class="input-group">
        <input type="text" name="voucher" value="" placeholder="Enter your gift certificate code here" id="input-voucher" class="form-control">
        <span class="input-group-btn">
        <input type="submit" value="Apply Gift Certificate" id="button-voucher"  class="btn btn-primary">
        </span> </div>
     
    </div>
  </div>
</div>

         </div>
       
        <div class="col-xs-12 col-sm-4 col-sm-offset-8">
          <table class="table table-bordered grand-total">
                        <tbody><tr>
              <td class="text-right"><strong>Sub-Total:</strong></td>
              <td class="text-right">₹180</td>
            </tr>
                        <tr>
              <td class="text-right"><strong>GST(10%):</strong></td>
              <td class="text-right">₹18</td>
            </tr>
                        <tr>
              <td class="text-right"><strong>Total:</strong></td>
              <td class="text-right">₹198</td>
            </tr>
                      </tbody></table>
        </div> 
         </div> 
      <div class="buttons clearfix">
        <div class="pull-left"><a href="#" class="btn btn-default">Continue Shopping</a></div>
        <div class="pull-right"><a href="{{ route('frontend.checkout') }}" class="btn btn-primary">Checkout</a></div>
      </div>
      </div>
    </div>
</div>

@include('frontend.footer')
