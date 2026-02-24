@include('frontend.header')
<div class="content-top-breadcum">

</div>

<div id="common-home">
  <div class="container">
<div class="main-slider">
<div id="spinner"></div>
<div class="swiper-viewport">
  <div id="slideshow0" class="swiper-container">
    <div class="swiper-wrapper">
      @forelse(($indexBanners ?? collect()) as $banner)
        @php
          $bannerImage = $banner->banner_image
            ? asset('storage/index_banner/' . $banner->banner_image)
            : asset('assets/frontend/images/mainbanner.jpg');
        @endphp
        <div class="swiper-slide text-center">
          <a href="#">
            <img src="{{ $bannerImage }}"
                 alt="Main Banner {{ $loop->iteration }}"
                 class="img-responsive"
                 onerror="this.onerror=null;this.src='{{ asset('assets/frontend/images/mainbanner.jpg') }}';">
          </a>
        </div>
      @empty
        <div class="swiper-slide text-center">
          <a href="#">
            <img src="{{ asset('assets/frontend/images/mainbanner.jpg') }}" alt="Main Banner" class="img-responsive">
          </a>
        </div>
      @endforelse
    </div>
  </div>
  <div class="swiper-pagination slideshow0"></div>
  <div class="swiper-pager mainbanner">
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
  </div>
</div>
</div>
</div>


<div class="container">
  <div class="row">
                <div id="content" class="col-sm-12"><div class="breadcrumb"></div>  

<div class="category-banner-block wow fadeInUp" data-wow-duration="0.5s" data-wow-delay="0.5s">
<h3>sub category</h3>
<div class="manufacture-slider">
    @forelse(($topSubCategories ?? collect()) as $subCategory)
      @php
        $subCategoryImage = $subCategory->sub_category_image
          ? asset('storage/sub_category/' . $subCategory->sub_category_image)
          : asset('assets/frontend/images/no_image.png');
      @endphp
      <div class="product-layout">
        <div class="product-thumb transition">
          <div class="caption categoryname">
            <h4><a href="{{ route('frontend.products', ['sub_category' => $subCategory->id]) }}">{{ $subCategory->sub_category_name }}</a></h4>
          </div>
          <div class="image-cstm">
            <a href="{{ route('frontend.products', ['sub_category' => $subCategory->id]) }}">
              <img src="{{ $subCategoryImage }}" alt="{{ $subCategory->sub_category_name }}" title="{{ $subCategory->sub_category_name }}" class="img-responsive fixed-img">
            </a>
          </div>
        </div>
      </div>
    @empty
      <div class="product-layout">
        <div class="product-thumb transition">
          <div class="caption categoryname">
            <h4><a href="{{ route('frontend.products') }}">No sub category available</a></h4>
          </div>
          <div class="image">
            <a href="{{ route('frontend.products') }}">
              <img src="{{ asset('assets/frontend/images/no_image.png') }}" alt="No sub category available" title="No sub category available" class="img-responsive">
            </a>
          </div>
        </div>
      </div>
    @endforelse
  </div>
</div>


<div class="section featured">
<div class="section-heading">Product Offers</div>

