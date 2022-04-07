<?php

namespace HitPay\Data\Countries\Objects;

use App\Enumerations\CountryCode;
use HitPay\Data\Countries\Objects\Bank\Branch;
use Illuminate\Support\Collection;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read string $name_code
 * @property-read string $code
 * @property-read string $swift_code
 * @property-read null|Collection|\HitPay\Data\Countries\Objects\Bank\Branch[] $branches
 */
class Bank extends Base
{
    public bool $useBranch = false;

    /**
     * @inheritdoc
     */
    protected function processData(array $data) : array
    {
        $branches = Collection::make();

        // Not all country requires branch code. E.g. Malaysia, Malaysia is using swift code only.
        //
        if ($this->country === CountryCode::SINGAPORE) {
            $this->useBranch = true;

            foreach ($data['branches'] as $_branch) {
                $_branch['swift_code'] = $data['head_office_swift_bic'];
                $_branch['routing_number'] = "{$data['bank_code']}-{$_branch['code']}";

                $branches->push(( new Branch($this->country) )->setData($_branch));
            }
        }

        $this->setChild('branches', $branches->sortBy('code')->values());

        $data['code'] = $codes[] = $data['bank_code'];
        $data['name'] = $data['bank_name'];
        $data['swift_code'] = $codes[] = $data['head_office_swift_bic'];

        $codes = array_filter($codes);

        if (count($codes) > 0) {
            $codes = implode(', ', $codes);

            $data['name_code'] = "{$data['name']} ({$codes})";
        } else {
            $data['name_code'] = $data['name'];
        }

        unset(
            $data['bank_code'],
            $data['bank_name'],
            $data['head_office_swift_bic'],
            $data['branches']
        );

        return $data;
    }
}
