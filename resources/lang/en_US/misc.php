<?php

use App\Enumerations\Business\ShippingCalculation;

return [

    'country' => [
        'ad' => 'Andorra',
        'ae' => 'United Arab Emirates',
        'af' => 'Afghanistan',
        'ag' => 'Antigua and Barbuda',
        'al' => 'Albania',
        'am' => 'Armenia',
        'ao' => 'Angola',
        'ar' => 'Argentina',
        'at' => 'Austria',
        'au' => 'Australia',
        'az' => 'Azerbaijan',
        'ba' => 'Bosnia and Herzegovina',
        'bb' => 'Barbados',
        'bd' => 'Bangladesh',
        'be' => 'Belgium',
        'bf' => 'Burkina Faso',
        'bg' => 'Bulgaria',
        'bh' => 'Bahrain',
        'bi' => 'Burundi',
        'bj' => 'Benin',
        'bn' => 'Brunei Darussalam',
        'bo' => 'Bolivia',
        'br' => 'Brazil',
        'bs' => 'Bahamas',
        'bt' => 'Bhutan',
        'bw' => 'Botswana',
        'by' => 'Belarus',
        'bz' => 'Belize',
        'ca' => 'Canada',
        'cd' => 'Democratic Republic of the Congo',
        'cf' => 'Central African Republic',
        'cg' => 'Congo',
        'ch' => 'Switzerland',
        'ci' => 'Côte d\'Ivoire',
        'ck' => 'Cook Islands',
        'cl' => 'Chile',
        'cm' => 'Cameroon',
        'cn' => 'China',
        'co' => 'Colombia',
        'cr' => 'Costa Rica',
        'cu' => 'Cuba',
        'cv' => 'Cabo Verde',
        'cy' => 'Cyprus',
        'cz' => 'Czechia',
        'de' => 'Germany',
        'dj' => 'Djibouti',
        'dk' => 'Denmark',
        'dm' => 'Dominica',
        'do' => 'Dominican Republic',
        'dz' => 'Algeria',
        'ec' => 'Ecuador',
        'ee' => 'Estonia',
        'eg' => 'Egypt',
        'er' => 'Eritrea',
        'es' => 'Spain',
        'et' => 'Ethiopia',
        'fi' => 'Finland',
        'fj' => 'Fiji',
        'fm' => 'Micronesia',
        'fo' => 'Faroe Islands ',
        'fr' => 'France',
        'ga' => 'Gabon',
        'gb' => 'United Kingdom of Great Britain and Northern Ireland',
        'gd' => 'Grenada',
        'ge' => 'Georgia',
        'gh' => 'Ghana',
        'gm' => 'Gambia',
        'gn' => 'Guinea',
        'gq' => 'Equatorial Guinea',
        'gr' => 'Greece',
        'gt' => 'Guatemala',
        'gw' => 'Guinea-Bissau',
        'gy' => 'Guyana',
        'hk' => 'Hong Kong',
        'hn' => 'Honduras',
        'hr' => 'Croatia',
        'ht' => 'Haiti',
        'hu' => 'Hungary',
        'id' => 'Indonesia',
        'ie' => 'Ireland',
        'il' => 'Israel',
        'in' => 'India',
        'iq' => 'Iraq',
        'ir' => 'Iran',
        'is' => 'Iceland',
        'it' => 'Italy',
        'jm' => 'Jamaica',
        'jo' => 'Jordan',
        'jp' => 'Japan',
        'ke' => 'Kenya',
        'kg' => 'Kyrgyzstan',
        'kh' => 'Cambodia',
        'ki' => 'Kiribati',
        'km' => 'Comoros',
        'kn' => 'Saint Kitts and Nevis',
        'kp' => 'Democratic People\'s Republic of Korea',
        'kr' => 'Republic of Korea',
        'kw' => 'Kuwait',
        'kz' => 'Kazakhstan',
        'la' => 'Lao People\'s Democratic Republic',
        'lb' => 'Lebanon',
        'lc' => 'Saint Lucia',
        'lk' => 'Sri Lanka',
        'lr' => 'Liberia',
        'ls' => 'Lesotho',
        'lt' => 'Lithuania',
        'lu' => 'Luxembourg',
        'lv' => 'Latvia',
        'ly' => 'Libya',
        'ma' => 'Morocco',
        'mc' => 'Monaco',
        'md' => 'Republic of Moldova',
        'me' => 'Montenegro',
        'mg' => 'Madagascar',
        'mh' => 'Marshall Islands',
        'mk' => 'North Macedonia',
        'ml' => 'Mali',
        'mm' => 'Myanmar',
        'mn' => 'Mongolia',
        'mr' => 'Mauritania',
        'mt' => 'Malta',
        'mu' => 'Mauritius',
        'mv' => 'Maldives',
        'mw' => 'Malawi',
        'mx' => 'Mexico',
        'my' => 'Malaysia',
        'mz' => 'Mozambique',
        'na' => 'Namibia',
        'ne' => 'Niger',
        'ng' => 'Nigeria',
        'ni' => 'Nicaragua',
        'nl' => 'Netherlands',
        'no' => 'Norway',
        'np' => 'Nepal',
        'nr' => 'Nauru',
        'nu' => 'Niue',
        'nz' => 'New Zealand',
        'om' => 'Oman',
        'pa' => 'Panama',
        'pe' => 'Peru',
        'pg' => 'Papua New Guinea',
        'ph' => 'Philippines',
        'pk' => 'Pakistan',
        'pl' => 'Poland',
        'pt' => 'Portugal',
        'pw' => 'Palau',
        'py' => 'Paraguay',
        'qa' => 'Qatar',
        'ro' => 'Romania',
        'rs' => 'Serbia',
        'ru' => 'Russian Federation',
        'rw' => 'Rwanda',
        'sa' => 'Saudi Arabia',
        'sb' => 'Solomon Islands',
        'sc' => 'Seychelles',
        'sd' => 'Sudan',
        'se' => 'Sweden',
        'sg' => 'Singapore',
        'si' => 'Slovenia',
        'sk' => 'Slovakia',
        'sl' => 'Sierra Leone',
        'sm' => 'San Marino',
        'sn' => 'Senegal',
        'so' => 'Somalia',
        'sr' => 'Suriname',
        'ss' => 'South Sudan',
        'st' => 'Sao Tome and Principe',
        'sv' => 'El Salvador',
        'sy' => 'Syrian Arab Republic',
        'sz' => 'Eswatini',
        'td' => 'Chad',
        'tg' => 'Togo',
        'th' => 'Thailand',
        'tj' => 'Tajikistan',
        'tk' => 'Tokelau ',
        'tl' => 'Timor-Leste',
        'tm' => 'Turkmenistan',
        'tn' => 'Tunisia',
        'to' => 'Tonga',
        'tr' => 'Turkey',
        'tt' => 'Trinidad and Tobago',
        'tv' => 'Tuvalu',
        'tz' => 'United Republic of Tanzania',
        'ua' => 'Ukraine',
        'ug' => 'Uganda',
        'us' => 'United States of America',
        'uy' => 'Uruguay',
        'uz' => 'Uzbekistan',
        'vc' => 'Saint Vincent and the Grenadines',
        've' => 'Venezuela',
        'vn' => 'Vietnam',
        'vu' => 'Vanuatu',
        'ws' => 'Samoa',
        'ye' => 'Yemen',
        'za' => 'South Africa',
        'zm' => 'Zambia',
        'zw' => 'Zimbabwe',
    ],

    'currency' => [
        'aed' => 'UAE Dirham (د.إ)',
        'afn' => 'Afghani (Af)',
        'all' => 'Lek (L)',
        'amd' => 'Armenian Dram (Դ)',
        'aoa' => 'Kwanza (Kz)',
        'ars' => 'Argentine Peso ($)',
        'aud' => 'Australian Dollar ($)',
        'awg' => 'Aruban Guilder/Florin (ƒ)',
        'azn' => 'Azerbaijanian Manat (ман)',
        'bam' => 'Konvertibilna Marka (КМ)',
        'bbd' => 'Barbados Dollar ($)',
        'bdt' => 'Taka (৳)',
        'bgn' => 'Bulgarian Lev (лв)',
        'bhd' => 'Bahraini Dinar (ب.د)',
        'bif' => 'Burundi Franc (₣)',
        'bmd' => 'Bermudian Dollar ($)',
        'bnd' => 'Brunei Dollar ($)',
        'bob' => 'Boliviano (Bs.)',
        'brl' => 'Brazilian Real (R$)',
        'bsd' => 'Bahamian Dollar ($)',
        'btn' => 'Ngultrum',
        'bwp' => 'Pula (P)',
        'byn' => 'Belarusian Ruble (Br)',
        'bzd' => 'Belize Dollar ($)',
        'cad' => 'Canadian Dollar ($)',
        'cdf' => 'Congolese Franc (₣)',
        'chf' => 'Swiss Franc (₣)',
        'clp' => 'Chilean Peso ($)',
        'cny' => 'Yuan (¥)',
        'cop' => 'Colombian Peso ($)',
        'crc' => 'Costa Rican Colon (₡)',
        'cup' => 'Cuban Peso ($)',
        'cve' => 'Cape Verde Escudo ($)',
        'czk' => 'Czech Koruna (Kč)',
        'djf' => 'Djibouti Franc (₣)',
        'dkk' => 'Danish Krone (kr)',
        'dop' => 'Dominican Peso ($)',
        'dzd' => 'Algerian Dinar (د.ج)',
        'egp' => 'Egyptian Pound (£)',
        'ern' => 'Nakfa (Nfk)',
        'etb' => 'Ethiopian Birr',
        'eur' => 'Euro (€)',
        'fjd' => 'Fiji Dollar ($)',
        'fkp' => 'Falkland Islands Pound (£)',
        'gbp' => 'Pound Sterling (£)',
        'gel' => 'Lari (ლ)',
        'ghs' => 'Cedi (₵)',
        'gip' => 'Gibraltar Pound (£)',
        'gmd' => 'Dalasi (D)',
        'gnf' => 'Guinea Franc (₣)',
        'gtq' => 'Quetzal (Q)',
        'gyd' => 'Guyana Dollar ($)',
        'hkd' => 'Hong Kong Dollar ($)',
        'hnl' => 'Lempira (L)',
        'hrk' => 'Croatian Kuna (Kn)',
        'htg' => 'Gourde (G)',
        'huf' => 'Forint (Ft)',
        'idr' => 'Rupiah (Rp)',
        'ils' => 'New Israeli Shekel (₪)',
        'inr' => 'Indian Rupee (₹)',
        'iqd' => 'Iraqi Dinar (ع.د)',
        'irr' => 'Iranian Rial (﷼)',
        'isk' => 'Iceland Krona (Kr)',
        'jmd' => 'Jamaican Dollar ($)',
        'jod' => 'Jordanian Dinar (د.ا)',
        'jpy' => 'Yen (¥)',
        'kes' => 'Kenyan Shilling (Sh)',
        'kgs' => 'Som',
        'khr' => 'Riel (៛)',
        'kpw' => 'North Korean Won (₩)',
        'krw' => 'South Korean Won (₩)',
        'kwd' => 'Kuwaiti Dinar (د.ك)',
        'kyd' => 'Cayman Islands Dollar ($)',
        'kzt' => 'Tenge (〒)',
        'lak' => 'Kip (₭)',
        'lbp' => 'Lebanese Pound (ل.ل)',
        'lkr' => 'Sri Lanka Rupee (Rs)',
        'lrd' => 'Liberian Dollar ($)',
        'lsl' => 'Loti (L)',
        'lyd' => 'Libyan Dinar (ل.د)',
        'mad' => 'Moroccan Dirham (د.م.)',
        'mdl' => 'Moldovan Leu (L)',
        'mga' => 'Malagasy Ariary',
        'mkd' => 'Denar (ден)',
        'mmk' => 'Kyat (K)',
        'mnt' => 'Tugrik (₮)',
        'mop' => 'Pataca (P)',
        'mru' => 'Ouguiya (UM)',
        'mur' => 'Mauritius Rupee (₨)',
        'mvr' => 'Rufiyaa (ރ.)',
        'mwk' => 'Kwacha (MK)',
        'mxn' => 'Mexican Peso ($)',
        'myr' => 'Malaysian Ringgit (RM)',
        'mzn' => 'Metical (MTn)',
        'nad' => 'Namibia Dollar ($)',
        'ngn' => 'Naira (₦)',
        'nio' => 'Cordoba Oro (C$)',
        'nok' => 'Norwegian Krone (kr)',
        'npr' => 'Nepalese Rupee (₨)',
        'nzd' => 'New Zealand Dollar ($)',
        'omr' => 'Rial Omani (ر.ع.)',
        'pab' => 'Balboa (B/.)',
        'pen' => 'Nuevo Sol (S/.)',
        'pgk' => 'Kina (K)',
        'php' => 'Philippine Peso (₱)',
        'pkr' => 'Pakistan Rupee (₨)',
        'pln' => 'PZloty (zł)',
        'pyg' => 'Guarani (₲)',
        'qar' => 'Qatari Rial (ر.ق)',
        'ron' => 'Leu (L)',
        'rsd' => 'Serbian Dinar (din)',
        'rub' => 'Russian Ruble (р. )',
        'rwf' => 'Rwanda Franc (₣)',
        'sar' => 'Saudi Riyal (ر.س)',
        'sbd' => 'Solomon Islands Dollar ($)',
        'scr' => 'Seychelles Rupee (₨)',
        'sdg' => 'Sudanese Pound (£)',
        'sek' => 'Swedish Krona (kr)',
        'sgd' => 'Singapore Dollar ($)',
        'shp' => 'Saint Helena Pound (£)',
        'sll' => 'Leone (Le)',
        'sos' => 'Somali Shilling (Sh)',
        'srd' => 'Suriname Dollar ($)',
        'stn' => 'Dobra (Db)',
        'syp' => 'Syrian Pound (ل.س)',
        'szl' => 'Lilangeni (L)',
        'thb' => 'Baht (฿)',
        'tjs' => 'Somoni (ЅМ)',
        'tmt' => 'Manat (m)',
        'tnd' => 'Tunisian Dinar (د.ت)',
        'top' => 'Pa’anga (T$)',
        'try' => 'Turkish Lira (₤)',
        'ttd' => 'Trinidad and Tobago Dollar ($)',
        'twd' => 'Taiwan Dollar ($)',
        'tzs' => 'Tanzanian Shilling (Sh)',
        'uah' => 'Hryvnia (₴)',
        'ugx' => 'Uganda Shilling (Sh)',
        'usd' => 'US Dollar ($)',
        'uyu' => 'Peso Uruguayo ($)',
        'uzs' => 'Uzbekistan Sum',
        'vef' => 'Bolivar Fuerte (Bs F)',
        'vnd' => 'Dong (₫)',
        'vuv' => 'Vatu (Vt)',
        'wst' => 'Tala (T)',
        'xaf' => 'CFA Franc BCEAO (₣)',
        'xcd' => 'East Caribbean Dollar ($)',
        'xpf' => 'CFP Franc (₣)',
        'yer' => 'Yemeni Rial (﷼)',
        'zar' => 'Rand (R)',
        'zmw' => 'Zambian Kwacha (ZK)',
        'zwl' => 'Zimbabwe Dollar ($)',
    ],

    'global' => 'Global',

    'shipping_calculation' => [
        ShippingCalculation::FLAT => 'Flat rate',
        ShippingCalculation::FEE_PER_UNIT => 'Fee Per Unit',
    ],

];
