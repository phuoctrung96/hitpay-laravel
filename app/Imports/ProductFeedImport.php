<?php


namespace App\Imports;

use App\Business;
use App\Business\Product;
use App\Enumerations\Business\ImageGroup;
use App\Notifications\ProductBulkUploadNotification;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductFeedImport implements ToCollection
{
    public $business;
    public $errors = array();

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    public function collection(Collection $rows)
    {
        list($products, $errors) = $this->prepareProductsAttributes($rows);
        if (count($products) > 0) {
            $this->storeProducts($products);
        }

        $productsCount = $this->countProducts($products);

        if (count($errors) || $productsCount) {
            $business_id = $this->business->getKey();
            $feedLog = new Business\ProductFeedLog();
            $feedLog->business_id = $business_id;
            $feedLog->error_count = count($errors);
            $feedLog->success_count = $productsCount;
            $feedLog->error_msg = json_encode($errors, true);
            $feedLog->feed_date = \date('Y-m-d');
            $feedLog->save();
            $this->business->notify(new ProductBulkUploadNotification($feedLog));
        }
    }

    private function prepareProductsAttributes(Collection $rows)
    {
        $products = [];
        $errors = [];
        foreach ($rows as $key => $row) {
            if ($key === 0) {
                continue;
            }

            if (empty(trim($row[0]))) {
                $row[0] = null;
            }

            if (empty(trim($row[1]))) {
                $errors['name'][] = "The name of product SKU $row[0]: can't be empty";
                continue;
            }

            if (!is_numeric($row[3])) {
                $errors[$row[0]][] = "The price of product SKU $row[0]: need to be the numeric value";
            }
            if (!is_numeric($row[4])) {
                $errors[$row[0]][] = "The quantity of product SKU $row[0]: need to be the numeric value";
            }

            if ($row[5]) {
                if (filter_var($row[5], FILTER_VALIDATE_URL) == false) {
                    $errors[$row[0]][] = "The Image of product SKU $row[0]: need to be the valid URL";
                }
            }
            $existProduct = Product::withoutGlobalScopes()->where('business_id', $this->business->getKey())->where('stock_keeping_unit', $row[0])->whereNotNull('stock_keeping_unit')->first();
            if (isset($existProduct->id)) {
                $errors[$row[0]][] = "The Product SKU $row[0]: already exist";
            }
            if (isset($errors[$row[0]]) && count($errors[$row[0]])) {
                continue;
            }

            $products[$key]['stock_keeping_unit'] = $row[0];
            $products[$key]['name'] = $row[1];
            $products[$key]['headline'] = '';
            $products[$key]['description'] = $row[2];;
            $products[$key]['currency'] = $this->business->currency;
            $products[$key]['price'] = $row[3];
            $products[$key]['quantity'] = $row[4];
            $products[$key]['image'] = $row[5];
            $products[$key]['publish'] = $row[6];
            $products[$key]['manage_inventory'] = $row[7];
        }
        return [$products, $errors];
    }

    /**
     * @param $products
     * @throws \Exception
     */
    public function storeProducts($products)
    {
        if (count($products) > 0) {
            foreach ($products as $product) {
                $this->createProduct($product);
            }
        }
    }

    /**
     * @param array $products
     * @return int
     */
    private function countProducts(array $products)
    {
        $productsCount = 0;
        foreach ($products as $product) {
            $productsCount += 1;
        }

        return $productsCount;
    }

    private function createProduct(array $productAttributes)
    {
        try {
            $product = new Product();
            $product->business_id = $this->business->id;
            $product->name = $productAttributes['name'];
            $product->headline = $productAttributes['headline'];
            $product->description = $productAttributes['description'];
            $product->stock_keeping_unit = $productAttributes['stock_keeping_unit'];
            $product->currency = $productAttributes['currency'];
            $product->price = $productAttributes['price'] * 100;
            $product->quantity = 1;
            if (!empty($productAttributes['publish'])) {
                $product->published_at = now();
            }
            $product->save();

            $variant = new Product();
            $variant->business_id = $this->business->id;
            $variant->parent_id = $product->id;
            $variant->quantity = $productAttributes['quantity'];
            $variant->price = $product->price;
            $variant->published_at = $product->published_at;
            $variant->save();

            if (!empty($productAttributes['image'])) {
                $imageFile = file_get_contents($productAttributes['image']);
                ImageProcessor::new($this->business, ImageGroup::PRODUCT, $imageFile, $product)
                    ->setCaption($product->name)
                    ->process();
            }
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
