<!DOCTYPE html><!--[if IE]><![endif]--><!--[if IE 8 ]><html dir="ltr" lang="en" class="ie8"><![endif]--><!--[if IE 9 ]><html dir="ltr" lang="en" class="ie9"><![endif]--><!--[if (gt IE 9)|!(IE)]><!--><html dir="ltr" lang="en"><!--<![endif]--><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>JcMarts</title>

<script src="{{ asset('assets/frontend/js/jquery-2.1.1.min.js') }}"></script>
<link href="{{ asset('assets/frontend/css/bootstrap.min_1.css') }}" rel="stylesheet" media="screen">
<script src="{{ asset('assets/frontend/js/bootstrap.min.js') }}"></script>
<link href="{{ asset('assets/frontend/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">

<link href="{{ asset('assets/frontend/css/stylesheet.css') }}" rel="stylesheet">
<link href="{{ asset('assets/frontend/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('assets/frontend/css/jquery.fancybox.css') }}" rel="stylesheet">
<link href="{{ asset('assets/frontend/css/animate.css') }}" rel="stylesheet">
<link href="{{ asset('assets/frontend/css/owl.carousel.css') }}" rel="stylesheet" type="text/css">
<link href="{{ asset('assets/frontend/css/live_search.css') }}" rel="stylesheet" type="text/css">

<link href="{{ asset('assets/frontend/fonts/Roboto-Italic-VariableFont_wdth,wght.ttf') }}" rel="stylesheet" media="screen">
<link href="{{ asset('assets/frontend/fonts/Roboto-VariableFont_wdth,wght.ttf') }}" rel="stylesheet" media="screen">
<link href="{{ asset('assets/frontend/fonts/Nunito-VariableFont_wght.ttf') }}" rel="stylesheet" media="screen">
<link href="{{ asset('assets/frontend/fonts/Nunito-Italic-VariableFont_wght.ttf') }}" rel="stylesheet" media="screen">

<link href="{{ asset('assets/frontend/css/magnific-popup.css') }}" rel="stylesheet" media="screen">
<link href="{{ asset('assets/frontend/css/bootstrap-datetimepicker.min.css') }}" rel="stylesheet" media="screen">
<link href="{{ asset('assets/frontend/css/swiper.min.css') }}" rel="stylesheet" media="screen">
<link href="{{ asset('assets/frontend/css/opencart.css') }}" rel="stylesheet" media="screen">
<script src="{{ asset('assets/frontend/js/jquery.magnific-popup.min.js') }}"></script>
<script src="{{ asset('assets/frontend/js/moment.min.js') }}"></script>
<script src="{{ asset('assets/frontend/js/moment-with-locales.min.js') }}"></script>
<script src="{{ asset('assets/frontend/js/bootstrap-datetimepicker.min.js') }}"></script>
<script src="{{ asset('assets/frontend/js/swiper.jquery.js') }}"></script>
<script src="{{ asset('assets/frontend/js/common.js') }}"></script>
<script src="{{ asset('assets/frontend/js/jquery.elevatezoom.min.js') }}"></script>
<script src="{{ asset('assets/frontend/js/jstree.min.js') }}"></script>
<script src="{{ asset('assets/frontend/js/tabs.js') }}"></script>
<script src="{{ asset('assets/frontend/js/template.js') }}"></script>
<script src="{{ asset('assets/frontend/js/jquery.firstVisitPopup.js') }}"></script>
<script src="{{ asset('assets/frontend/js/simpleswap.js') }}"></script>
<script src="{{ asset('assets/frontend/js/jquery.fancybox.js') }}"></script>
<script src="{{ asset('assets/frontend/js/owl.carousel.js') }}"></script>
<script src="{{ asset('assets/frontend/js/wow.min.js') }}"></script>


<link rel="stylesheet" href="{{ asset('assets/frontend/css/owl.transitions.css') }}">
<link href="{{ asset('assets/frontend/images/cart.png') }}" rel="icon">
@livewireStyles
</head>

<body class="common-home lang_en layout-1">
<nav id="top">
<div class="container">
<div class="row">
<span class="responsive-bar"><i class="fa fa-bars"></i></span>
<div class="header-middle-outer closetoggle">
</div>
</div>
</div>
</nav>

<header>
<div class="header-block">
<div class="container">
<div class="row">
<div class="header-top-block col-sm-12">
  
  <div class="header-top-right pull-right">
    <div class="telephone"><a href="#"><i class="fa fa-phone"></i>9514486111</a></div>
        <div class="login"><a href="#"><i class="fa fa-user"></i>Login</a></div>
    <div><a href="#">Signup</a></div>
      </div>
