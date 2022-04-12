<?php

namespace App\Http\Controllers\Shop;

use App\Business;
use App\Business\ProductCategory;
use App\Business\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;

class HomeController extends Controller
{
    /**
     * Show homepage.
     *
     * @return \Illuminate\Http\Response
     */
    public function showHomepage()
    {
        return Response::view('shop.home');
    }

    /**
     * Show business homepage.
     *
     * @return \Illuminate\Http\Response
     */
    public function showBusinessHomepage(Business $business)
    {
        $products = $business->products()->whereNotNull('published_at')->get();

        $product_attrs = [];
        $featured_product_attrs = [];
        $featured_products = [];

        foreach ($products as $product) {
            $product_attrs['image'][] = $product->display('image');
            $product_attrs['price'][] = $product->display('price');
            $product_attrs['available'][] = $product->isAvailable();

            if($product->is_pinned) {
                $featured_product_attrs['image'][] = $product->display('image');
                $featured_product_attrs['price'][] = $product->display('price');
                $featured_product_attrs['available'][] = $product->isAvailable();

                $productObj = $this->getProductObject($product, $business);
                array_push($featured_products, $productObj);
            }
        }

        $categories = $business->productCategories()->where('active', 1)->get();

        return Response::view('shop.business', compact('products', 'business', 'product_attrs', 'categories', 'featured_products', 'featured_product_attrs'));
    }

    private function getProductObject(Product $product)
    {
        if (!$product->shortcut_id) {
            $shortcut = $product->shortcut()->create([
                'route_name' => 'shop.product',
                'parameters' => [
                    'business' => $product->business_id,
                    'product_id' => $product->getKey(),
                ],
            ]);

            $product->shortcut_id = $shortcut->getKey();
            $product->save();
        }
        $data['id'] = $product->id;
        $data['name'] = $product->name;
        $data['description'] = $product->description;
        $data['currency'] = $product->currency;
        $data['price'] = $product->price;
        $data['stock_keeping_unit'] = $product->stock_keeping_unit;
        $data['categories'] = $product->business_product_category_id;
        $data['readable_price'] = $product->readable_price;
        $data['is_manageable'] = $product->quantity > 0;
        $data['is_published'] = $product->published_at instanceof Carbon;
        $data['has_variations'] = $product->variations_count > 1;
        $data['variations_count'] = $product->variations_count;
        $data['checkout_url'] = $product->shortcut_id
            ? URL::route('shortcut', $product->shortcut_id)
            : URL::route('shop.product', [
                $product->business_id,
                $product->getKey(),
            ]);
        if ($product->variations_count > 1) {
            $data['variation_types'] = array_filter([
                $product->variation_key_1,
                $product->variation_key_2,
                $product->variation_key_3,
            ]);
        } elseif ($data['is_manageable']) {
            $data['quantity'] = $product->variations[0]->quantity;
            $data['quantity_alert_level'] = $product->variations[0]->quantity_alert_level;
        }

        $data['variations'] = [];

        foreach ($product->variations as $variation) {
            $variationData = [
                'id' => $variation->id,
                'description' => $variation->description,
                'values' => [
                    [
                        'key' => $product->variation_key_1,
                        'value' => $variation->variation_value_1,
                    ],
                    [
                        'key' => $product->variation_key_2,
                        'value' => $variation->variation_value_2,
                    ],
                    [
                        'key' => $product->variation_key_3,
                        'value' => $variation->variation_value_3,
                    ],
                ],
                'price' => getReadableAmountByCurrency($product->currency, $variation->price),
            ];

            if ($data['is_manageable']) {
                $variationData['quantity'] = $variation->quantity;
                $variationData['quantity_alert_level'] = $variation->quantity_alert_level;
            }

            $data['variations'][] = $variationData;
        }

        if ($product->relationLoaded('images')) {
            foreach ($product->images as $image) {
                $data['image'][] = [
                    'id' => $image->getKey(),
                    'url' => $image->getUrl(),
                ];
            }
        }

        $data['is_pinned'] = $product->is_pinned;
        $data['created_at'] = $product->created_at->toAtomString();
        $data['updated_at'] = $product->updated_at->toAtomString();

        return $data;
    }

    public function getProductWithCategory(Business $business, Request $request)
    {
        $category_id = $request->category_id;
        $products = $business->products()->whereNotNull('published_at')->get();

        if ($category_id != 'home') {
            foreach ($products as $key => $product) {
                if ($product->business_product_category_id) {
                    $flag = false;
                    foreach ($product->business_product_category_id as $product_category) {
                        if ($product_category->id == $category_id) {
                            $flag = true;
                            break;
                        }
                    }
                    if (!$flag) $products->forget($key);
                } else $products->forget($key);
            }
        }

        $product_attrs = [];
        $featured_product_attrs = [];
        $featured_products = [];

        foreach ($products as $product) {
            $product_attrs['image'][] = $product->display('image');
            $product_attrs['price'][] = $product->display('price');
            $product_attrs['available'][] = $product->isAvailable();

            if($product->is_pinned) {
                $featured_product_attrs['image'][] = $product->display('image');
                $featured_product_attrs['price'][] = $product->display('price');
                $featured_product_attrs['available'][] = $product->isAvailable();

                $productObj = $this->getProductObject($product, $business);
                array_push($featured_products, $productObj);
            }
        }

        return Response::json(['products' => array_values($products->toArray()),
            'product_attrs' => $product_attrs,
            'featured_products' => $featured_products,
            'featured_product_attrs' => $featured_product_attrs
            ]);
    }



    public function searchProducts(Business $business, Request $request)
    {
        $search = $request->post('query');

        $query = $business
            ->setConnection('mysql_read')
            ->products()
            ->whereRaw(" MATCH (name) AGAINST (? IN BOOLEAN MODE)", $search)
            ->whereNotNull('published_at');

        $products = $query->get();

        $product_attrs = [];
        $featured_product_attrs = [];
        $featured_products = [];

        foreach ($products as $product) {
            $product_attrs['image'][] = $product->display('image');
            $product_attrs['price'][] = $product->display('price');
            $product_attrs['available'][] = $product->isAvailable();

            if($product->is_pinned) {
                $featured_product_attrs['image'][] = $product->display('image');
                $featured_product_attrs['price'][] = $product->display('price');
                $featured_product_attrs['available'][] = $product->isAvailable();

                $productObj = $this->getProductObject($product);
                $featured_products[] = $productObj;
            }
        }

        return Response::json(['products' => array_values($products->toArray()),
            'product_attrs' => $product_attrs,
            'featured_products' => $featured_products,
            'featured_product_attrs' => $featured_product_attrs
        ]);
    }


    public function showIntroduction(Business $business)
    {
        return Response::view('shop.intro', compact('business'));
    }
}
