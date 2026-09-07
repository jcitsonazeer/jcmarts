@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Promo Tiles</h4>
                            <a href="{{ route('admin.promo-tiles.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Promo Tile
                            </a>
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

                        <div class="row">
                            <div class="col-sm-12 col-xs-12">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>ID</th>
                                                <th>Image</th>
                                                <th>Title</th>
                                                <th>Sort Order</th>
                                                <th>Status</th>
                                                <th>Products</th>
                                                <th>Created By</th>
                                                <th>Created Date</th>
                                                <th>Updated By</th>
                                                <th>Updated Date</th>
                                                <th width="190">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($promoTiles as $promoTile)
                                                @php
                                                    $defaultImage = asset('assets/admin/images/no_image.png');
                                                    $tileImage = $promoTile->promo_image
                                                        ? asset('storage/promo_tile/' . $promoTile->promo_image)
                                                        : $defaultImage;
                                                @endphp
                                                <tr>
                                                    <td>{{ $promoTiles->firstItem() + $loop->index }}</td>
                                                    <td>
                                                        <img src="{{ $tileImage }}"
                                                             alt="Promo Tile Image"
                                                             style="width: 180px; height: 90px; object-fit: cover; border-radius: 6px;"
                                                             onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                                    </td>
                                                    <td>{{ $promoTile->promo_title }}</td>
                                                    <td>{{ $promoTile->sort_order }}</td>
                                                    <td>
                                                        @if($promoTile->is_active)
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $promoTile->products->count() }}</td>
                                                    <td>{{ $promoTile->createdBy ? $promoTile->createdBy->admin_username : '-' }}</td>
                                                    <td>{{ $promoTile->created_date ? date('d-m-Y H:i', strtotime($promoTile->created_date)) : '-' }}</td>
                                                    <td>{{ $promoTile->updatedBy ? $promoTile->updatedBy->admin_username : '-' }}</td>
                                                    <td>{{ $promoTile->updated_date ? date('d-m-Y H:i', strtotime($promoTile->updated_date)) : '-' }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('admin.promo-tiles.edit', $promoTile->id) }}"
                                                               class="btn btn-warning btn-sm"
                                                               title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <a href="{{ route('admin.promo-tiles.products', $promoTile->id) }}"
                                                               class="btn btn-info btn-sm"
                                                               title="Manage Products">
                                                                <i class="fa fa-th-list"></i>
                                                            </a>
                                                            <form action="{{ route('admin.promo-tiles.destroy', $promoTile->id) }}"
                                                                  method="POST"
                                                                  class="d-inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                        class="btn btn-danger btn-sm"
                                                                        title="Delete"
                                                                        onclick="return confirm('Are you sure you want to delete this promo tile? Its linked products will also be removed.')">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="11" class="text-center">
                                                        No promo tiles found
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($promoTiles->hasPages())
                                    <div class="mt-3 d-flex justify-content-center">
                                        {{ $promoTiles->links('pagination::bootstrap-4') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.btn-group .btn {
    margin-right: 2px;
}
.table td {
    vertical-align: middle;
}
</style>

@endsection