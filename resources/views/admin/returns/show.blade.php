@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Return Details - {{ $return->id }}</h4>
                            <div>
                                <a href="{{ route('admin.returns.process', $return->id) }}" class="btn btn-primary">Process Return</a>
                                <a href="{{ route('admin.returns.index') }}" class="btn btn-secondary">Back</a>
                            </div>
                        </div>

                        @include('admin.returns.partials.details', ['return' => $return])
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
