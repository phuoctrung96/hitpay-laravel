<?php

namespace App\Console\Commands;

use App\Business;
use App\Enumerations\Business\Wallet\Type;
use App\Enumerations\CurrencyCode;
use Illuminate\Console\Command;

class WalletForBusinesses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:wallet-for-businesses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read all wallet for businesses, create if not found.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Business::where('currency', CurrencyCode::SGD)->each(function (Business $business) {
            $repeater = str_repeat('=', strlen($business->name) + 6);

            $this->line($repeater);
            $this->line("=  <info>{$business->name}</info>  =");
            $this->line($repeater);

            foreach (Type::toArray() as $value) {
                $wallet = $business->wallet($value, $business->currency);

                $walletType = str_pad($wallet->type, 9);

                $walletBalance = strtoupper($wallet->currency).' ';
                $walletBalance .= getFormattedAmount($wallet->currency, $wallet->balance, false);

                if ($wallet->balance > 0) {
                    $type = 'info';
                } elseif ($wallet->balance < 0) {
                    $type = 'error';
                } else {
                    $type = 'comment';
                }

                $this->line("<info>{$walletType}</info> : <{$type}>{$walletBalance}</{$type}>");
            }

            $this->line('');
        });
    }
}
