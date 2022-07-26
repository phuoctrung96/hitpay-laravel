<?php

namespace App\Http\Controllers\Shop;

use App\Actions\Business\OnlineStore\Discounts\DiscountCalculator;
use App\Business;
use App\Business\ProductVariation;
use Exception;
use HitPay\Stripe\Charge;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Stripe\ApplePayDomain;

class CartController extends Controller
{
    /**
     * Add product to cart.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addProduct(Request $request, Business $business)
    {
        $cart = $this->getCart($request, $business);

        // Let's try to search variation again.
        if ($variationId = $request->get('variation_id')) {
            $variation = $business->productVariations()->with('product')->find($variationId);

            if (!$variation->product->isPublished()) {
                $variationIdExceptions = $variation->product->variations->pluck('id');
            } elseif ($variation instanceof ProductVariation) {
                if ($variation->product->isManageable()) {
                    $quantityMaxRule = 'max:' . $variation->quantity;
                }
            }
        }

        $rules = [
            'variation_id' => [
                'required',
                'string',
            ],
            'quantity' => [
                'required',
                'int',
                'min:0',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        if (isset($variation)) {
            if (isset($variationIdException)) {
                $rules['variation_id'][] = Rule::notIn($variationIdExceptions);
                $rules['variation_id'][] = Rule::exists('business_products')->where('business_id', $business->getKey());
            }

            if (isset($quantityMaxRule)) {
                $rules['quantity'][] = $quantityMaxRule;
            }
        } else {
            $rules['variation_id'][] = Rule::exists('business_products')->where('business_id', $business->getKey());
        }

        $data = $this->validate($request, $rules);

        if (!isset($variation)) {
            $variation = $business->productVariations()->with('product')->find($data['variation_id']);
        }

        $quantity = $cart['products'][$variation->getKey()]['quantity'] ?? 0;

        $cart['products'][$variation->getKey()] = [
            'variation_id' => $variation->getKey(),
            'quantity' => $quantity + $data['quantity'],
            'remark' => $data['remark'] ?? null,
        ];

        $this->updateCart($request, $business, $cart);

        return Response::json($cart);
    }

    /**
     * Update a product in cart.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param string $index
     *
     * @throws \Illuminate\Validation\ValidationException
     * @throws Exception
     */
    public function updateProduct(Request $request, Business $business, string $index)
    {
        $cart = $this->getCart($request, $business);

        if (!isset($cart['products'][$index])) {
            App::abort(403, ' The given index of product is not found.');
        }

        $variation = $business->productVariations()->with('product')->find($cart['products'][$index]['variation_id']);

        if (!$variation instanceof ProductVariation || !$variation->product->isPublished()) {
            unset($cart['products'][$index]);

            $this->updateCart($request, $business, $cart);

            App::abort(403, ' The product added is no longer available.');
        }

        if ($variation->product->isManageable()) {
            $quantityMaxRule = 'max:' . $variation->quantity;
        }

        $rules = [
            'quantity' => [
                'required',
                'int',
                'min:0',
            ],
            'remark' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];

        if (isset($quantityMaxRule)) {
            $rules['quantity'][] = $quantityMaxRule;
        }

        $data = $this->validate($request, $rules);

        if ($data['quantity'] === 0) {
            unset($cart['products'][$index]);

            $this->updateCart($request, $business, $cart);
        } else {
            $cart['products'][$index]['quantity'] = $data['quantity'];
            $cart['products'][$index]['remark'] = $data['remark'] ?? null;
        }

        $this->updateCart($request, $business, $cart);

        $variations = $business->productVariations()->with([
            'product' => function (BelongsTo $query) {
                $query->with('images');
            },
        ])->findMany(Collection::make($cart['products'])->pluck('variation_id'));

        $totalCartAmount = 0;
        $totalCartQuantity = 0;

        foreach ($cart['products'] as $key => $value) {
            $variation = $variations->find($value['variation_id']);

            if (!$variation instanceof ProductVariation) {
                continue;
            }
            $totalCartAmount = $totalCartAmount + bcmul($value['quantity'], $variation->price);
            $totalCartQuantity = $totalCartQuantity + $value['quantity'];
        }

        $discount = DiscountCalculator::withBusiness($business)->setCart($cart)->process();

        $data = [
            'discount' => $discount
        ];

        return json_encode($data);
    }

    /**
     * Delete a product from cart.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param string|null $index
     */
    public function removeProduct(Request $request, Business $business, string $index = null)
    {
        if (is_null($index)) {
            $this->updateCart($request, $business);
        } else {
            $cart = $this->getCart($request, $business);

            if (isset($cart['products'][$index])) {
                unset($cart['products'][$index]);
            }

            if (count($cart)) {
                $this->updateCart($request, $business, $cart);
            } else {
                $this->updateCart($request, $business);
            }
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \Illuminate\Http\Response
     * @throws \ReflectionException
     * @throws Exception
     */
    public function showCartPage(Request $request, Business $business)
    {
        $cart = $this->getCart($request, $business);

        $variations = $business->productVariations()->with([
            'product' => function (BelongsTo $query) {
                $query->with('images');
            },
        ])->findMany(Collection::make($cart['products'])->pluck('variation_id'));

        $variationsArray = [];
        $totalCartAmount = 0;

        foreach ($cart['products'] as $key => $value) {
            $variation = $variations->find($value['variation_id']);

            if (!$variation instanceof ProductVariation) {
                continue;
            }

            $totalCartAmount = $totalCartAmount + bcmul($value['quantity'], $variation->price);

            $variationsArray[$key] = [
                'cart' => $value,
                'model' => $variation->toArray(),
                'image' => optional($variation->product->images->first())->getUrl(),
            ];
        }

        $discount = DiscountCalculator::withBusiness($business)->setCart($cart)->process();

        return Response::view('shop.cart', [
            'checksum' => $cart['checksum'],
            'business' => $business,
            'variations' => $variationsArray,
            'discount' => $discount
        ]);
    }

    private function getCart(Request $request, Business $business)
    {
        return $request->session()->get('cart-' . $business->getKey(), [
            'checksum' => '',
            'products' => [
                //
            ],
        ]);
    }

    private function updateCart(Request $request, Business $business, array $cart = null)
    {
        if (is_null($cart)) {
            $request->session()->forget('cart-' . $business->getKey());
        } else {
            unset($cart['checksum']);

            $cart['checksum'] = md5(json_encode($cart));

            $request->session()->put('cart-' . $business->getKey(), $cart);
        }
    }
}
