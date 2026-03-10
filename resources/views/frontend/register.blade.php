@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="account-register" class="container">
  <div class="row">
    <div id="content" class="col-sm-12">
      <div class="auth-container">
        <h2>Create Account - JC Mart</h2>

        @if(session('success'))
          <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @error('otp')
          <div class="text-danger">{{ $message }}</div>
        @enderror

        <form method="POST" action="{{ route('frontend.register.store') }}">
          @csrf
          <input type="text" name="name" placeholder="Name" value="{{ old('name') }}" required>
          @error('name')
            <div class="text-danger">{{ $message }}</div>
          @enderror

          <input type="text" name="mobile_number" placeholder="Mobile Number" value="{{ old('mobile_number') }}" required>
          @error('mobile_number')
            <div class="text-danger">{{ $message }}</div>
          @enderror

          <button type="submit">Register</button>
        </form>

        <p>Already have an account?
          <a href="{{ route('frontend.login') }}">Login Here</a>
        </p>
      </div>
    </div>
  </div>
</div>

@include('frontend.footer')
