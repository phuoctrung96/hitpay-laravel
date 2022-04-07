<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class SendExportedFastPayoutsToAdmin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $data;

    public User $user;

    /**
     * Create a new job instance.
     *
     * @param array $data
     * @param \App\User|null $user
     */
    public function __construct(array $data, User $user = null)
    {
        $this->data = $data;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data['--email'] = $this->user->email;

        if (isset($this->data['starts_at']) && $this->isValidDate($this->data['starts_at'])) {
            $data['--start_date'] = $this->data['starts_at'];
        }

        if (isset($this->data['ends_at']) && $this->isValidDate($this->data['ends_at'])) {
            $data['--end_date'] = $this->data['ends_at'];
        }

        Artisan::call('export:transfers', $data);
    }

    private function isValidDate($date)
    {
        return preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $date);
    }
}
