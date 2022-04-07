<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class ProductFeedTemplate implements FromArray, WithHeadings, WithEvents
{
    public function array(): array
    {
        // TODO: Implement array() method.
        return  [
            array(
               123, 'example product title', 'product description', 50.00, 15,'https://shop.staging.hit-pay.com/storage/products/medium/902a240a69ed4a7f99f949381f282e9d.jpg', true, true
            ),
            array(
                124, 'example child product title', 'child product description', 30.00, 15, 'https://shop.staging.hit-pay.com/storage/products/medium/902a240a69ed4a7f99f949381f282e9d.jpg', true, true
            ),
        ];
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Name',
            'Description',
            'Price',
            'Quantity',
            'Image',
            'Publish',
            'Manage Inventory',
        ];
    }
    public function registerEvents(): array
    {
        return [
        ];
    }
}
