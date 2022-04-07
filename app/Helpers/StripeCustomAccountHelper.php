<?php
namespace App\Helpers;

use App\Enumerations\CountryCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades;

trait StripeCustomAccountHelper
{
    /***
     * Validate or test line 1 / address
     * @param string|null $line1
     * @return string
     */
    protected function validateAddress(?string $line1='') : string
    {
        if ($line1 === null) return '';

        if ($line1 == '') return $line1;

        if (!Facades\App::environment('production')) {
            if (!Config::get('services.stripe.sg.stripe_custom_account_production')) {
                // test mode address line1
                // https://stripe.com/docs/connect/testing#test-verification-addresses
                // address_full_match	Successful verification.
                // address_no_match	    Unsuccessful verification.
                if (Config::get('services.stripe.sg.stripe_custom_account_positive_test_mode')) {
                    return 'address_full_match';
                } else {
                    return 'address_no_match';
                }
            } else {
                return $line1;
            }
        } else {
            return $line1;
        }
    }

    /**
     * @param string $taxId
     * @return string
     */
    protected function validateTaxId(?string $taxId) : string
    {
        if ($taxId === null) {
            return '';
        }

        if (!Facades\App::environment('production')) {
            // for test mode
            // https://stripe.com/docs/connect/testing#test-business-tax-ids
            // 000000000	Successful verification.
            // 000000001	Successful verification as a non-profit.
            // 111111111	Unsuccessful verification (identity mismatch).
            // 222222222	Successful, immediate verification.
            //              The verification result is returned directly in
            //              the response, not as part of a webhook event.
            if (!Config::get('services.stripe.sg.stripe_custom_account_production')) {
                if (Config::get('services.stripe.sg.stripe_custom_account_positive_test_mode')) {
                    return "000000000";
                } else {
                    return "111111111";
                }
            } else {
                return $taxId;
            }
        } else {
            return $taxId;
        }
    }

    /**
     * @param string|null $phone
     * @return string
     */
    protected function validatePhoneNumber(?string $phone) : string
    {
        if (!Facades\App::environment('production')) {
            // for test mode use phone number this below
            // https://stripe.com/docs/connect/testing#using-oauth
            // 0000000000
            if (!Config::get('services.stripe.sg.stripe_custom_account_production')) {
                if (Config::get('services.stripe.sg.stripe_custom_account_positive_test_mode')) {
                    return '0000000000';
                } else {
                    return '0000000000';
                }
            } else {
                return $phone;
            }
        } else {
            if ( $this->business->country == CountryCode::SINGAPORE && $phone != "") {
                $countryCode = '+65';
                $phoneNumber = preg_replace("/^\+?{$countryCode}/", '',$phone);

                return $phoneNumber;
            }

            if ( $this->business->country == CountryCode::MALAYSIA && $phone != "") {
                $countryCode = '+60';
                $phoneNumber = preg_replace("/^\+?{$countryCode}/", '',$phone);

                return $phoneNumber;
            }
        }

        return '';
    }

    /***
     * @param string $dob
     * @return array
     */
    protected function validateDateOfBirth(string $dob) : array
    {
        if (!Facades\App::environment('production')) {
            // https://stripe.com/docs/connect/testing#test-dobs
            // for test mode
            // 1901-01-01	Successful verification. Any other DOB results in unsuccessful verification.
            // 1902-01-01	Successful, immediate verification.
            //              The verification result is returned directly in the response,
            //              not as part of a webhook event.
            // 1900-01-01	This DOB will trigger an Office of Foreign Assets Control (OFAC) alert.
            if (!Config::get('services.stripe.sg.stripe_custom_account_production')) {
                if (Config::get('services.stripe.sg.stripe_custom_account_positive_test_mode')) {
                    return [
                        'year' => '1901',
                        'month' => '01',
                        'day' => '01',
                    ];
                } else {
                    return $this->explodeStringDob($dob);
                }
            } else {
                return $this->explodeStringDob($dob);
            }
        } else {
            return $this->explodeStringDob($dob);
        }
    }

    /**
     * @param string $dob format "Y-m-d"
     * @return array
     */
    protected function explodeStringDob(string $dob) : array
    {
        $dob = Carbon::createFromFormat('Y-m-d', $dob);

        $dobYear = $dob->format('Y');
        $dobMonth = $dob->format('n');
        $dobDay = $dob->format('j');

        return [
            'year' => $dobYear,
            'month' => $dobMonth,
            'day' => $dobDay,
        ];
    }

    /**
     * @param string|null $number
     * @return string
     */
    protected function validateIdNumber(?string $number) : string
    {
        if ($number === null) {
            return '';
        }

        if (!Facades\App::environment('production')) {
            if (!Config::get('services.stripe.sg.stripe_custom_account_production')) {
                // https://stripe.com/docs/connect/testing#test-personal-id-numbers
                // 000000000	Successful verification. 0000 also works for SSN last 4 verification.
                // 111111111	Unsuccessful verification (identity mismatch).
                // 222222222	Successful, immediate verification.
                //              The verification result is returned directly in the response,
                //              not as part of a webhook event.
                if (Config::get('services.stripe.sg.stripe_custom_account_positive_test_mode')) {
                    return '000000000';
                } else {
                    return '111111111';
                }
            } else {
                return $number;
            }
        } else {
            return $number;
        }
    }

    /***
     * @param string $merchantCode
     * @return string
     * @throws Exception|\Exception
     */
    protected function validateMerchantCategoryCode(string $merchantCode) : string
    {
        // our MCC code have different value with stripe
        // ex: Agricultural stripe have 0763, we have 763
        // https://stripe.com/docs/connect/setting-mcc#list
        if (strlen(trim($merchantCode)) == 4) {
            return $merchantCode;
        }

        if (strlen(trim($merchantCode)) == 3) {
            return '0'.$merchantCode;
        }

        throw new \Exception("Fail when validate merchant category code with business id " . $this->business->getKey());
    }
}
