@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">Delivery Charges</h4>
                        </div>

                        @if (session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="alert alert-info">
                            <strong>How it works:</strong>
                            <ul class="mb-0">
                                <li>Delivery orders are accepted only for a minimum order value of Rs. <strong>{{ $settings->minimum_order_value ?? '0' }}</strong>.</li>
                                <li>For orders between the minimum order value and <strong>Rs. {{ $settings->free_delivery_above ?? '0' }}</strong>, a delivery charge of <strong>Rs. {{ $settings->delivery_charge ?? '0' }}</strong> applies.</li>
                                <li>Orders of <strong>Rs. {{ $settings->free_delivery_above ?? '0' }}</strong> and above qualify for free direct delivery.</li>
                            </ul>
                        </div>

                        <form method="POST" action="{{ route('admin.delivery-charges.store') }}">
                            @csrf

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Delivery Charge (Rs) <span class="text-danger">*</span></label>
                                    <input type="number" name="delivery_charge" class="form-control"
                                           value="{{ old('delivery_charge', $settings->delivery_charge ?? '') }}"
                                           min="0" step="0.01" required
                                           placeholder="e.g. 50">
                                    <small class="form-text text-muted">Flat amount charged for orders that are not free.</small>
                                </div>
                                <div class="col-md-6">
                                    <label>Free Delivery Above (Rs) <span class="text-danger">*</span></label>
                                    <input type="number" name="free_delivery_above" class="form-control"
                                           value="{{ old('free_delivery_above', $settings->free_delivery_above ?? '') }}"
                                           min="0" step="0.01" required
                                           placeholder="e.g. 1500">
                                    <small class="form-text text-muted">Orders equal to or above this amount get free delivery.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label>Minimum Order Value (Rs) <span class="text-danger">*</span></label>
                                    <input type="number" name="minimum_order_value" class="form-control"
                                           value="{{ old('minimum_order_value', $settings->minimum_order_value ?? '') }}"
                                           min="0" step="0.01" required
                                           placeholder="e.g. 300">
                                    <small class="form-text text-muted">Delivery orders are accepted only above this value.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary mr-2"><i class="fa fa-save"></i> Save Settings</button>
                                    <button type="reset" class="btn btn-warning mr-2"><i class="fa fa-refresh"></i> Reset</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection