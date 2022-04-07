<?php

namespace App\Http\Controllers;

use App\Business\ProductBase;
use Illuminate\Support\Facades\Response;

class CheckoutRedirectionController extends Controller
{
    public function productId(string $id)
    {
        $product = ProductBase::where('id', $id)->firstOrFail();

        if ($product->isProduct()) {
            $productId = $product->id;
        } else {
            $productId = $product->parent_id;
        }

        return Response::redirectToRoute('shop.product', [
            'business' => $product->business_id,
            'product_id' => $productId,
        ]);
    }

    public function storeUsername(string $username)
    {
        return Response::redirectToRoute('shop.business', $username);
    }

    public function s(string $id)
    {
        return Response::redirectToRoute('shortcut', $id);
    }
}