<div class="owl-carousel product-carousel">
  @forelse(($productOffers ?? collect()) as $offer)
    @php
      $finalPrice = (float) ($offer->final_price ?? 0);
      $sellingPrice = (float) ($offer->selling_price ?? 0);
    @endphp
    <div class=" product-items ">
      <div class="product-thumb transition">
        <p class="tag">{{ (int) round((float) ($offer->offer_percentage ?? 0)) }}<br> % <br> <i>off</i></p>
        <div class="imageproduct-cstm">
          <div class="">
            <a href="{{ route('frontend.single_product', ['product_id' => $offer->product_id]) }}">
              <img src="{{ !empty($offer->product_image) ? asset('storage/product/' . $offer->product_image) : asset('assets/frontend/images/no_image.png') }}"
                   alt="{{ $offer->product_name }}"
                   title="{{ $offer->product_name }}"
                   class="img-responsive"
                   onerror="this.onerror=null;this.src='{{ asset('assets/frontend/images/no_image.png') }}';">
            </a>
          </div>
          <div class="saleback"><span class="sale">sale</span></div>
        </div>
        <div class="mt-3">
          <div class="caption">
            <h4><a href="{{ route('frontend.single_product', ['product_id' => $offer->product_id]) }}">{{ $offer->product_name }}</a></h4>
            <p class="price">
              @if($sellingPrice > $finalPrice && $finalPrice > 0)
                <span class="price-new">&#8377;{{ number_format($finalPrice, 2) }}</span>
                <span class="price-old">&#8377;{{ number_format($sellingPrice, 2) }}</span>
              @elseif($finalPrice > 0)
                &#8377;{{ number_format($finalPrice, 2) }}
              @else
                &#8377;{{ number_format($sellingPrice, 2) }}
              @endif
              <span class="price-tax">Ex Tax: &#8377;{{ number_format(($finalPrice > 0 ? $finalPrice : $sellingPrice), 2) }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  @empty
    <div class=" product-items ">
      <div class="product-thumb transition">
        <div class="image">
          <div class="">
            <a href="{{ route('frontend.single_product') }}">
              <img src="{{ asset('assets/frontend/images/no_image.png') }}" alt="No offers" title="No offers" class="img-responsive">
            </a>
          </div>
        </div>
        <div class="mt-3">
          <div class="caption">
            <h4><a href="{{ route('frontend.single_product') }}">No product offers available</a></h4>
          </div>
        </div>
      </div>
    </div>
  @endforelse
</div>
</div>


<div class="shipping-outer  wow fadeInUp" data-wow-duration="0.5s" data-wow-delay="0.5s">
<div class="shipping-inner">
<div class="heading col-md-3 col-sm-12 col-xs-12">
  <h2>Why choose us?</h2>
</div>
<div class="subtitle-part subtitle-part1 col-md-3 col-sm-4 col-xs-12">
<div class="subtitle-part-inner">
<div class="subtitile">
<div class="subtitle-part-image"></div>  
<div class="subtitile1">On time delivery</div>
<div class="subtitile2">15% back if not able</div>
</div>
</div>
</div>
<div class="subtitle-part subtitle-part2 col-md-3 col-sm-4 col-xs-12">
<div class="subtitle-part-inner">
<div class="subtitile">
<div class="subtitle-part-image"></div>  
<div class="subtitile1">Free delivery</div>
<div class="subtitile2">Order over $ 200</div>
</div>
</div>
</div>
<div class="subtitle-part subtitle-part3 col-md-3 col-sm-4 col-xs-12">
<div class="subtitle-part-inner">
<div class="subtitile">
<div class="subtitle-part-image"></div>  
<div class="subtitile1">Quality assurance</div>
<div class="subtitile2">You can trust us</div>
</div>
</div>
</div>
</div>
</div>


<div class="section featured">
<div class="section-heading">Featured Products</div>

<div class="owl-carousel product-carousel">
  @forelse(($featuredProducts ?? collect()) as $product)
    <livewire:frontend.featured-product-card :product="$product" :key="'featured-product-' . $product->id" />
  @empty
    <div class=" product-items ">
      <div class="product-thumb transition">
        <div class="image">
          <div class="first_image">
            <a href="{{ route('frontend.single_product') }}">
              <img src="{{ asset('assets/frontend/images/no_image.png') }}" alt="No featured products" title="No featured products" class="img-responsive">
            </a>
          </div>
        </div>
        <div class="product-details">
          <div class="caption">
            <h4><a href="{{ route('frontend.single_product') }}">No featured products available</a></h4>
          </div>
        </div>
      </div>
    </div>
  @endforelse
</div>
</div>

</div>
    </div>
	</div>
</div>

@include('frontend.footer')

