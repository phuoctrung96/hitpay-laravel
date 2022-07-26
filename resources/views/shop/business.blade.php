@extends('shop.layouts.app', [
    'title' =>'Home',
    'app_name' => $business->getName(),
])

@php
    $customisation = $business->checkoutCustomisation();
    $customStyles = $business->getStoreCustomisationStyles();
    $cover = $business->coverImage;
    if ($cover) $cover_image = $cover->getUrl();

    $is_have_coupon = false;
    $umami = config('checkout.umamiUrl');
@endphp

@push('head-script')
  @if ($umami)
    <script defer data-website-id="{{ config('checkout.umamiStoreFrontId') }}" src="{{ $umami }}"></script>
  @endif
@endpush

@push('head-stack')
    <style>

        category__link {
           font-size: 1.05em;
           color: #929292;
           display: block;
           padding: 1em;
           cursor: pointer;
           -webkit-user-select: none;
           -moz-user-select: none;
           -ms-user-select: none;
           user-select: none;
           -webkit-touch-callout: none;
           -khtml-user-select: none;
           -webkit-tap-highlight-color: rgba(0, 0, 0, 0);
        }

        category__link:hover,
        category__link:focus {
           outline: none;
        }

        .category__item--current .category__link {
            color: #FFF;
            font-weight: 500;
            background-color: {{$customStyles['main_color']}};
        }

        input[type="radio"]:checked + label {
            background-color: {{$customStyles['main_color']}};
            color: {{$customStyles['main_text_color']}};
        }

        .app-cover-image {
            width: 100%;
            height: 475px;
        }

        @media (max-width: 767px) {
            .app-cover-image {
                height: 375px;
            }
        }
    </style>
@endpush

@section('app-content')
    <div class="app-banner">
        @foreach($business->coupons as $coupon)
            @if($coupon->is_promo_banner)
                <div class="promo-banner">
                    <div class="container">
                        <span class="banner-text">{{$coupon->banner_text}}</span>
                    </div>
                </div>
                <?php $is_have_coupon = true; ?>
                @break
            @endif
        @endforeach

        @if(!$is_have_coupon)
            @foreach($business->discounts as $discount)
                @if($discount->is_promo_banner)
                    <div class="promo-banner">
                        <div class="container">
                            <span class="banner-text">{{$discount->banner_text}}</span>
                        </div>
                    </div>
                    @break
                @endif
            @endforeach
        @endif
    </div>
    @isset ($cover_image)
    <div class="app-cover-image" style="background: url({{ $cover_image }}); background-size: cover; background-repeat: no-repeat; background-size: cover; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover;">
    </div>
    @endisset
    <div class="main-app-content homepage">
        <product-list :customisation="{{ $customisation }}"></product-list>
    </div>
@endsection

@push('body-stack')
    <script>
        window.Business = @json($business);
        window.Products = @json($products);
        window.ProductAttrs = @json($product_attrs);
        window.Categories = @json($categories);
        window.FeaturedProducts = @json($featured_products);
        window.FeaturedProductsAttrs = @json($featured_product_attrs);
    </script>
@endpush
