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
    @if(session()->has('customer_id'))
      <div class="login"><span><i class="fa fa-user"></i>Hello {{ session('customer_name') }}</span></div>
      <div class="login"><a href="{{ route('frontend.orders.index') }}"><i class="fa fa-shopping-bag"></i>My Orders</a></div>
      <div>
        <form method="POST" action="{{ route('frontend.logout') }}" style="display:inline;">
          @csrf
          <button type="submit" style="background:none;border:none;padding:0;color:#000;">Logout</button>
        </form>
      </div>
    @else
      <div class="login"><a href="{{ route('frontend.login') }}"><i class="fa fa-user"></i>Login</a></div>
      <div><a href="{{ route('frontend.register') }}">Signup</a></div>
    @endif
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
      <livewire:frontend.cart-summary />
</div>

<livewire:frontend.wishlist-summary />

    <div class="header-link-search">
  <div class="header-search">
    <div class="actions">
      <button type="button" title="Search" class="action search" id="head-search"></button>
    </div>

    <form id="search" class="input-group" method="GET" action="{{ route('frontend.products') }}">
      <input type="text" id="search-input" name="search" value="{{ request('search', '') }}" placeholder="Search" class="form-control input-lg">
      <span class="input-group-btn">
        <button type="submit" class="btn btn-default btn-lg">Search</button>
      </span>
    </form>


  </div>
</div>

    @php($firstCategory = ($menuCategories ?? collect())->first())
    <div class="header-menu" data-first-cat="{{ $firstCategory?->id ?? '' }}">
      <div class="menu-btn">Shop by Category</div>

      <div class="mega-menu">
        <div class="category-list">
          @forelse(($menuCategories ?? collect()) as $category)
            <div class="category" data-category-id="{{ $category->id }}">
              {{ $category->category_name }}
            </div>
          @empty
            <div class="category">Categories</div>
          @endforelse
        </div>

        <div class="subcategory">
          @foreach(($menuCategories ?? collect()) as $category)
            <div class="submenu" data-category-id="{{ $category->id }}">
              @forelse($category->subCategories as $subCategory)
                <a href="{{ route('frontend.products', ['sub_category' => $subCategory->id]) }}">
                  {{ $subCategory->sub_category_name }}
                </a>
              @empty
                <a href="{{ route('frontend.products') }}">View products</a>
              @endforelse
            </div>
          @endforeach
        </div>
      </div>
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
      @if(session()->has('customer_id'))
        <a href="javascript:void(0);"><i class="fa fa-user"></i>Hello {{ session('customer_name') }}</a>
      @else
        <a href="{{ route('frontend.login') }}"><i class="fa fa-user"></i>Login</a>
      @endif
      </div>
  <div class="telephone_icon">
    <a href="#"><i class="fa fa-phone"></i>Contact Us</a>
  </div>
</div>
 </div>
</div>
</div>
</header>
