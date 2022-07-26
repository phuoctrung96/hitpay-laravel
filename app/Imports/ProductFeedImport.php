<?php


namespace App\Imports;

use App\Business;
use App\Business\Product;
use App\Enumerations\Business\ImageGroup;
use App\Notifications\ProductBulkUploadNotification;
use HitPay\Image\Processor as ImageProcessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProductFeedImport implements ToCollection
{
    public Business $business;
    public array $errors = array();
    public int $successCount = 0;

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    /**
     * @throws \Exception
     */
    public function collection(Collection $rows)
    {
        list($products, $errors) = $this->prepareProductsAttributes($rows);
        $this->errors = $errors;
        if (count($products) > 0) {
            $this->storeProducts($products);
        }

        if (count($this->errors) || $this->successCount) {
            $business_id = $this->business->getKey();
            $feedLog = new Business\ProductFeedLog();
            $feedLog->business_id = $business_id;
            $feedLog->error_count = count($this->errors);
            $feedLog->success_count = $this->successCount;
            $feedLog->error_msg = json_encode($this->errors, true);
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

            if (isset($row[10]) && $row[10] === 'hotglue') {
                if (isset($row[8]) && strtolower($row[8]) !== $this->business->currency) {
                    $errors[$row[0]][] = "Sync failed for product SKU $row[0] due to ($row[8]) currency mismatch. Please ensure that the Shopify store currency is the same as the Hitpay store currency.";
                    continue;
                }
            }

            if (isset($row[10]) && $row[10] === 'hotglue') {
                $existProduct = Product::withoutGlobalScopes()->where('business_id', $this->business->getKey())->where('shopify_inventory_item_id', $row[11])->first();
            } else {
                $existProduct = Product::withoutGlobalScopes()->where('business_id', $this->business->getKey())->where('stock_keeping_unit', $row[0])->whereNotNull('stock_keeping_unit')->first();
            }

            if (isset($existProduct->id)) {
                $errors[$row[0]][] = "The Product SKU $row[0]: already exist";
            }

            if (isset($row[9]) && count($row[9]) >= 1) {
                $variations = $row[9]['variants'];
                foreach ($variations as $variant) {
                    $productVariant = $this->business->productVariations()->where('shopify_inventory_item_id', $variant['id'])->first();

                    if ($productVariant) {
                        $errors[$row[0]][] = "The Product Shopify Item ID {$variant['id']}: already exist";
                    }
                }
            }

            if (isset($errors[$row[0]]) && count($errors[$row[0]])) {
                continue;
            }

            $products[$key]['stock_keeping_unit'] = $row[0];
            $products[$key]['name'] = $row[1];
            $products[$key]['headline'] = '';
            $products[$key]['description'] = $row[2];
            $products[$key]['currency'] = $this->business->currency;
            $products[$key]['price'] = $row[3];
            $products[$key]['quantity'] = $row[4];
            $products[$key]['image'] = $row[5];
            $products[$key]['publish'] = $row[6];
            $products[$key]['manage_inventory'] = $row[7];

            if (!isset($row[10])) {
                // if import flow not from hotglue
                $products[$key]['categories'] = $row[8];
            }

            if (isset($row[9]) && count($row[9]) >= 1) {
                $products[$key]['variations'] = $row[9];
            }

            if (isset($row[10])) {
                $products[$key]['integration'] = $row[10];
            }

            if (isset($row[11])) {
                $products[$key]['item_id'] = $row[11];
            }
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
                if (isset($product['integration'])) {
                    $existProduct = Product::withoutGlobalScopes()->where('business_id', $this->business->getKey())->where('shopify_inventory_item_id', $product['item_id'])->first();
                } else {
                    $existProduct = Product::withoutGlobalScopes()->where('business_id', $this->business->getKey())->where('stock_keeping_unit', $product['stock_keeping_unit'])->whereNotNull('stock_keeping_unit')->first();
                }

                if (isset($existProduct->id)) {
                    $sku = $product['stock_keeping_unit'];
                    $this->errors[$sku][] = "The Product SKU  $sku: already exist";
                    continue;
                }

                $this->createProduct($product);
                $this->successCount += 1;
            }
        }
    }

    private function createProduct(array $productAttributes)
    {
        try {
            $stock_keeping_unit = isset($productAttributes['variations']) ? null : $productAttributes['stock_keeping_unit'];
            $stock_keeping_unit = !empty(trim($stock_keeping_unit)) ? trim($stock_keeping_unit) : null;
            $product = new Product();
            $product->business_id = $this->business->id;
            $product->name = $productAttributes['name'];
            $product->headline = $productAttributes['headline'];
            $product->description = $productAttributes['description'];
            if(isset($productAttributes['integration'])) {
                $product->shopify_stock_keeping_unit = $stock_keeping_unit;
            } else {
                $product->stock_keeping_unit = $stock_keeping_unit;
            }
            $product->currency = $productAttributes['currency'];
            $product->price = getRealAmountForCurrency(strtolower($productAttributes['currency']), $productAttributes['price']);
            $product->quantity = $productAttributes['manage_inventory'] ? 1 : 0;
            if (!empty($productAttributes['publish'])) {
                $product->published_at = now();
                $product->status = 'published';
            } else {
                $product->status = 'draft';
            }

            if (isset($productAttributes['categories'])) {
                $categoriesParsed = explode(';', $productAttributes['categories']);
                $categories = [];

                if (is_array($categoriesParsed) && count($categoriesParsed) > 0) {
                    foreach ($categoriesParsed as $categoryParamName) {
                        $categoryParamName = trim($categoryParamName);

                        if ($categoryParamName == "") {
                            continue;
                        } else {
                            $categories = $this->getCategories($categoryParamName, $categories);
                        }
                    }
                } else {
                    $categoryParamName = trim($productAttributes['categories']);

                    $categories = $this->getCategories($categoryParamName, $categories);
                }

                $product->business_product_category_id = json_encode($categories);
            }

            if (isset($productAttributes['variations'])) {
                $options = $productAttributes['variations']['options'];
                for ($i = 1; $i <= count($options); $i++) {
                    $index = $i - 1;
                    $product->{'variation_key_' . $i} = $options[$index];
                }
            }
            $product->save();

            if (isset($productAttributes['variations'])) {
                $tempVariants = $productAttributes['variations']['variants'];
                foreach ($tempVariants as $tempVariant) {
                    $variant = new Product();
                    $variant->business_id = $this->business->id;
                    $variant->parent_id = $product->id;
                    $variant->description = $tempVariant['title'];

                    $variantSku = !empty(trim($tempVariant['sku'])) ? trim($tempVariant['sku']) : null;
                    if($variantSku && isset($productAttributes['integration'])) {
                        $variant->shopify_stock_keeping_unit = $variantSku;
                    } else {
                        $variant->stock_keeping_unit = $variantSku;
                    }

                    for ($i = 1; $i <= 3; $i++) {
                        $variant->{'variation_value_' . $i} = $tempVariant['option' . $i];
                    }

                    $variant->quantity = $tempVariant['inventory_quantity'];
                    $variant->price = getRealAmountForCurrency(strtolower($productAttributes['currency']), $tempVariant['price']);
                    $variant->shopify_inventory_item_id = $tempVariant['id'];
                    $variant->published_at = $product->published_at;
                    $variant->save();
                }
            } else {
                $variant = new Product();
                $variant->business_id = $this->business->id;
                $variant->parent_id = $product->id;
                $variant->quantity = $productAttributes['quantity'];
                $variant->price = $product->price;
                $variant->shopify_inventory_item_id = $productAttributes['item_id'] ?? null;
                $variant->published_at = $product->published_at;
                $variant->save();
            }

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

    /**
     * @param string $categoryParamName
     * @param array $categories
     * @return array
     */
    private function getCategories(string $categoryParamName, array $categories): array
    {
        $category = Business\ProductCategory::where('name', $categoryParamName)
            ->where('business_id', $this->business->getKey())
            ->first();

        if (!$category instanceof Business\ProductCategory) {
            $category = new Business\ProductCategory();
            $category->business_id = $this->business->getKey();
            $category->name = $categoryParamName;
            $category->description = $categoryParamName;
            $category->active = true;
            $category->save();
        }

        $categories[] = $category->getKey();

        return $categories;
    }
}
