@include('frontend.header')
<div class="content-top-breadcum">

</div>

<div id="product-category" class="container category">
  <ul class="breadcrumb">
    <li><a href="{{ route('frontend.home') }}"><i class="fa fa-home"></i></a></li>
    <li><a href="{{ route('frontend.products') }}">Products</a></li>
    @if($selectedSubCategory)
      <li><a href="{{ route('frontend.products', ['sub_category' => $selectedSubCategory->id]) }}">{{ $selectedSubCategory->sub_category_name }}</a></li>
    @endif
  </ul>
  <div class="row">
    <aside id="column-left" class="col-sm-3 hidden-xs" style="background-color: #fff;">
      <div class="sidebar section sidebar_category">
        <div class="section-heading"><div class="border"></div>Categories</div>
        <div class="section-block category_block">
          <ul class="left-category treeview-list treeview">
            @forelse(($menuCategories ?? collect()) as $category)
              @php
                $isCategoryActive = $selectedSubCategory && (int) $selectedSubCategory->category_id === (int) $category->id;
              @endphp
              <li class="{{ $isCategoryActive ? 'cat-active collapsable' : 'expandable' }}">
                <a href="{{ route('frontend.products') }}" class="{{ $isCategoryActive ? 'active' : '' }}">
                  {{ $category->category_name }}
                </a>
                @if($category->subCategories->isNotEmpty())
                  <ul class="menu-dropdown {{ $isCategoryActive ? 'in collapsable' : '' }}">
                    @foreach($category->subCategories as $subCategory)
                      <li>
                        <a href="{{ route('frontend.products', ['sub_category' => $subCategory->id]) }}"
                           class="{{ $selectedSubCategory && (int) $selectedSubCategory->id === (int) $subCategory->id ? 'active' : '' }}">
                          {{ $subCategory->sub_category_name }}
                        </a>
                      </li>
                    @endforeach
                  </ul>
                @endif
              </li>
            @empty
              <li class="expandable">
                <a href="{{ route('frontend.products') }}">No categories found</a>
              </li>
            @endforelse
          </ul>
        </div>
      </div>

    </aside>

    <div id="content" class="col-sm-9">
      <h1>{{ $selectedSubCategory ? $selectedSubCategory->sub_category_name : 'All Products' }}</h1>

      <div class="subcateory">
        <h3>Refine Search</h3>
        <div class="row">
          <div class="col-sm-3">
            <ul>
              <li><a href="#">Daal & Pulses</a></li>
              <li><a href="#">Dry Fruits & Nuts</a></li>
              <li><a href="#">Edible Oils</a></li>
              <li><a href="#">Riced cauliflower</a></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="filter-product">
        <div class="btn-grid-list">
          <div class="btn-group btn-group-sm">
            <button type="button" id="filter-view" class="btn btn-default filter collapsed" data-target="#" data-toggle="collapse" title="Refine Search"><i class="fa fa-th-list"></i></button>
            <button type="button" id="grid-view" class="btn btn-default grid" data-toggle="tooltip" title="Grid"><i class="fa fa-th"></i></button>
            <button type="button" id="list-view" class="btn btn-default list" data-toggle="tooltip" title="List"><i class="fa fa-th-list"></i></button>
          </div>
        </div>
        <div class="compare-total"><a href="#" id="compare-total">Product Compare (0)</a></div>
        <div class="filter-product-right text-right">
          <div class="sort-filter">
            <div class="col-xs-4 col-sm-4 col-md-4 text-right">
              <label class="control-label" for="input-sort">Sort By:</label>
            </div>
            <div class="col-xs-8 col-sm-8 col-md-8 text-right">
              <select id="input-sort" class="form-control">
                <option selected="selected">Default</option>
              </select>
            </div>
          </div>
          <div class="show-filter">
            <div class="col-xs-6 col-sm-6 col-md-6 text-right">
              <label class="control-label" for="input-limit">Show:</label>
            </div>
            <div class="col-xs-6 col-sm-6 col-md-6 text-right">
              <select id="input-limit" class="form-control">
                <option selected="selected">12</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <div class="products-collection">
        <div class="row product-layoutrow">
          @forelse(($products ?? collect()) as $product)
            <livewire:frontend.product-list-card
              :product="$product"
              :key="'product-list-card-' . $product->id" />
          @empty
            <div class="col-sm-12">
              <div class="alert alert-info">
                No products found{{ $selectedSubCategory ? ' for selected sub category' : '' }}.
              </div>
            </div>
          @endforelse
        </div>
      </div>

      <div class="row">
        <div class="col-sm-6 text-left"></div>
        <div class="col-sm-6 text-right">Showing {{ ($products ?? collect())->count() }} products</div>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    function applyCurrentDisplayMode() {
      var mode = localStorage.getItem('display') === 'list' ? 'list' : 'grid';
      if (mode === 'list') {
        $('#list-view').trigger('click');
      } else {
        $('#grid-view').trigger('click');
      }
    }

    document.addEventListener('livewire:initialized', function () {
      applyCurrentDisplayMode();

      if (window.Livewire && typeof window.Livewire.hook === 'function') {
        try {
          window.Livewire.hook('message.processed', function () {
            applyCurrentDisplayMode();
          });
        } catch (e) {}

        try {
          window.Livewire.hook('morph.updated', function () {
            applyCurrentDisplayMode();
          });
        } catch (e) {}
      }
    });
  })();
</script>

@include('frontend.footer')
