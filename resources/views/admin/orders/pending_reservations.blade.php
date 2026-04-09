@extends('admin.dashboard.headerfooter')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var tableWrapper = document.getElementById('ordersTable');
    var updatedAt = document.getElementById('ordersTableUpdatedAt');

    function refreshOrdersTable() {
        fetch("{{ route('admin.orders.pending-reservations.table') }}", {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.text();
            })
            .then(function (html) {
                tableWrapper.innerHTML = html;
                updatedAt.textContent = new Date().toLocaleString();
            })
            .catch(function () {
                updatedAt.textContent = 'Refresh failed';
            });
    }

    updatedAt.textContent = new Date().toLocaleString();
    setInterval(refreshOrdersTable, 60000);
});
</script>
@endpush

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Expired Reservation History</h4>
                            <a href="{{ route('admin.orders.index') }}" class="btn btn-secondary">Back to Orders</a>
                        </div>

                        <p class="text-muted">
                            This page refreshes the table every 1 minute. On each refresh, newly expired pending reservations
                            are released automatically and the stock is added back before the table is shown.
                        </p>
                        <p class="text-muted mb-3">
                            Last updated: <span id="ordersTableUpdatedAt">-</span>
                        </p>

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

                        <div id="ordersTable" class="table-responsive">
                            @include('admin.orders.partials.pending_reservations_table', ['orders' => $orders])
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
