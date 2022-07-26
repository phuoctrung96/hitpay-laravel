<?php

namespace App\Jobs;

use App\Business;
use App\Notifications\SendFile;
use App\Role;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use League\Csv\Writer;

class SendExportedProducts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $products;
    public $business;
    public $user;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Business $business, $products, $user)
    {
        $this->business = $business;
        $this->products = $products;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $csv = Writer::createFromString('');

        $csv->insertOne(collect([
            '#',
            'SKU',
            'Name',
            'Description',
            'Price',
            'Quantity',
            'Image',
            'Publish',
            'Manage Inventory',
            'Created At'
        ])->toArray());

        $i = 1;
        $data = [];

        foreach ($this->products as $product) {
            $product = $product->getProductObject();

            $productData = collect([
                '#' =>  $i++,
                'SKU' => $product['stock_keeping_unit'],
                'Name' => $product['name'],
                'Description' => $product['description'],
                'Price' => $product['readable_price'],
                'Quantity' => $product['quantity'] ?? '',
                'Image' => $product['image_display'],
                'Publish' => $product['is_published'],
                'Manage Inventory' => $product['is_manageable'],
                'Created At' => $product['created_at']
            ])->toArray();

            $data[] = $productData;

            if ($product['has_variations']){
                foreach ($product['variations'] as $variation){
                    $variationData = collect([
                        '#' =>  '',
                        'SKU' => '',
                        'Name' => '',
                        'Description' => $variation['description'],
                        'Price' => $variation['price'],
                        'Quantity' => $variation['quantity'] ?? '',
                        'Image' => '',
                        'Publish' => '',
                        'Manage Inventory' => '',
                        'Created At' => ''
                    ])->toArray();

                    $data[] = $variationData;
                }
            }
        }

        $csv->insertAll($data);

        if ($this->user instanceof User) {
            $this->user->notify(new SendFile($this->business->getName().' - Exported Products', [
                'Please find attached the exported products'
            ], 'exported-products', $csv->getContent()));
        } else {
            $this->business->notify(new SendFile($this->business->getName().' - Exported Products', [
                'Please find attached the exported products'
            ], 'exported-products', $csv->getContent()));
        }

        $businessAdmins = $this->business->businessUsers()
            ->with('user')
            ->where('role_id', Role::admin()->id)
            ->get();

        foreach ($businessAdmins as $businessAdmin) {
            $businessAdmin->user->notify(new SendFile($this->business->getName().' - Exported Products', [
                'Please find attached the exported products'
            ], 'exported-products', $csv->getContent()));
        }
    }
}
