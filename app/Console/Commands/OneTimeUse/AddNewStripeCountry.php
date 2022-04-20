<?php

namespace App\Console\Commands\OneTimeUse;

use App\Providers\AppServiceProvider;
use Illuminate\Console\Command;
use Stripe\CountrySpec;
use Stripe\Stripe;

class AddNewStripeCountry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'otu:add-new-stripe-countries {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add New Stripe Countries';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Stripe::setApiKey(env('STRIPE_SG_SECRET'));
        Stripe::setApiVersion(AppServiceProvider::STRIPE_VERSION);

        foreach (CountrySpec::all(['limit' => 500]) as $country) {
            $supported_countries = array_map(fn($c) => strtoupper($c), array_keys($country->supported_bank_account_currencies->toArray()));
            $currencies_text = "CurrencyCode::" . implode(",\n        CurrencyCode::", $supported_countries);

            if (class_exists('\HitPay\Data\Countries\\' . $country->id)) {
                $this->line("{$country->id} already exists, skipping... ", null);
            } else {
                $this->line("    Adding country: {$country->id}", null);

                $this->line("- Creating country class", null);
                // create country class
                $country_class = <<<CODE
<?php

namespace HitPay\Data\Countries;

class {$country->id} extends Country {
    const skip_verification = true;
}
CODE;

                file_put_contents(
                    base_path('hitpay/Data/Countries/' . $country->id . '.php'),
                    $country_class
                );

                $alpha_2 = strtolower($country->id);
                $country_name = self::getCountryNameFromCode($country->id);
                $alpha_3 = strtolower(self::getAlpha3($country_name));

                $this->line("- Creating country file", null);

                // create country file
                $country_file = <<<CODE
<?php

use App\Enumerations\CurrencyCode;

return [
    'id' => '{$alpha_2}',
    'alpha_2' => '{$alpha_2}',
    'alpha_3' => '{$alpha_3}',
    'name' => '{$country_name}',
    'currencies' => [
        {$currencies_text}
    ],
    'banks' => \HitPay\Data\Countries\\{$country->id}::banks()->toArray(),
    'payment_providers' => \HitPay\Data\Countries\\{$country->id}::paymentProviders()->toArray(),
];

CODE;
                file_put_contents(
                    base_path('hitpay/Data/Countries/files/' . $country->id . '.php'),
                    $country_file
                );


                $this->line("- Creating country folders", null);
                // create country folder
                @mkdir(base_path('hitpay/Data/Countries/files/' . $country->id));
                // create banks folder
                @mkdir(base_path('hitpay/Data/Countries/files/' . $country->id . '/banks'));
                // create payment_providers folder
                @mkdir(base_path('hitpay/Data/Countries/files/' . $country->id . '/payment_providers'));

                $this->line("- Creating stripe_us payment provider file", null);

                // create stripe_us payment provider
                $stripe_us_file = <<<CODE
<?php

return require base_path('hitpay/Data/Countries/files/_common/stripe_us.php');

CODE;

                file_put_contents(
                    base_path('hitpay/Data/Countries/files/' . $country->id . '/payment_providers/stripe_us.php'),
                    $stripe_us_file
                );
            }
        }

        return 0;
    }

    public static function getCountryNameFromCode(string $country_code): string
    {
        $countries = [
            'AF' => 'Afghanistan',
            'AX' => 'Aland Islands',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AQ' => 'Antarctica',
            'AG' => 'Antigua And Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia And Herzegovina',
            'BW' => 'Botswana',
            'BV' => 'Bouvet Island',
            'BR' => 'Brazil',
            'IO' => 'British Indian Ocean Territory',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CX' => 'Christmas Island',
            'CC' => 'Cocos (Keeling) Islands',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo, Democratic Republic',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote D\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands (Malvinas)',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'TF' => 'French Southern Territories',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GG' => 'Guernsey',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HM' => 'Heard Island & Mcdonald Islands',
            'VA' => 'Holy See (Vatican City State)',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran, Islamic Republic Of',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IM' => 'Isle Of Man',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JE' => 'Jersey',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KR' => 'Korea',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Lao People\'s Democratic Republic',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libyan Arab Jamahiriya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MK' => 'Macedonia',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'YT' => 'Mayotte',
            'MX' => 'Mexico',
            'FM' => 'Micronesia, Federated States Of',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'AN' => 'Netherlands Antilles',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'NF' => 'Norfolk Island',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestinian Territory, Occupied',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PN' => 'Pitcairn',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RE' => 'Reunion',
            'RO' => 'Romania',
            'RU' => 'Russian Federation',
            'RW' => 'Rwanda',
            'BL' => 'Saint Barthelemy',
            'SH' => 'Saint Helena',
            'KN' => 'Saint Kitts And Nevis',
            'LC' => 'Saint Lucia',
            'MF' => 'Saint Martin',
            'PM' => 'Saint Pierre And Miquelon',
            'VC' => 'Saint Vincent And Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome And Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'GS' => 'South Georgia And Sandwich Isl.',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SJ' => 'Svalbard And Jan Mayen',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syrian Arab Republic',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad And Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks And Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UM' => 'United States Outlying Islands',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VE' => 'Venezuela',
            'VN' => 'Viet Nam',
            'VG' => 'Virgin Islands, British',
            'VI' => 'Virgin Islands, U.S.',
            'WF' => 'Wallis And Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ];

        return $countries[$country_code];
    }

    public static function getAlpha3(string $country_name): string
    {
        $countries = [
            'Aruba' => 'ABW',
            'Afghanistan' => 'AFG',
            'Angola' => 'AGO',
            'Anguilla' => 'AIA',
            'Åland Islands' => 'ALA',
            'Albania' => 'ALB',
            'Andorra' => 'AND',
            'United Arab Emirates' => 'ARE',
            'Argentina' => 'ARG',
            'Armenia' => 'ARM',
            'American Samoa' => 'ASM',
            'Antarctica' => 'ATA',
            'French Southern Territories' => 'ATF',
            'Antigua and Barbuda' => 'ATG',
            'Australia' => 'AUS',
            'Austria' => 'AUT',
            'Azerbaijan' => 'AZE',
            'Burundi' => 'BDI',
            'Belgium' => 'BEL',
            'Benin' => 'BEN',
            'Bonaire, Sint Eustatius and Saba' => 'BES',
            'Burkina Faso' => 'BFA',
            'Bangladesh' => 'BGD',
            'Bulgaria' => 'BGR',
            'Bahrain' => 'BHR',
            'Bahamas' => 'BHS',
            'Bosnia and Herzegovina' => 'BIH',
            'Saint Barthélemy' => 'BLM',
            'Belarus' => 'BLR',
            'Belize' => 'BLZ',
            'Bermuda' => 'BMU',
            'Bolivia, Plurinational State of' => 'BOL',
            'Brazil' => 'BRA',
            'Barbados' => 'BRB',
            'Brunei Darussalam' => 'BRN',
            'Bhutan' => 'BTN',
            'Bouvet Island' => 'BVT',
            'Botswana' => 'BWA',
            'Central African Republic' => 'CAF',
            'Canada' => 'CAN',
            'Cocos (Keeling) Islands' => 'CCK',
            'Switzerland' => 'CHE',
            'Chile' => 'CHL',
            'China' => 'CHN',
            'Côte d\'Ivoire' => 'CIV',
            'Cameroon' => 'CMR',
            'Congo, the Democratic Republic of the' => 'COD',
            'Congo' => 'COG',
            'Cook Islands' => 'COK',
            'Colombia' => 'COL',
            'Comoros' => 'COM',
            'Cape Verde' => 'CPV',
            'Costa Rica' => 'CRI',
            'Cuba' => 'CUB',
            'Curaçao' => 'CUW',
            'Christmas Island' => 'CXR',
            'Cayman Islands' => 'CYM',
            'Cyprus' => 'CYP',
            'Czech Republic' => 'CZE',
            'Germany' => 'DEU',
            'Djibouti' => 'DJI',
            'Dominica' => 'DMA',
            'Denmark' => 'DNK',
            'Dominican Republic' => 'DOM',
            'Algeria' => 'DZA',
            'Ecuador' => 'ECU',
            'Egypt' => 'EGY',
            'Eritrea' => 'ERI',
            'Western Sahara' => 'ESH',
            'Spain' => 'ESP',
            'Estonia' => 'EST',
            'Ethiopia' => 'ETH',
            'Finland' => 'FIN',
            'Fiji' => 'FJI',
            'Falkland Islands (Malvinas)' => 'FLK',
            'France' => 'FRA',
            'Faroe Islands' => 'FRO',
            'Micronesia, Federated States of' => 'FSM',
            'Gabon' => 'GAB',
            'United Kingdom' => 'GBR',
            'Georgia' => 'GEO',
            'Guernsey' => 'GGY',
            'Ghana' => 'GHA',
            'Gibraltar' => 'GIB',
            'Guinea' => 'GIN',
            'Guadeloupe' => 'GLP',
            'Gambia' => 'GMB',
            'Guinea-Bissau' => 'GNB',
            'Equatorial Guinea' => 'GNQ',
            'Greece' => 'GRC',
            'Grenada' => 'GRD',
            'Greenland' => 'GRL',
            'Guatemala' => 'GTM',
            'French Guiana' => 'GUF',
            'Guam' => 'GUM',
            'Guyana' => 'GUY',
            'Hong Kong' => 'HKG',
            'Heard Island and McDonald Islands' => 'HMD',
            'Honduras' => 'HND',
            'Croatia' => 'HRV',
            'Haiti' => 'HTI',
            'Hungary' => 'HUN',
            'Indonesia' => 'IDN',
            'Isle of Man' => 'IMN',
            'India' => 'IND',
            'British Indian Ocean Territory' => 'IOT',
            'Ireland' => 'IRL',
            'Iran, Islamic Republic of' => 'IRN',
            'Iraq' => 'IRQ',
            'Iceland' => 'ISL',
            'Israel' => 'ISR',
            'Italy' => 'ITA',
            'Jamaica' => 'JAM',
            'Jersey' => 'JEY',
            'Jordan' => 'JOR',
            'Japan' => 'JPN',
            'Kazakhstan' => 'KAZ',
            'Kenya' => 'KEN',
            'Kyrgyzstan' => 'KGZ',
            'Cambodia' => 'KHM',
            'Kiribati' => 'KIR',
            'Saint Kitts and Nevis' => 'KNA',
            'Korea, Republic of' => 'KOR',
            'Kuwait' => 'KWT',
            'Lao People\'s Democratic Republic' => 'LAO',
            'Lebanon' => 'LBN',
            'Liberia' => 'LBR',
            'Libya' => 'LBY',
            'Saint Lucia' => 'LCA',
            'Liechtenstein' => 'LIE',
            'Sri Lanka' => 'LKA',
            'Lesotho' => 'LSO',
            'Lithuania' => 'LTU',
            'Luxembourg' => 'LUX',
            'Latvia' => 'LVA',
            'Macao' => 'MAC',
            'Saint Martin (French part)' => 'MAF',
            'Morocco' => 'MAR',
            'Monaco' => 'MCO',
            'Moldova, Republic of' => 'MDA',
            'Madagascar' => 'MDG',
            'Maldives' => 'MDV',
            'Mexico' => 'MEX',
            'Marshall Islands' => 'MHL',
            'Macedonia, the former Yugoslav Republic of' => 'MKD',
            'Mali' => 'MLI',
            'Malta' => 'MLT',
            'Myanmar' => 'MMR',
            'Montenegro' => 'MNE',
            'Mongolia' => 'MNG',
            'Northern Mariana Islands' => 'MNP',
            'Mozambique' => 'MOZ',
            'Mauritania' => 'MRT',
            'Montserrat' => 'MSR',
            'Martinique' => 'MTQ',
            'Mauritius' => 'MUS',
            'Malawi' => 'MWI',
            'Malaysia' => 'MYS',
            'Mayotte' => 'MYT',
            'Namibia' => 'NAM',
            'New Caledonia' => 'NCL',
            'Niger' => 'NER',
            'Norfolk Island' => 'NFK',
            'Nigeria' => 'NGA',
            'Nicaragua' => 'NIC',
            'Niue' => 'NIU',
            'Netherlands' => 'NLD',
            'Norway' => 'NOR',
            'Nepal' => 'NPL',
            'Nauru' => 'NRU',
            'New Zealand' => 'NZL',
            'Oman' => 'OMN',
            'Pakistan' => 'PAK',
            'Panama' => 'PAN',
            'Pitcairn' => 'PCN',
            'Peru' => 'PER',
            'Philippines' => 'PHL',
            'Palau' => 'PLW',
            'Papua New Guinea' => 'PNG',
            'Poland' => 'POL',
            'Puerto Rico' => 'PRI',
            'Korea, Democratic People\'s Republic of' => 'PRK',
            'Portugal' => 'PRT',
            'Paraguay' => 'PRY',
            'Palestinian Territory, Occupied' => 'PSE',
            'French Polynesia' => 'PYF',
            'Qatar' => 'QAT',
            'Réunion' => 'REU',
            'Romania' => 'ROU',
            'Russian Federation' => 'RUS',
            'Rwanda' => 'RWA',
            'Saudi Arabia' => 'SAU',
            'Sudan' => 'SDN',
            'Senegal' => 'SEN',
            'Singapore' => 'SGP',
            'South Georgia and the South Sandwich Islands' => 'SGS',
            'Saint Helena, Ascension and Tristan da Cunha' => 'SHN',
            'Svalbard and Jan Mayen' => 'SJM',
            'Solomon Islands' => 'SLB',
            'Sierra Leone' => 'SLE',
            'El Salvador' => 'SLV',
            'San Marino' => 'SMR',
            'Somalia' => 'SOM',
            'Saint Pierre and Miquelon' => 'SPM',
            'Serbia' => 'SRB',
            'South Sudan' => 'SSD',
            'Sao Tome and Principe' => 'STP',
            'Suriname' => 'SUR',
            'Slovakia' => 'SVK',
            'Slovenia' => 'SVN',
            'Sweden' => 'SWE',
            'Swaziland' => 'SWZ',
            'Sint Maarten (Dutch part)' => 'SXM',
            'Seychelles' => 'SYC',
            'Syrian Arab Republic' => 'SYR',
            'Turks and Caicos Islands' => 'TCA',
            'Chad' => 'TCD',
            'Togo' => 'TGO',
            'Thailand' => 'THA',
            'Tajikistan' => 'TJK',
            'Tokelau' => 'TKL',
            'Turkmenistan' => 'TKM',
            'Timor-Leste' => 'TLS',
            'Tonga' => 'TON',
            'Trinidad and Tobago' => 'TTO',
            'Tunisia' => 'TUN',
            'Turkey' => 'TUR',
            'Tuvalu' => 'TUV',
            'Taiwan, Province of China' => 'TWN',
            'Tanzania, United Republic of' => 'TZA',
            'Uganda' => 'UGA',
            'Ukraine' => 'UKR',
            'United States Minor Outlying Islands' => 'UMI',
            'Uruguay' => 'URY',
            'United States' => 'USA',
            'Uzbekistan' => 'UZB',
            'Holy See (Vatican City State)' => 'VAT',
            'Saint Vincent and the Grenadines' => 'VCT',
            'Venezuela, Bolivarian Republic of' => 'VEN',
            'Virgin Islands, British' => 'VGB',
            'Virgin Islands, U.S.' => 'VIR',
            'Viet Nam' => 'VNM',
            'Vanuatu' => 'VUT',
            'Wallis and Futuna' => 'WLF',
            'Samoa' => 'WSM',
            'Yemen' => 'YEM',
            'South Africa' => 'ZAF',
            'Zambia' => 'ZMB',
            'Zimbabwe' => 'ZWE',
            ];

        return $countries[$country_name];
    }

    /**
     * @inheritdoc
     */
    public function error($message, $verbosity = null) : void
    {
        $this->line("<error>[  ERROR  ]</error> - <fg=red;>{$message}</>", null, $verbosity);
    }
}
