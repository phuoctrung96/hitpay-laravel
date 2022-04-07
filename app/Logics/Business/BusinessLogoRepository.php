<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\Image;
use App\Business\Image as ImageModel;
use App\Enumerations\Business\ImageGroup;
use App\Enumerations\Image\Size;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BusinessLogoRepository
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
    public static function store(Request $request, Business $business, string $group)
    {
        $image = Validator::validate($request->all(), [
            'image' => [
                'required',
                'image',
            ],
        ])['image'];

        $image = DB::transaction(function () use ($business, $image) {
            $image = ImageProcessor::new($business, ImageGroup::LOGO, $image)
                ->setCaption($business->name)
                ->process();

            $business->images()
                ->where('group', ImageGroup::LOGO)
                ->where('id', '<>', $image->getKey())
                ->each(function (ImageModel $image) {
                    $image->delete();
                });

            return $image;
        });

        return $image->getUrl(Size::MEDIUM);
    }

    /**
     * Delete a business logo.
     *
     * @param \App\Business $business
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(Business $business) : ?bool
    {
        return $business->images()
            ->where('group', ImageGroup::LOGO)
            ->each(function (ImageModel $image) {
                $image->delete();
            });
    }
}
