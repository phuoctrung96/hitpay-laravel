@php($hasDescription = strlen($product->description) > 0)
@php($customisation = $business->checkoutCustomisation())
@php($customStyles = $business->getStoreCustomisationStyles())
@php($product_image = is_array($product_images) ? $product_images[0] : $product_images)
@php($json_product_images = json_encode($product_images))
@php($umami = config('checkout.umamiUrl'))
@extends('shop.layouts.app', [
    'title' => $product->name.' by '.$business->getName(),
])

@push('head-script')
  @if ($umami)
    <script defer data-website-id="{{ config('checkout.umamiStoreFrontId') }}" src="{{ $umami }}"></script>
  @endif
@endpush

@push('head-stack')
    <meta property="og:title" content="{{ $product->name }}">
    <meta name="twitter:title" content="{{ $product->name }}">
    @if ($hasDescription)
        <meta property="og:description" content="{{ str_limit($product->description) }}">
        <meta name="twitter:description" content="{{ str_limit($product->description) }}">
    @endif
    @if (isset($product_image))
        <meta property="og:image" content="{{ $product_image }}">
        <meta name="twitter:image" content="{{ $product_image }}">
        <meta name="twitter:card" content="summary_large_image">
    @elseif (isset($business_logo))
        <meta property="og:image" content="{{ $business_logo }}">
        <meta name="twitter:image" content="{{ $business_logo }}">
        <meta name="twitter:card" content="summary_large_image">
    @endif
    <meta property="og:url" content="{{ $product->shortcut_id ? route('shortcut', $product->shortcut_id) : route('shop.product', [
        $business->getKey(),
        $product->getKey(),
    ]) }}">

    <style>
        .cart-info-btn {
            border-radius: 0.5rem;
            background-color: {{$customStyles['main_color']}};
            color: {{$customStyles['main_text_color']}};
        }

        .radio-toolbar input[type="radio"]:checked + label {
            background-color: {{$customStyles['main_color']}};
            color: {{$customStyles['main_text_color']}};
        }

        .splide__pagination__page.is-active{
            background: #002771 !important;
            transform: scale(1) !important;
        }

        .product-images-mobile {
            display: none;
        }

        .col-product-images .product-image-normal{
            width: 100%;
        }

        .ct-product{
            padding: 63px 0px 0px;
        }

        .col-product-information h3{
            font-size: 40px;
            margin: 0px 0px 13px;
        }

        .col-product-information .category{
            font-size: 18px;
            color: #6D6E73;
            margin: 0px 0px 29px;
        }

        .col-product-information .category span{
            position: relative;
        }

        .col-product-information .category span:after{
            content: ',';
        }

        .col-product-information .category span:last-child:after{
            display: none;
        }

        .col-product-information .information{
            color: #1E1E1F;
            font-size: 16px;
            padding:  0px 0px 41px;
        }

        .product-item-image{
            width: 100%;
            height: auto;
        }

        .m-slider .splide__pagination{
            bottom: -30px;
            display: none;
            text-align: center;
        }

        @media (max-width: 767px) {
            .product-images-mobile {
              display: block;
            }

            .product-images {
                display: none;
            }

            .col-product-images{
                padding:  0px 0px 71px;
            }

            .col-product-images .product-image-normal{
                padding:  0px 15px;
            }

            .product-item-image{
                padding-left: 15px;
                padding-right: 15px;
            }

            .ct-product{
                padding: 33px 0px 0px;
            }

            .col-product-information h3{
                font-size: 24px;
                text-align: center;
                margin: 0px 0px 9px;
            }

            .col-product-information .category{
                font-size: 16px;
                text-align: center;
                margin: 0px 0px 20px;
            }

            .col-product-information .information{
                padding:  0px 0px 25px;
            }
        }

        @media (min-width: 1200px) {
            .col-product-information{
                padding-left: 54px;
            }
        }

    </style>
@endpush
@section('app-content')
<div class="main-app-content">
    <div class="container">
        <div class="g-top-meta">
            <a href="{{ url()->previous() }}" class="btn-back"><img src="{{asset('/images/back_icon.svg')}}"/> Back</a>
        </div>
        <div class="ct-product">
            <div class="row">
                <div class="col-md-6 col-lg-5 col-product-images">
                    @if(is_array($product_images))
                        @if(count($product_images) === 1)
                            <img src="{{$product_images[0]}}" alt="product" class="product-item-image">
                        @else
                            <product-images :images="{{$json_product_images}}" class="product-images"></product-images>
                            <product-images-mobile :images="{{$json_product_images}}" class="product-images-mobile"></product-images-mobile>
                        @endif
                    @else
                        <div class="text-center">
                            <img src="{{ $product_images }}" class="product-image-normal"
                                 alt="{{ $product->name }}"/>
                        </div>
                    @endif
                </div>
                <div class="col-md-6 col-lg-7 col-product-information">
                    <h3 class="p-title">{{ $product->name }}</h3>
                    @if($product->business_product_category_id)
                        <div class="category">
                            @foreach($product->business_product_category_id as $category)
                                <span>{{$category->name}}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($hasDescription)
                        <div class="information">
                            @foreach (preg_split('/\n|\r\n?/', $product->description) as $line)
                                @if (($line = trim($line)) && strlen($line) > 0)
                                    {!! $line !!}
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if($is_product_available)
                        <add-product :customisation="{{ $customisation }}"></add-product>
                    @else
                        <div class="alert alert-danger border-top-0 border-left-0 border-right-0 rounded-0 mb-0">
                            <div class="container-fluid text-center">
                                <p class="small mb-0">The Product is not available. Contact the seller for further
                                    information
                                </p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('body-stack')
    <script src="https://js.stripe.com/v3/"></script>
    <script type="text/javascript" defer>
        window.Business = @json((new \App\Http\Resources\Business($business))->toArray(request()->instance()));
        window.Product = @json((new \App\Http\Resources\Business\Product($product))->toArray(request()->instance()));
    </script>
@endpush
