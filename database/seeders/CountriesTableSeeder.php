<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CountriesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('countries')->delete();
        
        \DB::table('countries')->insert(array (
            0 => 
            array (
                'id' => 1,
                'country_code' => 'AD',
                'country' => 'Andorra',
                'currency_code' => 'EUR',
            ),
            1 => 
            array (
                'id' => 2,
                'country_code' => 'AE',
                'country' => 'United Arab Emirates',
                'currency_code' => 'AED',
            ),
            2 => 
            array (
                'id' => 3,
                'country_code' => 'AF',
                'country' => 'Afghanistan',
                'currency_code' => 'AFN',
            ),
            3 => 
            array (
                'id' => 4,
                'country_code' => 'AG',
                'country' => 'Antigua and Barbuda',
                'currency_code' => 'XCD',
            ),
            4 => 
            array (
                'id' => 5,
                'country_code' => 'AI',
                'country' => 'Anguilla',
                'currency_code' => 'XCD',
            ),
            5 => 
            array (
                'id' => 6,
                'country_code' => 'AL',
                'country' => 'Albania',
                'currency_code' => 'ALL',
            ),
            6 => 
            array (
                'id' => 7,
                'country_code' => 'AM',
                'country' => 'Armenia',
                'currency_code' => 'AMD',
            ),
            7 => 
            array (
                'id' => 8,
                'country_code' => 'AO',
                'country' => 'Angola',
                'currency_code' => 'AOA',
            ),
            8 => 
            array (
                'id' => 9,
                'country_code' => 'AQ',
                'country' => 'Antarctica',
                'currency_code' => '',
            ),
            9 => 
            array (
                'id' => 10,
                'country_code' => 'AR',
                'country' => 'Argentina',
                'currency_code' => 'ARS',
            ),
            10 => 
            array (
                'id' => 11,
                'country_code' => 'AS',
                'country' => 'American Samoa',
                'currency_code' => 'USD',
            ),
            11 => 
            array (
                'id' => 12,
                'country_code' => 'AT',
                'country' => 'Austria',
                'currency_code' => 'EUR',
            ),
            12 => 
            array (
                'id' => 13,
                'country_code' => 'AU',
                'country' => 'Australia',
                'currency_code' => 'AUD',
            ),
            13 => 
            array (
                'id' => 14,
                'country_code' => 'AW',
                'country' => 'Aruba',
                'currency_code' => 'AWG',
            ),
            14 => 
            array (
                'id' => 15,
                'country_code' => 'AX',
                'country' => 'Ãƒâ€¦land',
                'currency_code' => 'EUR',
            ),
            15 => 
            array (
                'id' => 16,
                'country_code' => 'AZ',
                'country' => 'Azerbaijan',
                'currency_code' => 'AZN',
            ),
            16 => 
            array (
                'id' => 17,
                'country_code' => 'BA',
                'country' => 'Bosnia and Herzegovina',
                'currency_code' => 'BAM',
            ),
            17 => 
            array (
                'id' => 18,
                'country_code' => 'BB',
                'country' => 'Barbados',
                'currency_code' => 'BBD',
            ),
            18 => 
            array (
                'id' => 19,
                'country_code' => 'BD',
                'country' => 'Bangladesh',
                'currency_code' => 'BDT',
            ),
            19 => 
            array (
                'id' => 20,
                'country_code' => 'BE',
                'country' => 'Belgium',
                'currency_code' => 'EUR',
            ),
            20 => 
            array (
                'id' => 21,
                'country_code' => 'BF',
                'country' => 'Burkina Faso',
                'currency_code' => 'XOF',
            ),
            21 => 
            array (
                'id' => 22,
                'country_code' => 'BG',
                'country' => 'Bulgaria',
                'currency_code' => 'BGN',
            ),
            22 => 
            array (
                'id' => 23,
                'country_code' => 'BH',
                'country' => 'Bahrain',
                'currency_code' => 'BHD',
            ),
            23 => 
            array (
                'id' => 24,
                'country_code' => 'BI',
                'country' => 'Burundi',
                'currency_code' => 'BIF',
            ),
            24 => 
            array (
                'id' => 25,
                'country_code' => 'BJ',
                'country' => 'Benin',
                'currency_code' => 'XOF',
            ),
            25 => 
            array (
                'id' => 26,
                'country_code' => 'BL',
                'country' => 'Saint BarthÃƒÂ©lemy',
                'currency_code' => 'EUR',
            ),
            26 => 
            array (
                'id' => 27,
                'country_code' => 'BM',
                'country' => 'Bermuda',
                'currency_code' => 'BMD',
            ),
            27 => 
            array (
                'id' => 28,
                'country_code' => 'BN',
                'country' => 'Brunei',
                'currency_code' => 'BND',
            ),
            28 => 
            array (
                'id' => 29,
                'country_code' => 'BO',
                'country' => 'Bolivia',
                'currency_code' => 'BOB',
            ),
            29 => 
            array (
                'id' => 30,
                'country_code' => 'BQ',
                'country' => 'Bonaire',
                'currency_code' => 'USD',
            ),
            30 => 
            array (
                'id' => 31,
                'country_code' => 'BR',
                'country' => 'Brazil',
                'currency_code' => 'BRL',
            ),
            31 => 
            array (
                'id' => 32,
                'country_code' => 'BS',
                'country' => 'Bahamas',
                'currency_code' => 'BSD',
            ),
            32 => 
            array (
                'id' => 33,
                'country_code' => 'BT',
                'country' => 'Bhutan',
                'currency_code' => 'BTN',
            ),
            33 => 
            array (
                'id' => 34,
                'country_code' => 'BV',
                'country' => 'Bouvet Island',
                'currency_code' => 'NOK',
            ),
            34 => 
            array (
                'id' => 35,
                'country_code' => 'BW',
                'country' => 'Botswana',
                'currency_code' => 'BWP',
            ),
            35 => 
            array (
                'id' => 36,
                'country_code' => 'BY',
                'country' => 'Belarus',
                'currency_code' => 'BYR',
            ),
            36 => 
            array (
                'id' => 37,
                'country_code' => 'BZ',
                'country' => 'Belize',
                'currency_code' => 'BZD',
            ),
            37 => 
            array (
                'id' => 38,
                'country_code' => 'CA',
                'country' => 'Canada',
                'currency_code' => 'CAD',
            ),
            38 => 
            array (
                'id' => 39,
                'country_code' => 'CC',
                'country' => 'Cocos [Keeling] Islands',
                'currency_code' => 'AUD',
            ),
            39 => 
            array (
                'id' => 40,
                'country_code' => 'CD',
                'country' => 'Democratic Republic of the Congo',
                'currency_code' => 'CDF',
            ),
            40 => 
            array (
                'id' => 41,
                'country_code' => 'CF',
                'country' => 'Central African Republic',
                'currency_code' => 'XAF',
            ),
            41 => 
            array (
                'id' => 42,
                'country_code' => 'CG',
                'country' => 'Republic of the Congo',
                'currency_code' => 'XAF',
            ),
            42 => 
            array (
                'id' => 43,
                'country_code' => 'CH',
                'country' => 'Switzerland',
                'currency_code' => 'CHF',
            ),
            43 => 
            array (
                'id' => 44,
                'country_code' => 'CI',
                'country' => 'Ivory Coast',
                'currency_code' => 'XOF',
            ),
            44 => 
            array (
                'id' => 45,
                'country_code' => 'CK',
                'country' => 'Cook Islands',
                'currency_code' => 'NZD',
            ),
            45 => 
            array (
                'id' => 46,
                'country_code' => 'CL',
                'country' => 'Chile',
                'currency_code' => 'CLP',
            ),
            46 => 
            array (
                'id' => 47,
                'country_code' => 'CM',
                'country' => 'Cameroon',
                'currency_code' => 'XAF',
            ),
            47 => 
            array (
                'id' => 48,
                'country_code' => 'CN',
                'country' => 'China',
                'currency_code' => 'CNY',
            ),
            48 => 
            array (
                'id' => 49,
                'country_code' => 'CO',
                'country' => 'Colombia',
                'currency_code' => 'COP',
            ),
            49 => 
            array (
                'id' => 50,
                'country_code' => 'CR',
                'country' => 'Costa Rica',
                'currency_code' => 'CRC',
            ),
            50 => 
            array (
                'id' => 51,
                'country_code' => 'CU',
                'country' => 'Cuba',
                'currency_code' => 'CUP',
            ),
            51 => 
            array (
                'id' => 52,
                'country_code' => 'CV',
                'country' => 'Cape Verde',
                'currency_code' => 'CVE',
            ),
            52 => 
            array (
                'id' => 53,
                'country_code' => 'CW',
                'country' => 'Curacao',
                'currency_code' => 'ANG',
            ),
            53 => 
            array (
                'id' => 54,
                'country_code' => 'CX',
                'country' => 'Christmas Island',
                'currency_code' => 'AUD',
            ),
            54 => 
            array (
                'id' => 55,
                'country_code' => 'CY',
                'country' => 'Cyprus',
                'currency_code' => 'EUR',
            ),
            55 => 
            array (
                'id' => 56,
                'country_code' => 'CZ',
                'country' => 'Czech Republic',
                'currency_code' => 'CZK',
            ),
            56 => 
            array (
                'id' => 57,
                'country_code' => 'DE',
                'country' => 'Germany',
                'currency_code' => 'EUR',
            ),
            57 => 
            array (
                'id' => 58,
                'country_code' => 'DJ',
                'country' => 'Djibouti',
                'currency_code' => 'DJF',
            ),
            58 => 
            array (
                'id' => 59,
                'country_code' => 'DK',
                'country' => 'Denmark',
                'currency_code' => 'DKK',
            ),
            59 => 
            array (
                'id' => 60,
                'country_code' => 'DM',
                'country' => 'Dominica',
                'currency_code' => 'XCD',
            ),
            60 => 
            array (
                'id' => 61,
                'country_code' => 'DO',
                'country' => 'Dominican Republic',
                'currency_code' => 'DOP',
            ),
            61 => 
            array (
                'id' => 62,
                'country_code' => 'DZ',
                'country' => 'Algeria',
                'currency_code' => 'DZD',
            ),
            62 => 
            array (
                'id' => 63,
                'country_code' => 'EC',
                'country' => 'Ecuador',
                'currency_code' => 'USD',
            ),
            63 => 
            array (
                'id' => 64,
                'country_code' => 'EE',
                'country' => 'Estonia',
                'currency_code' => 'EUR',
            ),
            64 => 
            array (
                'id' => 65,
                'country_code' => 'EG',
                'country' => 'Egypt',
                'currency_code' => 'EGP',
            ),
            65 => 
            array (
                'id' => 66,
                'country_code' => 'EH',
                'country' => 'Western Sahara',
                'currency_code' => 'MAD',
            ),
            66 => 
            array (
                'id' => 67,
                'country_code' => 'ER',
                'country' => 'Eritrea',
                'currency_code' => 'ERN',
            ),
            67 => 
            array (
                'id' => 68,
                'country_code' => 'ES',
                'country' => 'Spain',
                'currency_code' => 'EUR',
            ),
            68 => 
            array (
                'id' => 69,
                'country_code' => 'ET',
                'country' => 'Ethiopia',
                'currency_code' => 'ETB',
            ),
            69 => 
            array (
                'id' => 70,
                'country_code' => 'FI',
                'country' => 'Finland',
                'currency_code' => 'EUR',
            ),
            70 => 
            array (
                'id' => 71,
                'country_code' => 'FJ',
                'country' => 'Fiji',
                'currency_code' => 'FJD',
            ),
            71 => 
            array (
                'id' => 72,
                'country_code' => 'FK',
                'country' => 'Falkland Islands',
                'currency_code' => 'FKP',
            ),
            72 => 
            array (
                'id' => 73,
                'country_code' => 'FM',
                'country' => 'Micronesia',
                'currency_code' => 'USD',
            ),
            73 => 
            array (
                'id' => 74,
                'country_code' => 'FO',
                'country' => 'Faroe Islands',
                'currency_code' => 'DKK',
            ),
            74 => 
            array (
                'id' => 75,
                'country_code' => 'FR',
                'country' => 'France',
                'currency_code' => 'EUR',
            ),
            75 => 
            array (
                'id' => 76,
                'country_code' => 'GA',
                'country' => 'Gabon',
                'currency_code' => 'XAF',
            ),
            76 => 
            array (
                'id' => 77,
                'country_code' => 'GB',
                'country' => 'United Kingdom',
                'currency_code' => 'GBP',
            ),
            77 => 
            array (
                'id' => 78,
                'country_code' => 'GD',
                'country' => 'Grenada',
                'currency_code' => 'XCD',
            ),
            78 => 
            array (
                'id' => 79,
                'country_code' => 'GE',
                'country' => 'Georgia',
                'currency_code' => 'GEL',
            ),
            79 => 
            array (
                'id' => 80,
                'country_code' => 'GF',
                'country' => 'French Guiana',
                'currency_code' => 'EUR',
            ),
            80 => 
            array (
                'id' => 81,
                'country_code' => 'GG',
                'country' => 'Guernsey',
                'currency_code' => 'GBP',
            ),
            81 => 
            array (
                'id' => 82,
                'country_code' => 'GH',
                'country' => 'Ghana',
                'currency_code' => 'GHS',
            ),
            82 => 
            array (
                'id' => 83,
                'country_code' => 'GI',
                'country' => 'Gibraltar',
                'currency_code' => 'GIP',
            ),
            83 => 
            array (
                'id' => 84,
                'country_code' => 'GL',
                'country' => 'Greenland',
                'currency_code' => 'DKK',
            ),
            84 => 
            array (
                'id' => 85,
                'country_code' => 'GM',
                'country' => 'Gambia',
                'currency_code' => 'GMD',
            ),
            85 => 
            array (
                'id' => 86,
                'country_code' => 'GN',
                'country' => 'Guinea',
                'currency_code' => 'GNF',
            ),
            86 => 
            array (
                'id' => 87,
                'country_code' => 'GP',
                'country' => 'Guadeloupe',
                'currency_code' => 'EUR',
            ),
            87 => 
            array (
                'id' => 88,
                'country_code' => 'GQ',
                'country' => 'Equatorial Guinea',
                'currency_code' => 'XAF',
            ),
            88 => 
            array (
                'id' => 89,
                'country_code' => 'GR',
                'country' => 'Greece',
                'currency_code' => 'EUR',
            ),
            89 => 
            array (
                'id' => 90,
                'country_code' => 'GS',
                'country' => 'South Georgia and the South Sandwich Islands',
                'currency_code' => 'GBP',
            ),
            90 => 
            array (
                'id' => 91,
                'country_code' => 'GT',
                'country' => 'Guatemala',
                'currency_code' => 'GTQ',
            ),
            91 => 
            array (
                'id' => 92,
                'country_code' => 'GU',
                'country' => 'Guam',
                'currency_code' => 'USD',
            ),
            92 => 
            array (
                'id' => 93,
                'country_code' => 'GW',
                'country' => 'Guinea-Bissau',
                'currency_code' => 'XOF',
            ),
            93 => 
            array (
                'id' => 94,
                'country_code' => 'GY',
                'country' => 'Guyana',
                'currency_code' => 'GYD',
            ),
            94 => 
            array (
                'id' => 95,
                'country_code' => 'HK',
                'country' => 'Hong Kong',
                'currency_code' => 'HKD',
            ),
            95 => 
            array (
                'id' => 96,
                'country_code' => 'HM',
                'country' => 'Heard Island and McDonald Islands',
                'currency_code' => 'AUD',
            ),
            96 => 
            array (
                'id' => 97,
                'country_code' => 'HN',
                'country' => 'Honduras',
                'currency_code' => 'HNL',
            ),
            97 => 
            array (
                'id' => 98,
                'country_code' => 'HR',
                'country' => 'Croatia',
                'currency_code' => 'HRK',
            ),
            98 => 
            array (
                'id' => 99,
                'country_code' => 'HT',
                'country' => 'Haiti',
                'currency_code' => 'HTG',
            ),
            99 => 
            array (
                'id' => 100,
                'country_code' => 'HU',
                'country' => 'Hungary',
                'currency_code' => 'HUF',
            ),
            100 => 
            array (
                'id' => 101,
                'country_code' => 'ID',
                'country' => 'Indonesia',
                'currency_code' => 'IDR',
            ),
            101 => 
            array (
                'id' => 102,
                'country_code' => 'IE',
                'country' => 'Ireland',
                'currency_code' => 'EUR',
            ),
            102 => 
            array (
                'id' => 103,
                'country_code' => 'IL',
                'country' => 'Israel',
                'currency_code' => 'ILS',
            ),
            103 => 
            array (
                'id' => 104,
                'country_code' => 'IM',
                'country' => 'Isle of Man',
                'currency_code' => 'GBP',
            ),
            104 => 
            array (
                'id' => 105,
                'country_code' => 'IN',
                'country' => 'India',
                'currency_code' => 'INR',
            ),
            105 => 
            array (
                'id' => 106,
                'country_code' => 'IO',
                'country' => 'British Indian Ocean Territory',
                'currency_code' => 'USD',
            ),
            106 => 
            array (
                'id' => 107,
                'country_code' => 'IQ',
                'country' => 'Iraq',
                'currency_code' => 'IQD',
            ),
            107 => 
            array (
                'id' => 108,
                'country_code' => 'IR',
                'country' => 'Iran',
                'currency_code' => 'IRR',
            ),
            108 => 
            array (
                'id' => 109,
                'country_code' => 'IS',
                'country' => 'Iceland',
                'currency_code' => 'ISK',
            ),
            109 => 
            array (
                'id' => 110,
                'country_code' => 'IT',
                'country' => 'Italy',
                'currency_code' => 'EUR',
            ),
            110 => 
            array (
                'id' => 111,
                'country_code' => 'JE',
                'country' => 'Jersey',
                'currency_code' => 'GBP',
            ),
            111 => 
            array (
                'id' => 112,
                'country_code' => 'JM',
                'country' => 'Jamaica',
                'currency_code' => 'JMD',
            ),
            112 => 
            array (
                'id' => 113,
                'country_code' => 'JO',
                'country' => 'Jordan',
                'currency_code' => 'JOD',
            ),
            113 => 
            array (
                'id' => 114,
                'country_code' => 'JP',
                'country' => 'Japan',
                'currency_code' => 'JPY',
            ),
            114 => 
            array (
                'id' => 115,
                'country_code' => 'KE',
                'country' => 'Kenya',
                'currency_code' => 'KES',
            ),
            115 => 
            array (
                'id' => 116,
                'country_code' => 'KG',
                'country' => 'Kyrgyzstan',
                'currency_code' => 'KGS',
            ),
            116 => 
            array (
                'id' => 117,
                'country_code' => 'KH',
                'country' => 'Cambodia',
                'currency_code' => 'KHR',
            ),
            117 => 
            array (
                'id' => 118,
                'country_code' => 'KI',
                'country' => 'Kiribati',
                'currency_code' => 'AUD',
            ),
            118 => 
            array (
                'id' => 119,
                'country_code' => 'KM',
                'country' => 'Comoros',
                'currency_code' => 'KMF',
            ),
            119 => 
            array (
                'id' => 120,
                'country_code' => 'KN',
                'country' => 'Saint Kitts and Nevis',
                'currency_code' => 'XCD',
            ),
            120 => 
            array (
                'id' => 121,
                'country_code' => 'KP',
                'country' => 'North Korea',
                'currency_code' => 'KPW',
            ),
            121 => 
            array (
                'id' => 122,
                'country_code' => 'KR',
                'country' => 'South Korea',
                'currency_code' => 'KRW',
            ),
            122 => 
            array (
                'id' => 123,
                'country_code' => 'KW',
                'country' => 'Kuwait',
                'currency_code' => 'KWD',
            ),
            123 => 
            array (
                'id' => 124,
                'country_code' => 'KY',
                'country' => 'Cayman Islands',
                'currency_code' => 'KYD',
            ),
            124 => 
            array (
                'id' => 125,
                'country_code' => 'KZ',
                'country' => 'Kazakhstan',
                'currency_code' => 'KZT',
            ),
            125 => 
            array (
                'id' => 126,
                'country_code' => 'LA',
                'country' => 'Laos',
                'currency_code' => 'LAK',
            ),
            126 => 
            array (
                'id' => 127,
                'country_code' => 'LB',
                'country' => 'Lebanon',
                'currency_code' => 'LBP',
            ),
            127 => 
            array (
                'id' => 128,
                'country_code' => 'LC',
                'country' => 'Saint Lucia',
                'currency_code' => 'XCD',
            ),
            128 => 
            array (
                'id' => 129,
                'country_code' => 'LI',
                'country' => 'Liechtenstein',
                'currency_code' => 'CHF',
            ),
            129 => 
            array (
                'id' => 130,
                'country_code' => 'LK',
                'country' => 'Sri Lanka',
                'currency_code' => 'LKR',
            ),
            130 => 
            array (
                'id' => 131,
                'country_code' => 'LR',
                'country' => 'Liberia',
                'currency_code' => 'LRD',
            ),
            131 => 
            array (
                'id' => 132,
                'country_code' => 'LS',
                'country' => 'Lesotho',
                'currency_code' => 'LSL',
            ),
            132 => 
            array (
                'id' => 133,
                'country_code' => 'LT',
                'country' => 'Lithuania',
                'currency_code' => 'EUR',
            ),
            133 => 
            array (
                'id' => 134,
                'country_code' => 'LU',
                'country' => 'Luxembourg',
                'currency_code' => 'EUR',
            ),
            134 => 
            array (
                'id' => 135,
                'country_code' => 'LV',
                'country' => 'Latvia',
                'currency_code' => 'EUR',
            ),
            135 => 
            array (
                'id' => 136,
                'country_code' => 'LY',
                'country' => 'Libya',
                'currency_code' => 'LYD',
            ),
            136 => 
            array (
                'id' => 137,
                'country_code' => 'MA',
                'country' => 'Morocco',
                'currency_code' => 'MAD',
            ),
            137 => 
            array (
                'id' => 138,
                'country_code' => 'MC',
                'country' => 'Monaco',
                'currency_code' => 'EUR',
            ),
            138 => 
            array (
                'id' => 139,
                'country_code' => 'MD',
                'country' => 'Moldova',
                'currency_code' => 'MDL',
            ),
            139 => 
            array (
                'id' => 140,
                'country_code' => 'ME',
                'country' => 'Montenegro',
                'currency_code' => 'EUR',
            ),
            140 => 
            array (
                'id' => 141,
                'country_code' => 'MF',
                'country' => 'Saint Martin',
                'currency_code' => 'EUR',
            ),
            141 => 
            array (
                'id' => 142,
                'country_code' => 'MG',
                'country' => 'Madagascar',
                'currency_code' => 'MGA',
            ),
            142 => 
            array (
                'id' => 143,
                'country_code' => 'MH',
                'country' => 'Marshall Islands',
                'currency_code' => 'USD',
            ),
            143 => 
            array (
                'id' => 144,
                'country_code' => 'MK',
                'country' => 'Macedonia',
                'currency_code' => 'MKD',
            ),
            144 => 
            array (
                'id' => 145,
                'country_code' => 'ML',
                'country' => 'Mali',
                'currency_code' => 'XOF',
            ),
            145 => 
            array (
                'id' => 146,
                'country_code' => 'MM',
                'country' => 'Myanmar [Burma]',
                'currency_code' => 'MMK',
            ),
            146 => 
            array (
                'id' => 147,
                'country_code' => 'MN',
                'country' => 'Mongolia',
                'currency_code' => 'MNT',
            ),
            147 => 
            array (
                'id' => 148,
                'country_code' => 'MO',
                'country' => 'Macao',
                'currency_code' => 'MOP',
            ),
            148 => 
            array (
                'id' => 149,
                'country_code' => 'MP',
                'country' => 'Northern Mariana Islands',
                'currency_code' => 'USD',
            ),
            149 => 
            array (
                'id' => 150,
                'country_code' => 'MQ',
                'country' => 'Martinique',
                'currency_code' => 'EUR',
            ),
            150 => 
            array (
                'id' => 151,
                'country_code' => 'MR',
                'country' => 'Mauritania',
                'currency_code' => 'MRO',
            ),
            151 => 
            array (
                'id' => 152,
                'country_code' => 'MS',
                'country' => 'Montserrat',
                'currency_code' => 'XCD',
            ),
            152 => 
            array (
                'id' => 153,
                'country_code' => 'MT',
                'country' => 'Malta',
                'currency_code' => 'EUR',
            ),
            153 => 
            array (
                'id' => 154,
                'country_code' => 'MU',
                'country' => 'Mauritius',
                'currency_code' => 'MUR',
            ),
            154 => 
            array (
                'id' => 155,
                'country_code' => 'MV',
                'country' => 'Maldives',
                'currency_code' => 'MVR',
            ),
            155 => 
            array (
                'id' => 156,
                'country_code' => 'MW',
                'country' => 'Malawi',
                'currency_code' => 'MWK',
            ),
            156 => 
            array (
                'id' => 157,
                'country_code' => 'MX',
                'country' => 'Mexico',
                'currency_code' => 'MXN',
            ),
            157 => 
            array (
                'id' => 158,
                'country_code' => 'MY',
                'country' => 'Malaysia',
                'currency_code' => 'MYR',
            ),
            158 => 
            array (
                'id' => 159,
                'country_code' => 'MZ',
                'country' => 'Mozambique',
                'currency_code' => 'MZN',
            ),
            159 => 
            array (
                'id' => 160,
                'country_code' => 'NA',
                'country' => 'Namibia',
                'currency_code' => 'NAD',
            ),
            160 => 
            array (
                'id' => 161,
                'country_code' => 'NC',
                'country' => 'New Caledonia',
                'currency_code' => 'XPF',
            ),
            161 => 
            array (
                'id' => 162,
                'country_code' => 'NE',
                'country' => 'Niger',
                'currency_code' => 'XOF',
            ),
            162 => 
            array (
                'id' => 163,
                'country_code' => 'NF',
                'country' => 'Norfolk Island',
                'currency_code' => 'AUD',
            ),
            163 => 
            array (
                'id' => 164,
                'country_code' => 'NG',
                'country' => 'Nigeria',
                'currency_code' => 'NGN',
            ),
            164 => 
            array (
                'id' => 165,
                'country_code' => 'NI',
                'country' => 'Nicaragua',
                'currency_code' => 'NIO',
            ),
            165 => 
            array (
                'id' => 166,
                'country_code' => 'NL',
                'country' => 'Netherlands',
                'currency_code' => 'EUR',
            ),
            166 => 
            array (
                'id' => 167,
                'country_code' => 'NO',
                'country' => 'Norway',
                'currency_code' => 'NOK',
            ),
            167 => 
            array (
                'id' => 168,
                'country_code' => 'NP',
                'country' => 'Nepal',
                'currency_code' => 'NPR',
            ),
            168 => 
            array (
                'id' => 169,
                'country_code' => 'NR',
                'country' => 'Nauru',
                'currency_code' => 'AUD',
            ),
            169 => 
            array (
                'id' => 170,
                'country_code' => 'NU',
                'country' => 'Niue',
                'currency_code' => 'NZD',
            ),
            170 => 
            array (
                'id' => 171,
                'country_code' => 'NZ',
                'country' => 'New Zealand',
                'currency_code' => 'NZD',
            ),
            171 => 
            array (
                'id' => 172,
                'country_code' => 'OM',
                'country' => 'Oman',
                'currency_code' => 'OMR',
            ),
            172 => 
            array (
                'id' => 173,
                'country_code' => 'PA',
                'country' => 'Panama',
                'currency_code' => 'PAB',
            ),
            173 => 
            array (
                'id' => 174,
                'country_code' => 'PE',
                'country' => 'Peru',
                'currency_code' => 'PEN',
            ),
            174 => 
            array (
                'id' => 175,
                'country_code' => 'PF',
                'country' => 'French Polynesia',
                'currency_code' => 'XPF',
            ),
            175 => 
            array (
                'id' => 176,
                'country_code' => 'PG',
                'country' => 'Papua New Guinea',
                'currency_code' => 'PGK',
            ),
            176 => 
            array (
                'id' => 177,
                'country_code' => 'PH',
                'country' => 'Philippines',
                'currency_code' => 'PHP',
            ),
            177 => 
            array (
                'id' => 178,
                'country_code' => 'PK',
                'country' => 'Pakistan',
                'currency_code' => 'PKR',
            ),
            178 => 
            array (
                'id' => 179,
                'country_code' => 'PL',
                'country' => 'Poland',
                'currency_code' => 'PLN',
            ),
            179 => 
            array (
                'id' => 180,
                'country_code' => 'PM',
                'country' => 'Saint Pierre and Miquelon',
                'currency_code' => 'EUR',
            ),
            180 => 
            array (
                'id' => 181,
                'country_code' => 'PN',
                'country' => 'Pitcairn Islands',
                'currency_code' => 'NZD',
            ),
            181 => 
            array (
                'id' => 182,
                'country_code' => 'PR',
                'country' => 'Puerto Rico',
                'currency_code' => 'USD',
            ),
            182 => 
            array (
                'id' => 183,
                'country_code' => 'PS',
                'country' => 'Palestine',
                'currency_code' => 'ILS',
            ),
            183 => 
            array (
                'id' => 184,
                'country_code' => 'PT',
                'country' => 'Portugal',
                'currency_code' => 'EUR',
            ),
            184 => 
            array (
                'id' => 185,
                'country_code' => 'PW',
                'country' => 'Palau',
                'currency_code' => 'USD',
            ),
            185 => 
            array (
                'id' => 186,
                'country_code' => 'PY',
                'country' => 'Paraguay',
                'currency_code' => 'PYG',
            ),
            186 => 
            array (
                'id' => 187,
                'country_code' => 'QA',
                'country' => 'Qatar',
                'currency_code' => 'QAR',
            ),
            187 => 
            array (
                'id' => 188,
                'country_code' => 'RE',
                'country' => 'RÃƒÂ©union',
                'currency_code' => 'EUR',
            ),
            188 => 
            array (
                'id' => 189,
                'country_code' => 'RO',
                'country' => 'Romania',
                'currency_code' => 'RON',
            ),
            189 => 
            array (
                'id' => 190,
                'country_code' => 'RS',
                'country' => 'Serbia',
                'currency_code' => 'RSD',
            ),
            190 => 
            array (
                'id' => 191,
                'country_code' => 'RU',
                'country' => 'Russia',
                'currency_code' => 'RUB',
            ),
            191 => 
            array (
                'id' => 192,
                'country_code' => 'RW',
                'country' => 'Rwanda',
                'currency_code' => 'RWF',
            ),
            192 => 
            array (
                'id' => 193,
                'country_code' => 'SA',
                'country' => 'Saudi Arabia',
                'currency_code' => 'SAR',
            ),
            193 => 
            array (
                'id' => 194,
                'country_code' => 'SB',
                'country' => 'Solomon Islands',
                'currency_code' => 'SBD',
            ),
            194 => 
            array (
                'id' => 195,
                'country_code' => 'SC',
                'country' => 'Seychelles',
                'currency_code' => 'SCR',
            ),
            195 => 
            array (
                'id' => 196,
                'country_code' => 'SD',
                'country' => 'Sudan',
                'currency_code' => 'SDG',
            ),
            196 => 
            array (
                'id' => 197,
                'country_code' => 'SE',
                'country' => 'Sweden',
                'currency_code' => 'SEK',
            ),
            197 => 
            array (
                'id' => 198,
                'country_code' => 'SG',
                'country' => 'Singapore',
                'currency_code' => 'SGD',
            ),
            198 => 
            array (
                'id' => 199,
                'country_code' => 'SH',
                'country' => 'Saint Helena',
                'currency_code' => 'SHP',
            ),
            199 => 
            array (
                'id' => 200,
                'country_code' => 'SI',
                'country' => 'Slovenia',
                'currency_code' => 'EUR',
            ),
            200 => 
            array (
                'id' => 201,
                'country_code' => 'SJ',
                'country' => 'Svalbard and Jan Mayen',
                'currency_code' => 'NOK',
            ),
            201 => 
            array (
                'id' => 202,
                'country_code' => 'SK',
                'country' => 'Slovakia',
                'currency_code' => 'EUR',
            ),
            202 => 
            array (
                'id' => 203,
                'country_code' => 'SL',
                'country' => 'Sierra Leone',
                'currency_code' => 'SLL',
            ),
            203 => 
            array (
                'id' => 204,
                'country_code' => 'SM',
                'country' => 'San Marino',
                'currency_code' => 'EUR',
            ),
            204 => 
            array (
                'id' => 205,
                'country_code' => 'SN',
                'country' => 'Senegal',
                'currency_code' => 'XOF',
            ),
            205 => 
            array (
                'id' => 206,
                'country_code' => 'SO',
                'country' => 'Somalia',
                'currency_code' => 'SOS',
            ),
            206 => 
            array (
                'id' => 207,
                'country_code' => 'SR',
                'country' => 'Suriname',
                'currency_code' => 'SRD',
            ),
            207 => 
            array (
                'id' => 208,
                'country_code' => 'SS',
                'country' => 'South Sudan',
                'currency_code' => 'SSP',
            ),
            208 => 
            array (
                'id' => 209,
                'country_code' => 'ST',
                'country' => 'SÃƒÂ£o TomÃƒÂ© and PrÃƒÂ­ncipe',
                'currency_code' => 'STD',
            ),
            209 => 
            array (
                'id' => 210,
                'country_code' => 'SV',
                'country' => 'El Salvador',
                'currency_code' => 'USD',
            ),
            210 => 
            array (
                'id' => 211,
                'country_code' => 'SX',
                'country' => 'Sint Maarten',
                'currency_code' => 'ANG',
            ),
            211 => 
            array (
                'id' => 212,
                'country_code' => 'SY',
                'country' => 'Syria',
                'currency_code' => 'SYP',
            ),
            212 => 
            array (
                'id' => 213,
                'country_code' => 'SZ',
                'country' => 'Swaziland',
                'currency_code' => 'SZL',
            ),
            213 => 
            array (
                'id' => 214,
                'country_code' => 'TC',
                'country' => 'Turks and Caicos Islands',
                'currency_code' => 'USD',
            ),
            214 => 
            array (
                'id' => 215,
                'country_code' => 'TD',
                'country' => 'Chad',
                'currency_code' => 'XAF',
            ),
            215 => 
            array (
                'id' => 216,
                'country_code' => 'TF',
                'country' => 'French Southern Territories',
                'currency_code' => 'EUR',
            ),
            216 => 
            array (
                'id' => 217,
                'country_code' => 'TG',
                'country' => 'Togo',
                'currency_code' => 'XOF',
            ),
            217 => 
            array (
                'id' => 218,
                'country_code' => 'TH',
                'country' => 'Thailand',
                'currency_code' => 'THB',
            ),
            218 => 
            array (
                'id' => 219,
                'country_code' => 'TJ',
                'country' => 'Tajikistan',
                'currency_code' => 'TJS',
            ),
            219 => 
            array (
                'id' => 220,
                'country_code' => 'TK',
                'country' => 'Tokelau',
                'currency_code' => 'NZD',
            ),
            220 => 
            array (
                'id' => 221,
                'country_code' => 'TL',
                'country' => 'East Timor',
                'currency_code' => 'USD',
            ),
            221 => 
            array (
                'id' => 222,
                'country_code' => 'TM',
                'country' => 'Turkmenistan',
                'currency_code' => 'TMT',
            ),
            222 => 
            array (
                'id' => 223,
                'country_code' => 'TN',
                'country' => 'Tunisia',
                'currency_code' => 'TND',
            ),
            223 => 
            array (
                'id' => 224,
                'country_code' => 'TO',
                'country' => 'Tonga',
                'currency_code' => 'TOP',
            ),
            224 => 
            array (
                'id' => 225,
                'country_code' => 'TR',
                'country' => 'Turkey',
                'currency_code' => 'TRY',
            ),
            225 => 
            array (
                'id' => 226,
                'country_code' => 'TT',
                'country' => 'Trinidad and Tobago',
                'currency_code' => 'TTD',
            ),
            226 => 
            array (
                'id' => 227,
                'country_code' => 'TV',
                'country' => 'Tuvalu',
                'currency_code' => 'AUD',
            ),
            227 => 
            array (
                'id' => 228,
                'country_code' => 'TW',
                'country' => 'Taiwan',
                'currency_code' => 'TWD',
            ),
            228 => 
            array (
                'id' => 229,
                'country_code' => 'TZ',
                'country' => 'Tanzania',
                'currency_code' => 'TZS',
            ),
            229 => 
            array (
                'id' => 230,
                'country_code' => 'UA',
                'country' => 'Ukraine',
                'currency_code' => 'UAH',
            ),
            230 => 
            array (
                'id' => 231,
                'country_code' => 'UG',
                'country' => 'Uganda',
                'currency_code' => 'UGX',
            ),
            231 => 
            array (
                'id' => 232,
                'country_code' => 'UM',
                'country' => 'U.S. Minor Outlying Islands',
                'currency_code' => 'USD',
            ),
            232 => 
            array (
                'id' => 233,
                'country_code' => 'US',
                'country' => 'United States',
                'currency_code' => 'USD',
            ),
            233 => 
            array (
                'id' => 234,
                'country_code' => 'UY',
                'country' => 'Uruguay',
                'currency_code' => 'UYU',
            ),
            234 => 
            array (
                'id' => 235,
                'country_code' => 'UZ',
                'country' => 'Uzbekistan',
                'currency_code' => 'UZS',
            ),
            235 => 
            array (
                'id' => 236,
                'country_code' => 'VA',
                'country' => 'Vatican City',
                'currency_code' => 'EUR',
            ),
            236 => 
            array (
                'id' => 237,
                'country_code' => 'VC',
                'country' => 'Saint Vincent and the Grenadines',
                'currency_code' => 'XCD',
            ),
            237 => 
            array (
                'id' => 238,
                'country_code' => 'VE',
                'country' => 'Venezuela',
                'currency_code' => 'VEF',
            ),
            238 => 
            array (
                'id' => 239,
                'country_code' => 'VG',
                'country' => 'British Virgin Islands',
                'currency_code' => 'USD',
            ),
            239 => 
            array (
                'id' => 240,
                'country_code' => 'VI',
                'country' => 'U.S. Virgin Islands',
                'currency_code' => 'USD',
            ),
            240 => 
            array (
                'id' => 241,
                'country_code' => 'VN',
                'country' => 'Vietnam',
                'currency_code' => 'VND',
            ),
            241 => 
            array (
                'id' => 242,
                'country_code' => 'VU',
                'country' => 'Vanuatu',
                'currency_code' => 'VUV',
            ),
            242 => 
            array (
                'id' => 243,
                'country_code' => 'WF',
                'country' => 'Wallis and Futuna',
                'currency_code' => 'XPF',
            ),
            243 => 
            array (
                'id' => 244,
                'country_code' => 'WS',
                'country' => 'Samoa',
                'currency_code' => 'WST',
            ),
            244 => 
            array (
                'id' => 245,
                'country_code' => 'XK',
                'country' => 'Kosovo',
                'currency_code' => 'EUR',
            ),
            245 => 
            array (
                'id' => 246,
                'country_code' => 'YE',
                'country' => 'Yemen',
                'currency_code' => 'YER',
            ),
            246 => 
            array (
                'id' => 247,
                'country_code' => 'YT',
                'country' => 'Mayotte',
                'currency_code' => 'EUR',
            ),
            247 => 
            array (
                'id' => 248,
                'country_code' => 'ZA',
                'country' => 'South Africa',
                'currency_code' => 'ZAR',
            ),
            248 => 
            array (
                'id' => 249,
                'country_code' => 'ZM',
                'country' => 'Zambia',
                'currency_code' => 'ZMW',
            ),
            249 => 
            array (
                'id' => 250,
                'country_code' => 'ZW',
                'country' => 'Zimbabwe',
                'currency_code' => 'ZWL',
            ),
        ));
        
        
    }
}