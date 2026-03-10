@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="account-login" class="container">
  <div class="row">
    <div id="content" class="col-sm-12">
      <div class="auth-container">
        <h2>Login - JC Mart</h2>

        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('frontend.login.store') }}">
          @csrf
          <input type="text" name="mobile_number" placeholder="Mobile Number" value="{{ old('mobile_number') }}" required>
          @error('mobile_number')
            <div class="text-danger">{{ $message }}</div>
          @enderror

          <button type="submit">Login</button>
        </form>
        <p>OTP will be sent to your mobile number if registered.</p>

        <p>New user?
          <a href="{{ route('frontend.register') }}">Register Here</a>
        </p>
      </div>
    </div>
  </div>
</div>

@include('frontend.footer')
