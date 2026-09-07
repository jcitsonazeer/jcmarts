@include('frontend.header')

<div class="content-top-breadcum"></div>

<div class="container account-page-wrapper orders-theme">
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <div class="col-md-3">
            <div class="order-sidebar order-fixed">
                <h3>My Account</h3>
                <div class="account-menu">
                    <a href="{{ route('frontend.account') }}" class="account-link active">My Account</a>
                    <a href="{{ route('frontend.orders.index') }}" class="account-link">Order History</a>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="order-details order-fixed">
                @if($customer)
                    <div class="section-title">Profile Information</div>
                    <div class="info-card">
                        <div class="info-row">
                            <div class="label">Name</div>
                            <div class="value">{{ $customer->name }}</div>
                        </div>
                        <div class="info-row">
                            <div class="label">Mobile Number</div>
                            <div class="value">{{ $customer->mobile_number }}</div>
                        </div>
                        <div class="info-row">
                            <div class="label">Status</div>
                            <div class="value">{{ ucwords($customer->verified_status) }}</div>
                        </div>
                        <div class="info-row">
                            <div class="label">Member Since</div>
                            <div class="value">{{ $customer->created_date ? $customer->created_date->format('d-m-Y') : '-' }}</div>
                        </div>
                    </div>

                    <div class="section-title">My Addresses ({{ $customer->addresses->count() }})</div>
                    @forelse($customer->addresses as $address)
                        <div class="address-card">
                            <div><strong>{{ $address->address_line_1 }}</strong></div>
                            <div>{{ $address->address_line_2 }}</div>
                            <div><strong>Location:</strong> {{ $address->location ?? '-' }}</div>
                            <div><strong>Pincode:</strong> {{ $address->pincode ?? '-' }}</div>
                            <div><strong>Landmark:</strong> {{ $address->landmark ?? '-' }}</div>
                        </div>
                    @empty
                        <div class="text-center">No addresses added yet.</div>
                    @endforelse

                    <div class="section-title">Quick Links</div>
                    <div class="info-card">
                        <div class="info-row">
                            <div class="value"><a href="{{ route('frontend.orders.index') }}">View My Orders</a></div>
                        </div>
                        <div class="info-row">
                            <div class="value"><a href="{{ route('frontend.wishlist') }}">My Wishlist</a></div>
                        </div>
                    </div>
                @else
                    <div class="text-center">Customer information not found.</div>
                @endif
            </div>
        </div>
    </div>
</div>

@include('frontend.footer')
