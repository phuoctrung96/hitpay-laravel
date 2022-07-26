<?php


namespace App\Imports;

use App\Business;
use App\Business\Customer;
use App\Notifications\CustomerBulkUploadNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class CustomerFeedImport implements ToCollection
{
    public $business;
    public $errors = array();

    public function __construct(Business $business)
    {
        $this->business = $business;
    }

    public function collection(Collection $rows)
    {
        list($customers, $errors) = $this->prepareCustomersAttributes($rows);
        if (count($customers) > 0) {
            $this->storeCustomers($customers);
        }

        $customersCount = $this->countCustomers($customers);

        if (count($errors) || $customersCount) {
            $business_id = $this->business->getKey();
            $feedLog = new Business\CustomerFeedLog();
            $feedLog->business_id = $business_id;
            $feedLog->error_count = count($errors);
            $feedLog->success_count = $customersCount;
            $feedLog->error_msg = json_encode($errors, true);
            $feedLog->feed_date = \date('Y-m-d');
            $feedLog->save();
            // Removed due to production bug
            //$this->business->notify(new CustomerBulkUploadNotification($feedLog));
        }
    }

    private function prepareCustomersAttributes(Collection $rows)
    {
        $customers = [];
        $errors = [];
        foreach ($rows as $key => $row) {
            if ($key === 0) {
                continue;
            }
            if (empty($row[0])) {
                $errors['name'][] = "Customer name is required";
                continue;
            }
            if (empty($row[1])) {
                $errors['email'][] = "Customer email is required";
                continue;
            }

            if (!filter_var($row[1], FILTER_VALIDATE_EMAIL)) {
                $errors['email'][] = "Invalid email address found, failed to add customer $row[0]";
                continue;
            }

            $extractEmail = explode('@', $row[1]);
            $emailHost = end($extractEmail) . '.';
            if (!checkdnsrr(idn_to_ascii($emailHost), 'MX')) {
                $errors['email'][] = "Invalid email address found, failed to add customer $row[0]";
                continue;
            }

            $existCustomer = Customer::where('business_id', $this->business->getKey())->where('email', $row[1])->first();
            if (isset($existCustomer->id)) {
                $errors[$row[0]][] = "The customer $row[0]: exist";
                continue;
            }

            $customers[$key]['name'] = $row[0];
            $customers[$key]['email'] = $row[1];
            $customers[$key]['phone_number'] = $row[2];
            $customers[$key]['remark'] = $row[3];
        }
        return [$customers, $errors];
    }

    /**
     * @param $customers
     * @throws \Exception
     */
    public function storeCustomers($customers)
    {
        if (count($customers) > 0) {
            foreach ($customers as $customer) {
                $this->createCustomer($customer);
            }
        }
    }

    /**
     * @param array $invoices
     * @return int
     */
    private function countCustomers(array $customers)
    {
        $customersCount = 0;
        foreach ($customers as $customer) {
            $customersCount += 1;
        }

        return $customersCount;
    }

    private function createCustomer(array $customerAttributes)
    {
        try {
            $customer = new Customer();
            $customer->business_id = $this->business->id;
            $customer->name = $customerAttributes['name'];
            $customer->email = $customerAttributes['email'];
            $customer->phone_number = $customerAttributes['phone_number'];
            $customer->remark = $customerAttributes['remark'];

            $customer = $this->business->customers()->save($customer);
        } catch (\Exception $exception) {
            Log::error($exception);
        }
    }
}
