@include('frontend.header')
<div class="content-top-breadcum"></div>

<div id="account-register" class="container">
  <div class="row">
    <div id="content" class="col-sm-12">
      <div class="auth-container">
        <h2>Create Account - JC Mart</h2>

        <form>
          <input type="text" placeholder="Name" required>
          <input type="number" placeholder="Mobile Number" required>

          <button type="submit">Register</button>
        </form>

        <p>Already have an account?
          <a href="login.html">Login Here</a>
        </p>
      </div>
    </div>
  </div>
</div>

@include('frontend.footer')
