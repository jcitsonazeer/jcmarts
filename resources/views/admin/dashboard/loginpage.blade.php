<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="description" content="JCMarts Admin Login">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>JCMarts | Admin Login</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('assets/admin/images/favicon.png') }}">

    <!-- Master Stylesheet CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/css/style.css') }}">
</head>

<body>

<div class="main-content- h-100vh">
    <div class="container h-100">
        <div class="row h-100 align-items-center justify-content-center">

            <div class="col-sm-10 col-md-8 col-lg-5">
                <div class="middle-box">
                    <div class="card">
                        <div class="card-body p-4">

                            <!-- Logo -->
                            <div class="text-center mb-4">
                                <img src="{{ asset('assets/admin/images/jcmarts-logo.png') }}"
                                     alt="JCMarts"
                                     style="height:60px;">
                            </div>

                            <h4 class="text-center mb-4">Admin Login</h4>

                            {{-- ERROR MESSAGE --}}
                            @if(session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            {{-- VALIDATION ERRORS --}}
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- LOGIN FORM -->
                            <form method="POST" action="{{ route('admin.login.submit') }}">
                                @csrf

                                <div class="form-group">
                                    <input type="text"
                                           class="form-control login"
                                           name="username"
                                           placeholder="Admin Username"
                                           value="{{ old('username') }}"
                                           required>
                                </div>

                                <div class="form-group">
                                    <input type="password"
                                           class="form-control login"
                                           name="password"
                                           placeholder="Password"
                                           required>
                                </div>

                                <div class="form-group mb-0">
                                    <button class="btn btn-primary btn-block" type="submit">
                                        Login to Admin
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

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

</body>
</html>
