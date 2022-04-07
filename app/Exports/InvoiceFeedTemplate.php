<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class InvoiceFeedTemplate implements FromArray, WithHeadings, WithEvents
{
    public function array(): array
    {
        return  [
            array(
                'test@test.com', '23345', 'sgd', 75.00, '-', '2021-05-31', '2021-06-05'
            ),
            array(
                'anothertest@test.com', '2124', 'sgd', 33.33, 'ExampleReference', '', ''
            ),
        ];
    }

    public function headings(): array
    {
        return [
            'Customer Email(it has to be associated with a customer created on HitPay)',
            'Invoice Number',
            'Currency',
            'Amount',
            'Reference (leave empty for auto-creating)',
            'Invoice Date',
            'Due Date',
        ];
    }
    public function registerEvents(): array
    {
        return [
        ];
    }
}
