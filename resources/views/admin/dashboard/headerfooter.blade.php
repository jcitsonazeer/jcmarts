<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="JCMarts Admin Panel">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>JCMarts ADMIN</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/admin/images/favicon.png') }}">

    <!-- Master Stylesheet CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    @livewireStyles
    @stack('styles')
</head>

<body>

<!-- Preloader -->
<div id="preloader-area">
    <div class="lds-ripple">
        <div></div>
        <div></div>
    </div>
</div>
<!-- Preloader -->

<!-- ======================================
********* Main Page Wrapper ***********
====================================== -->
<div class="main-container-wrapper">

    <!-- Top Navbar (Mobile) -->
    <div class="horizontal-menu sticky sticky-top">
        <nav class="navbar top-navbar col-lg-12 col-12 d-block d-sm-none p-0">
            <div class="container">
                <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                    <a class="navbar-brand brand-logo-mini">
                        <img src="{{ asset('assets/admin/images/jcmarts-logo.png') }}" alt="logo">
                    </a>
                </div>
                <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
                    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button">
                        <span class="ti-menu"></span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Bottom Navbar (Desktop) -->
        <nav class="bottom-navbar">
            <div class="container">
                <ul class="nav page-navigation">

                    <!-- Logo -->
                    <li class="d-none d-sm-block">
                        <img src="{{ asset('assets/admin/images/jcmarts-logo.png') }}"
                             alt="JCMarts"
                             class="img-fluid"
                             style="height:50px;">
                    </li>

                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="{{ url('/') }}" class="nav-link">
                            <i class="ti-home menu-icon"></i>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </li>

                    <!-- Users -->
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}" class="nav-link">
                            <i class="ti-user menu-icon"></i>
                            <span class="menu-title">Users</span>
                        </a>
                    </li>

                    <!-- Catalog Menu -->
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="ti-layout-grid2 menu-icon"></i>
                            <span class="menu-title">Catalog</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="submenu">
                            <ul class="submenu-item">
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.categories.index') }}">
                                        Categories
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{  route('admin.sub-categories.index') }}">
                                        Sub Categories
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.products.index') }}">
                                        Products
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.product-images.index') }}">
                                        Product Images
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.index-banners.index') }}">
                                        Index Banners
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.uom-masters.index') }}">
                                        UOM Master
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.rate-masters.index') }}">
                                        Rate Master
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.selected-display.index') }}">
                                        Selected Display
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Orders (Future) -->
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="ti-shopping-cart menu-icon"></i>
                            <span class="menu-title">Orders</span>
                        </a>
                    </li>

                    <!-- Customers (Future) -->
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <i class="ti-user menu-icon"></i>
                            <span class="menu-title">Customers</span>
                        </a>
                    </li>

                    <!-- Logout -->
                    <li class="nav-item">
                        <form action="{{ route('admin.logout') }}" method="POST" class="nav-link p-0">
                            @csrf
                            <button type="submit" class="btn btn-link nav-link p-0">
                                <i class="ti-power-off menu-icon"></i>
                                <span class="menu-title">Logout</span>
                            </button>
                        </form>
                    </li>

                </ul>
            </div>
        </nav>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        @yield('content')
    </div>

</div>
<!-- ======================================
********* Page Wrapper End ***********
====================================== -->

<!-- Scripts -->
<script src="{{ asset('assets/admin/js/jquery.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/bundle.js') }}"></script>

<script src="{{ asset('assets/admin/js/canvas.js') }}"></script>
<script src="{{ asset('assets/admin/js/collapse.js') }}"></script>
<script src="{{ asset('assets/admin/js/settings.js') }}"></script>
<script src="{{ asset('assets/admin/js/template.js') }}"></script>
<script src="{{ asset('assets/admin/js/active.js') }}"></script>

@livewireScripts
@stack('scripts')

</body>
</html>
