<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Image;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ImageRepository
{
    /**
     * Create a new product.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     * @param string $group
     *
     * @return \App\Business\Image
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business, string $group) : Image
    {
        $image = Validator::validate($request->all(), [
            'image' => [
                'required',
                'image',
            ],
        ])['image'];

        return DB::transaction(function () use ($business, $group, $image) : Image {
            return ImageProcessor::new($business, $group, $image)->process();
        }, 3);
    }

    /**
     * Delete a product.
     *
     * @param \App\Business\Product $product
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Image $image) : ?bool
    {
        return DB::transaction(function () use ($image) : ?bool {
            return $image->delete();
        }, 3);
    }
}
