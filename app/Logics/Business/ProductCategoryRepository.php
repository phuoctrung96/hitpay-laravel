<?php

namespace App\Logics\Business;

use App\Business;
use App\Business\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductCategoryRepository
{
    /**
     * Create a new product category.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business $business
     *
     * @return \App\Business\ProductCategory
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function store(Request $request, Business $business) : ProductCategory
    {
        $data = Validator::validate($request->all(), [
            'name' => [
                'required',
                'string',
                'max:64',
            ],
            'description' => [
                'nullable',
                'string',
                'max:65536',
            ],
            'active' => [
                'required',
                'bool',
            ],
        ]);

        return DB::transaction(function () use ($business, $data) : ProductCategory {
            return $business->productCategories()->create($data);
        }, 3);
    }

    /**
     * Update an existing product category.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Business\ProductCategory $category
     *
     * @return \App\Business\ProductCategory
     * @throws \Illuminate\Validation\ValidationException
     * @throws \Throwable
     */
    public static function update(Request $request, ProductCategory $category) : ProductCategory
    {
        $data = Validator::validate($request->all(), [
            'name' => [
                'required',
                'string',
                'max:64',
            ],
            'description' => [
                'nullable',
                'string',
                'max:65536',
            ],
            'active' => [
                'required',
                'bool',
            ],
        ]);

        $category = DB::transaction(function () use ($category, $data) : ProductCategory {
            $category->update($data);

            return $category;
        }, 3);

        return $category;
    }

    /**
     * Delete an existing product category.
     *
     * @param \App\Business\ProductCategory $category
     *
     * @return bool|null
     * @throws \Throwable
     */
    public static function delete(ProductCategory $category) : ?bool
    {
        return DB::transaction(function () use ($category) : ?bool {
            return $category->delete();
        }, 3);
    }
}
