<?php


namespace App\DTO\Export;


use App\Business;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\DataTransferObject\DataTransferObject;

class HitpaySalesExportDto extends DataTransferObject
{
    public ?Business $business;

    public ?Carbon $startDate;

    public ?Collection $chargesCollection;

    public ?array $quickbooksInvoices = [];

    public ?array $quickbooksFeeInvoices = [];
}
