@extends('admin.dashboard.headerfooter')

@section('content')
<div class="main-panel">
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-xl-12 box-margin height-card">
                    <div class="card card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">Product Images Management</h4>
                            <a href="{{ route('admin.product-images.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> Add Product Images
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

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Product</th>
                                        <th>Sub Category</th>
                                        <th>Single Image 1</th>
                                        <th>Single Image 2</th>
                                        <th>Single Image 3</th>
                                        <th>Single Image 4</th>
                                        <th width="170">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $product)
                                        @php
                                            $defaultImage = asset('assets/admin/images/no_image.png');
                                            $image1 = $product->single_image_1 ? asset('storage/product/single/' . $product->single_image_1) : $defaultImage;
                                            $image2 = $product->single_image_2 ? asset('storage/product/single/' . $product->single_image_2) : $defaultImage;
                                            $image3 = $product->single_image_3 ? asset('storage/product/single/' . $product->single_image_3) : $defaultImage;
                                            $image4 = $product->single_image_4 ? asset('storage/product/single/' . $product->single_image_4) : $defaultImage;
                                            $hasImages = $product->single_image_1 || $product->single_image_2 || $product->single_image_3 || $product->single_image_4;
                                        @endphp
                                        <tr>
                                            <td>{{ $product->id }}</td>
                                            <td>{{ $product->product_name }}</td>
                                            <td>{{ $product->subCategory ? $product->subCategory->sub_category_name : '-' }}</td>
                                            <td>
                                                <img src="{{ $image1 }}" alt="Single Image 1"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </td>
                                            <td>
                                                <img src="{{ $image2 }}" alt="Single Image 2"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </td>
                                            <td>
                                                <img src="{{ $image3 }}" alt="Single Image 3"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </td>
                                            <td>
                                                <img src="{{ $image4 }}" alt="Single Image 4"
                                                     style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    @if($hasImages)
                                                        <a href="{{ route('admin.product-images.edit', $product->id) }}"
                                                           class="btn btn-warning btn-sm"
                                                           title="Edit">
                                                            <i class="fa fa-edit"></i>
                                                        </a>
                                                        <form action="{{ route('admin.product-images.destroy', $product->id) }}"
                                                              method="POST"
                                                              class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit"
                                                                    class="btn btn-danger btn-sm"
                                                                    title="Delete"
                                                                    onclick="return confirm('Are you sure you want to delete all single images for this product?')">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    @else
                                                        <a href="{{ route('admin.product-images.create', ['product_id' => $product->id]) }}"
                                                           class="btn btn-primary btn-sm"
                                                           title="Add Images">
                                                            <i class="fa fa-plus"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No products found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
