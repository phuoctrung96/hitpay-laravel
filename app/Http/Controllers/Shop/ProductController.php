<?php

    namespace App\Http\Controllers\Shop;

    use App\Business;
    use App\Business\Product;
    use App\Exports\FacebookProductsExport;
    use Exception;
    use HitPay\Stripe\Charge;
    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\App;
    use Illuminate\Support\Facades\Config;
    use Illuminate\Support\Facades\Log;
    use Illuminate\Support\Facades\Response;
    use Maatwebsite\Excel\Facades\Excel;
    use Stripe\ApplePayDomain;

    class ProductController extends Controller
    {
        public function showProductPage(Request $request, Business $business, Product $product)
        {

            if (!$business->can_pick_up && $business->shippings_count < 1) {
                App::abort(404);
            } elseif ($business->paymentProviders->count() < 1) {
                App::abort(404);
            }

            $isProductAvailable = $this->isProductAvailable($product, false);

            $checkoutOptions = $this->getCheckoutOptions($business);

            $productImages = $product->display('images', null, true);

            return Response::view('shop.product', [
                'business' => $business,
                'product' => $product,
                'product_images' => $productImages ?? asset('hitpay/images/product.jpg'),
                'checkout_options' => $checkoutOptions,
                'is_product_available' => $isProductAvailable
            ]);
        }
        public function generateFBFeedProducts($slot)
        {
            if (!strlen($slot)) {
                App::abort(404);
            }
            $business = Business::with(
                [
                    "products" => function ($q) {
                        $q->whereNotNull('published_at');
                    }])
                ->where('fb_feed_slot', $slot)->first();
            if (!isset($business->id)) {
                App::abort(404);
            }
            $products = $business->products;
            $product_header = array(
                'id',
                'title',
                'description',
                'availability',
                'condition',
                'price',
                'link',
                'image_link',
                'brand',
            );
            $products_content = array();
            foreach ($products as $key => $product) {
                $products_item = array();
                $products_item[] = $product->id;
                $products_item[] = $product->name;
                $products_item[] = strlen($product->description)?$product->description: 'No description';
                $products_item[] =  ((isset($product->quantity) && $product->quantity > 0) || !isset($product->quantity) )?'in stock' : 'out of stock ';
                $products_item[] =  'new';
                $products_item[] =  getFormattedAmount($business->currency, $product->price, true, true);
                $products_item[] =  'https://'.Config::get('app.subdomains.shop').'/s/'.$product->shortcut_id;
                $products_item[] = $product->display('image');
                $products_item[] = 'The '. \config('app.name'). ' '. $business->getName();
                array_push($products_content, $products_item);
            }
            array_unshift($products_content, $product_header);
            $filename = 'Facebook_product_feed-' . $slot . '.csv';
            return Excel::download(new FacebookProductsExport($products_content), $filename, \Maatwebsite\Excel\Excel::CSV)->send();
        }
    }
