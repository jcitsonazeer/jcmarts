@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="card-title">View Index Banner</h4>
                            <div>
                                <a href="{{ route('admin.index-banners.index') }}" class="btn btn-secondary">
                                    <i class="fa fa-arrow-left"></i> Back to Banners
                                </a>
                                <a href="{{ route('admin.index-banners.edit', $banner->id) }}" class="btn btn-warning">
                                    <i class="fa fa-edit"></i> Edit
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tbody>
                                    <tr>
                                        <th width="200">ID</th>
                                        <td>{{ $banner->id }}</td>
                                    </tr>
                                    <tr>
                                        <th>Image</th>
                                        @php
                                            $defaultImage = asset('assets/admin/images/no_image.png');
                                            $bannerImage = $banner->banner_image
                                                ? asset('storage/index_banner/' . $banner->banner_image)
                                                : $defaultImage;
                                        @endphp
                                        <td>
                                            <img src="{{ $bannerImage }}"
                                                 alt="Banner Image"
                                                 style="width: 300px; height: 120px; object-fit: cover; border-radius: 6px;"
                                                 onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Created By</th>
                                        <td>{{ $banner->createdBy ? $banner->createdBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Created Date</th>
                                        <td>{{ $banner->created_date ? date('d-m-Y H:i', strtotime($banner->created_date)) : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated By</th>
                                        <td>{{ $banner->updatedBy ? $banner->updatedBy->admin_username : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Updated Date</th>
                                        <td>{{ $banner->updated_date ? date('d-m-Y H:i', strtotime($banner->updated_date)) : '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    setTimeout(function () {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>
@endpush
@endsection
