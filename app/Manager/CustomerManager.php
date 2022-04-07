<?php

namespace App\Manager;

use App\Business;
use App\Business\Customer;
use App\Logics\Business\CustomerRepository;
use Illuminate\Support\Facades\DB;

class CustomerManager extends AbstractManager implements ManagerInterface, CustomerManagerInterface
{
    public function getClass()
    {
        return Customer::class;
    }

    public function getFindOrCreateByEmail(Business $business, string $email, ?string $name = null, ?string $phone = null) : Customer
    {
        $customer = $this->getFindByBusinessAndEmail($business, $email);

        if ($customer instanceof Customer) {
            return $customer;
        }

        return CustomerRepository::createByEmail($business, $email, $name, $phone);
    }

    public function getFindOrCreate(Business $business, array $data) : Customer
    {
        $customer = $this->getFindByBusinessAndEmail($business, $data['customer_email']);

        if ($customer instanceof Customer) {
            return $customer;
        }

        return CustomerRepository::create($business, $data);
    }

    public function getFindByBusinessAndEmail(Business $business, string $email) : ?Customer
    {
        return CustomerRepository::findByBusinessAndEmail($business, $email);
    }
}
