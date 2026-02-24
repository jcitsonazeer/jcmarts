@include('frontend.header')
<div class="content-top-breadcum">

</div>

@php
    $defaultImage = asset('assets/frontend/images/no_image.png');
    $fallbackImage = !empty($product->product_image) ? asset('storage/product/' . $product->product_image) : $defaultImage;
    $galleryList = collect($galleryImages ?? []);

    if ($galleryList->isEmpty()) {
        $galleryList = collect([$fallbackImage]);
    } else {
        $galleryList = $galleryList->map(function ($img) {
            return asset('storage/product/single/' . $img);
        });
    }

@endphp

<div id="product-product" class="container product">
    <ul class="breadcrumb">
        <li><a href="{{ route('frontend.home') }}"><i class="fa fa-home"></i></a></li>
        <li><a href="{{ route('frontend.products') }}">Products</a></li>
        @if($product->subCategory)
            <li><a href="{{ route('frontend.products', ['sub_category' => $product->subCategory->id]) }}">{{ $product->subCategory->sub_category_name }}</a></li>
        @endif
        <li><a href="javascript:void(0);">{{ $product->product_name }}</a></li>
    </ul>

    <div class="row">
        <div id="content" class="productpage col-sm-12">
            <div class="row">
                <div class="col-sm-6 col-md-5 left">
                    <div class="thumbnails">
                        <div class="product-additional-block swiper-viewport">
                            <div class="image-additional">
                                @foreach($galleryList as $imageIndex => $imagePath)
                                    <div class="item">
                                        <div class="product-thumb">
                                            <a href="javascript:void(0);" title="{{ $product->product_name }}" class="{{ $imageIndex === 0 ? 'active' : '' }}">
                                                <img src="{{ $imagePath }}"
                                                     width="126"
                                                     height="151"
                                                     title="{{ $product->product_name }}"
                                                     alt="{{ $product->product_name }}"
                                                     onerror="this.onerror=null;this.src='{{ $defaultImage }}';">
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-7 right">
                    <h1>{{ $product->product_name }}</h1>

                    <ul class="list-unstyled detail">
                        <li class="manufacturer">
                            <div class="label">   {{ $product->product_name }} </div>
                          
                        </li>
                        <li class="stock">In Stock</li>
                    </ul>

                    <livewire:frontend.single-product-rate-selector
                        :product="$product"
                        :key="'single-product-rate-' . $product->id" />

                    <div class="addthis_inline_share_toolbox"></div>
                </div>

                <div class="col-sm-12 producttab">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#tab-description" data-toggle="tab">Description</a></li>
                        <li><a href="#tab-specification" data-toggle="tab">Specification</a></li>
                        <li><a href="#tab-review" data-toggle="tab">Reviews (0)</a></li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane active" id="tab-description">
                            @if(!empty($product->description))
                                <p>{{ $product->description }}</p>
                            @else
                                <p>Description not available.</p>
                            @endif
                        </div>

                        <div class="tab-pane" id="tab-specification">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <td colspan="2"><strong>Product Details</strong></td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td>Sub Category</td>
                                    <td>{{ $product->subCategory ? $product->subCategory->sub_category_name : '-' }}</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="tab-review">
                            <form class="form-horizontal" id="form-review">
                                <div id="review"></div>
                                <h2>Write a review</h2>
                                Please <a href="javascript:void(0);">login</a> or <a href="javascript:void(0);">register</a> to review
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<div class="section related wow fadeInUp" data-wow-duration="0.5s" data-wow-delay="0.5s">
<div class="section-heading">Related Products</div>

<div class="section-block">
<div class="section-product section-product product-carousel">
@forelse(($relatedProducts ?? collect()) as $relatedProduct)
    <livewire:frontend.featured-product-card
        :product="$relatedProduct"
        :key="'related-product-' . $relatedProduct->id" />
@empty
    <div class="slider-item">
        <div class="product-thumb transition">
            <div class="product-details">
                <div class="caption">
                    <h4>No related products found</h4>
                </div>
            </div>
        </div>
    </div>
@endforelse
</div>
</div>

</div>
        <div class="prodbottominfo">
    <ul class="list-unstyled">                    
                    <li data-toggle="tooltip" title="Worldwide Shipping">
                      <img src="{{ asset('assets/frontend/images/world.png') }}" alt=""> 
                    </li>
                    <li data-toggle="tooltip" title="100% Original Product">
                      <img src="{{ asset('assets/frontend/images/original.png') }}" alt=""> 
                    </li>
                    <li data-toggle="tooltip" title="Best Price Guaranteed">
                      <img src="{{ asset('assets/frontend/images/inquire.png') }}" alt=""> 
                    </li>
                     <li title="COD Available in India" data-toggle="tooltip">
                       <img src="{{ asset('assets/frontend/images/cod.png') }}" alt=""> 
                    </li>
                </ul>
  </div>


 <div class="testimonial-block section fadeInUp" data-wow-duration="0.5s" data-wow-delay="0.5s">
 <div class="section-heading">Testimonial</div>
<div class="testimonial owlCarousel">


               <div class="item">
               <img class="testimonial-img img-responsive" src="{{ asset('assets/frontend/images/person1.jpg') }}" alt="#">
              <div class="testmonial-author-name">johny walker</div>
              <div class="testmonial-author">php Developer</div>
              <div class="desc">It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution</div>

             </div>
               <div class="item">
               <img class="testimonial-img img-responsive" src="{{ asset('assets/frontend/images/person2.jpg') }}" alt="#">
              <div class="testmonial-author-name">johny walker</div>
              <div class="testmonial-author">Lorem ipsum</div>
              <div class="desc">Very nice product ...................!</div>

             </div>
        
  </div>
   </div>



</div>
    
</div>

@include('frontend.footer')


