<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class CustomerFeedTemplate implements FromArray, WithHeadings, WithEvents
{
    public function array(): array
    {
        return  [
            array(
                'Test Name', 'test@test.com', '8001207153', 'Remark'
            ),
            array(
                'Another Test', 'anothertest@test.com', '8001207154', ''
            ),
        ];
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Phone Number',
            'Remark',
        ];
    }
    public function registerEvents(): array
    {
        return [
        ];
    }
}
