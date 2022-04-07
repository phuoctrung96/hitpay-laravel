<?php


namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
class FacebookProductsExport implements FromCollection
{
    public $list;

    public function __construct($productList)
    {
        $this->list = $productList;
    }

    public function collection()
    {
        if (is_array($this->list))
        {
            return new Collection($this->list);
        }
    }
}
