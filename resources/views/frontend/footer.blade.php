
<footer>
  <div id="footer">
    <div class="container">
        <div class="footer_block">
       <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-4 column myaccount">
          <h4>My Account</h4>
          <h5 class="collapsed" data-target="#dropdown-account" data-toggle="collapse">My Account <span class="icon"></span></h5>
          <ul class="list-unstyled collapse" id="dropdown-account">
            <li><a href="#">My Account</a></li>
            <li><a href="#">Order History</a></li>
            <li><a href="#">Contact Us</a></li>
          </ul>
        </div>
                <div class="col-xs-12 col-sm-12 col-md-4 column information">
          <h4>Information</h4>
          <h5 class="collapsed" data-target="#dropdown-information" data-toggle="collapse">Information <span class="icon"></span></h5>
          <ul class="list-unstyled collapse" id="dropdown-information">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Delivery Information</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms &amp; Conditions</a></li>
                        <li><a href="#">Returns</a></li>
          </ul>
        </div>
                  


        
         <div class="col-xs-12 col-sm-12 col-md-4 column contacts">   <div class="footer-common collapsible mobile-collapsible">
  <div class="footer-static">
    <h4>Contacts </h4>
    <h5 class="collapsed" data-target="#dropdown-contact" data-toggle="collapse">Contacts <span class="icon"></span> </h5>
    <ul class="clearfix collapse" id="dropdown-contact">
      <li class="item">Saravana Arcade, Old Bridge Road,<br>Kuzhithurai,<br>Marthandam.</li>
      <li class="item email"><a href="">info@jcmarts.com</a></li>
      <li class="item call">(+91) 9514486111</li>
    </ul>
  </div>
</div>

 </div>
         </div>
       
    <div class="container">
      <div class="row">
       <div class="footer-bottom-down col-md-12 col-sm-12 col-xs-12">  




  

  <div class="footer-bottom-section3 footer_social section col-md-12 col-xs-12">
  <div class="section-heading">Social media</div>
  <ul class="social-icon">
    <li><a class="facebook" title="Facebook" href="#"><i class="fa fa-facebook"> </i></a></li>
    <li><a class="twitter" title="Twitter" href="#"><i class="fa fa-twitter"> </i></a></li>
    <li><a class="instagram" title="Instagram" href="#"><i class="fa fa-instagram"> </i></a></li>
    <li><a class="rss" title="RSS" href="#"><i class="fa fa-rss"> </i></a></li>
    <li><a class="youtube" title="youtube" href="#"><i class="fa fa-youtube"> </i></a></li>
  </ul>
</div>

 </div>

        <div class="footer-bottom">
          <div class="copy-right col-md-12 col-sm-12 col-xs-12">
            <div id="powered">Powered By <a href="#">OpenCart</a> JC Mart © 2023</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</footer>

        
            
		






     


<script><!--
$('#slideshow0').swiper({
	mode: 'horizontal',
	slidesPerView: 1,
	pagination: '.slideshow0',
	paginationClickable: true,
	nextButton: '.swiper-button-next',
    prevButton: '.swiper-button-prev',
    spaceBetween: 0,
	autoplay: 5000,
    autoplayDisableOnInteraction: true,
	loop: true
});
--></script>

<script><!--

$(document).ready(function(){
$('.manufacture-slider').owlCarousel({
        items: 6,
		autoPlay: true,
		singleItem: false,
		navigation: true,
		pagination: false,
		itemsDesktop : [1199,5],
		itemsDesktopSmall :	[991,4],
		itemsTablet : [767,3],
		itemsTabletSmall : [479,2],
		itemsMobile : [360,2]
	});

});
--></script>
<script>
$(document).ready(function(){
$('.product-carousel').owlCarousel({
        items: 5,
        autoPlay: false,
        singleItem: false,
        navigation: true,
        pagination: false,
        itemsDesktop : [1199,4],
        itemsDesktopSmall : [991,3],
        itemsTablet : [767,3],
        itemsTabletSmall : [650,2],
       itemsMobile: [350, 2],   
    itemsMobileSmall: [349, 1] 
    });
   
    $('.banner-carousel').owlCarousel({
        items: 2,
        autoPlay: false,
        singleItem: false,
        navigation: false,
        pagination: true,
        itemsDesktop : [1199,2],
        itemsDesktopSmall : [991,2],
        itemsTablet : [767,2],
        itemsTabletSmall : [479,1],
        itemsMobile : [319,1]
    });
    
});
</script>
<script>
	// Can also be used with $(document).ready()
	$(window).load(function() {		
	  $("#spinner").fadeOut("slow");
	});	
</script>
<script>
$(document).ready(function(){

$('.productpage-carousel').owlCarousel({
    items: 5,
    autoPlay: false,
    singleItem: false,
    navigation: true,
    pagination: false,
    itemsDesktop : [1199,4],
    itemsDesktopSmall : [991,3],
    itemsTablet : [479,2],
    itemsMobile : [319,1]
  });
});

</script>

<script>
$(document).ready(function() {
    // Handle thumbnail clicks
    $('.image-additional .product-thumb a').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Get the image source from the clicked thumbnail
        var newImageSrc = $(this).find('img').attr('src');
        
        // Update the main image
        $('#zoom').attr('src', newImageSrc);
        
        // Optional: Add active class to clicked thumbnail
        $('.image-additional .product-thumb a').removeClass('active');
        $(this).addClass('active');
        
        return false;
    });
});
</script>


<script>
    $(document).ready(function(){

    $('.testimonial').owlCarousel({
        items: 1,
        autoPlay: true,
        singleItem: true,
        navigation: false,
        pagination: false
    });
    });
</script>


<script><!--


$(document).ready(function() {
	$('.thumbnails').magnificPopup({
		delegate: 'a.elevatezoom-gallery',
		type: 'image',
		tLoading: 'Loading image #%curr%...',
		mainClass: 'mfp-with-zoom',
		gallery: {
			enabled: true,
			navigateByImgClick: true,
			preload: [0,1] // Will preload 0 - before current, and 1 after the current image
		},
		image: {
			tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
			titleSrc: function(item) {
				return item.el.attr('title');
			}
		}
	});

  $('.image-additional').owlCarousel({
    items: 1,
    singleItem: true,
    navigation: true,
    pagination: false,
    itemsDesktop: [1199, 1],
    itemsDesktopSmall: [991, 1],
    itemsTablet: [767, 1],
    itemsTabletSmall: [479, 1],
    itemsMobile: [319, 1]
  });


$('.product-carousel').owlCarousel({
		    items: 5,
    autoPlay: false,
    singleItem: false,
    navigation: true,
    pagination: false,
    itemsDesktop : [1199,4],
    itemsDesktopSmall : [991,3],
    itemsTablet : [479,2],
    itemsMobile : [319,1]
	});



});

//--></script>



@livewireScripts
</body></html>
