<?php

namespace App\Console\Commands;

use App\Configuration;
use App\Enumerations\Permission;
use App\Role;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Console\Exception\RuntimeException;

class InitializeSystem extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hitpay:initialize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup HitPay system. (For first time only)';

    /**
     * Execute the console command.
     *
     * @throws \Exception
     */
    public function handle()
    {
        if (Configuration::count()) {
            throw new RuntimeException('The system has been initialized.');
        } elseif (!$this->confirmToProceed()) {
            return;
        }

        if ($this->getLaravel()->environment('local')) {
            $supportInformation = [
                'info_support_phone_number' => '+60124542433',
                'info_support_email' => 'hello@bankorh.com',
            ];

            $userData = [
                'first_name' => 'Ban Korh',
                'last_name' => 'Wong',
                'display_name' => 'Bankorh',
                'email' => 'me@bankorh.com',
                'phone_number' => '+601245424433',
                'password' => '1234',
            ];
        }

        $this->title('Setup Support Information');

        foreach ([
            'info_support_email' => 'Support Email',
            'info_support_phone_number' => 'Support Phone Number',
        ] as $key => $title) {
            while (empty($supportInformation[$key])) {
                $supportInformation[$key] = $this->ask($title);

                if (empty($supportInformation[$key])) {
                    $this->error('The '.strtolower($title).' is required.');
                }
            }
        }

        $this->title('Setup Super Administrator');

        foreach ([
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'display_name' => 'Display Name',
            'email' => 'Email',
            'phone_number' => 'Phone Number',
            'password' => 'Password',
        ] as $key => $title) {
            while (empty($userData[$key])) {
                if ($key === 'password') {
                    $userData[$key] = $this->secret($title);
                } else {
                    $userData[$key] = $this->ask($title);
                }

                if (empty($userData[$key])) {
                    $this->error('The '.strtolower($title).' is required.');
                }
            }
        }

        DB::beginTransaction();

        Configuration::create([
            'configuration_key' => 'platform_version',
            'type' => 'string',
            'value' => '3', // Recommendation 55 - 60 characters
            'autoload' => true,
        ]);

        Configuration::create([
            'configuration_key' => 'site_default_title',
            'type' => 'string',
            'value' => null, // Recommendation 55 - 60 characters
            'autoload' => true,
        ]);

        Configuration::create([
            'configuration_key' => 'site_default_meta_description',
            'type' => 'string',
            'value' => null, // Recommendation about 160 characters
            'autoload' => true,
        ]);

        Configuration::create([
            'configuration_key' => 'site_default_meta_keywords',
            'type' => 'string',
            'value' => null, // Recommendation not more than 10 keywords, separated by comma
            'autoload' => true,
        ]);

        Configuration::create([
            'configuration_key' => 'info_support_email',
            'type' => 'string',
            'value' => $supportInformation['info_support_email'],
            'autoload' => true,
        ]);

        Configuration::create([
            'configuration_key' => 'info_support_phone_number',
            'type' => 'string',
            'value' => $supportInformation['info_support_phone_number'],
            'autoload' => true,
        ]);

        Configuration::create([
            'configuration_key' => 'is_user_can_register',
            'type' => 'bool',
            'value' => true,
            'autoload' => true,
        ]);

        $role = Role::create([
            'title' => 'Super Administrator',
            'description' => 'This role has complete access to all objects, folders, role templates, and groups in the system. A deployment can have one or more Super Administrators. A Super Administrator can create users, groups, and other super administrators.',
        ]);

        $role->grantPermissions([
            Permission::ALL,
        ]);

        $user = User::create([
            'display_name' => $userData['display_name'],
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'phone_number' => $userData['phone_number'],
            'password' => $userData['password'],
            'verified_at' => Date::now(),
            'email_verified_at' => Date::now(),
            'phone_number_verified_at' => Date::now(),
        ]);

        $user->role()->associate($role);
        $user->save();

        $this->call('passport:install');

        DB::commit();

        $this->line('System setup completed.');
    }

    /**
     * Write a string in an alert box.
     *
     * @param string $string
     */
    public function title($string) : void
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->info(str_repeat('*', $length));
        $this->info('*     '.$string.'     *');
        $this->info(str_repeat('*', $length));

        $this->output->newLine();
    }
}