</div>
<div class="header-bottom-block col-sm-12">
  <div class="header-bottom-left col-xs-6 col-sm-2 col-md-2 col-lg-2">
    <div class="header-logo">
    <div id="logo"><a href="{{ route('frontend.home') }}"><img src="{{ asset('assets/frontend/images/logo.png') }}" title="JC Mart" alt="JC Mart" class="img-responsive" ></a></div>
    </div>
  </div>
  <div class="header-bottom-right col-xs-6 col-sm-10 col-md-10 col-lg-10">
    <div class="header-cart">
  <div id="cart" class="btn-group btn-block cart_block">
    <button 
      type="button"
      class="btn btn-inverse btn-block btn-lg"
      onclick="window.location.href='{{ route('frontend.cart') }}';">
      <span class="cart-text">My basket</span>
      <span class="cart-total">Item 0</span>
      <span class="cart-total-res">0</span>
    </button>
    <a href="{{ route('frontend.cart') }}" class="addtocart btn">
    <span class="cart-text">My basket</span>
    <span class="cart-total-res">0</span>
    </a>
  </div>
</div>
    <div class="header-link-search">
  <div class="header-search">
    <div class="actions">
      <button type="button" title="Search" class="action search" id="head-search"></button>
    </div>

    <div id="search" class="input-group">
  <input type="text" id="search-input" name="search" value="" placeholder="Search" class="form-control input-lg">
  <span class="input-group-btn">
    <button type="button" class="btn btn-default btn-lg">Search</button>
  </span>
</div>


  </div>
</div>

    <div class="header-menu">
    <div class="responsive-menubar-block">
      <span>Shop By<br> Category</span>
    <span class="menu-bar collapsed" data-target="#menu" data-toggle="collapse"><i class="fa fa-bars"></i></span>
    </div>
    <nav id="menu" class="navbar collapse">
<div class="navbar-header"> <span id="category" class="visible-xs">Top categories</span>
  <button type="button" class="btn btn-navbar navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse"><i class="fa fa-bars"></i></button>
</div>
<div class="collapse navbar-collapse navbar-ex1-collapse">
  <ul class="nav navbar-nav main-navigation">
    @forelse(($menuCategories ?? collect()) as $category)
      <li class="main_cat dropdown">
        <a href="{{ $category->subCategories->isNotEmpty() ? 'javascript:void(0);' : route('frontend.products') }}">{{ $category->category_name }}</a>
        @if($category->subCategories->isNotEmpty())
          <div class="dropdown-menu megamenu column1">
            <div class="dropdown-inner">
              <ul class="list-unstyled childs_1">
                @foreach($category->subCategories as $subCategory)
                  <li class="main_cat">
                    <a href="{{ route('frontend.products', ['sub_category' => $subCategory->id]) }}">{{ $subCategory->sub_category_name }}</a>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        @endif
      </li>
    @empty
      <li class="main_cat"><a href="{{ route('frontend.products') }}">Categories</a></li>
    @endforelse
  </ul>
  </div>

</nav>
<div id="responsive-menu" class="nav-container1 nav-responsive navbar collapse">

     <div class="navbar-collapse navbar-ex1-collapse collapse">
              <ul class="nav navbar-nav">
                @forelse(($menuCategories ?? collect()) as $category)
                  @php($collapseId = 'cat-' . \Illuminate\Support\Str::slug($category->category_name) . '-' . $category->id)
                  @if($category->subCategories->isNotEmpty())
                    <li class="collapsed" data-toggle="collapse" data-target="#{{ $collapseId }}">
                      <a href="javascript:void(0);">{{ $category->category_name }}</a>
                      <span><i class="fa fa-plus"></i></span>
                      <ul class="menu-dropdown collapse" id="{{ $collapseId }}">
                        @foreach($category->subCategories as $subCategory)
                          <li class="main_cat">
                            <a href="{{ route('frontend.products', ['sub_category' => $subCategory->id]) }}">{{ $subCategory->sub_category_name }}</a>
                          </li>
                        @endforeach
                      </ul>
                    </li>
                  @else
                    <li><a href="{{ route('frontend.products') }}">{{ $category->category_name }}</a></li>
                  @endif
                @empty
                  <li><a href="{{ route('frontend.products') }}">Categories</a></li>
                @endforelse
              </ul>
            </div></div>


 
    </div>
 </div>
</div>
</div>
</div>
</div>
<div class="header-static-block">
<div class="container">
<div class="row">
<div class="icon-block">
  <div class="home_icon">
  <a href="#"><i class="fa fa-home"></i>Home</a>
  </div>
  <div class="search_icon">
  <a href="#"><i class="fa fa-search"></i>Search</a>
  </div>
  <div class="cart_icon">

  </div>
  <div class="login_icon">
        <a href="#"><i class="fa fa-user"></i>Login</a>
      </div>
  <div class="telephone_icon">
    <a href="#"><i class="fa fa-phone"></i>Contact Us</a>
  </div>
</div>
 </div>
</div>
</div>
</header>
