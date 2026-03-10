@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="account-login-otp" class="container">
  <div class="row">
    <div id="content" class="col-sm-12">
      <div class="auth-container">
        <h2>Login OTP Verification</h2>
        <p>Enter the OTP received on your number: <strong>{{ $mobileNumber }}</strong></p>

        @error('otp')
          <div class="text-danger">{{ $message }}</div>
        @enderror

        <p>Time left: <strong id="otp-timer">03:00</strong></p>

        <form method="POST" action="{{ route('frontend.login.otp.verify') }}">
          @csrf
          <input id="otp_code" type="text" name="otp_code" maxlength="6" placeholder="Enter OTP" value="{{ old('otp_code') }}" required>
          @error('otp_code')
            <div class="text-danger">{{ $message }}</div>
          @enderror

          <button id="otp-submit" type="submit">Verify & Login</button>
        </form>

        <input type="hidden" id="otp-deadline" value="{{ $countdownDeadline }}">
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    var deadlineEl = document.getElementById('otp-deadline');
    var timerEl = document.getElementById('otp-timer');
    var submitBtn = document.getElementById('otp-submit');
    var otpInput = document.getElementById('otp_code');

    if (!deadlineEl || !timerEl || !submitBtn || !otpInput) {
      return;
    }

    var deadline = parseInt(deadlineEl.value, 10);

    function formatTime(seconds) {
      var m = Math.floor(seconds / 60);
      var s = seconds % 60;
      return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
    }

    function tick() {
      var now = Math.floor(Date.now() / 1000);
      var remaining = Math.max(0, deadline - now);
      timerEl.textContent = formatTime(remaining);

      if (remaining <= 0) {
        submitBtn.disabled = true;
        otpInput.disabled = true;
      }
    }

    tick();
    setInterval(tick, 1000);
  })();
</script>

@include('frontend.footer')

